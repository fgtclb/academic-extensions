<?php

declare(strict_types=1);

namespace FGTCLB\AcademicsDevSite\Tests\Unit;

use Composer\Autoload\ClassLoader;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Attribute\Exclude;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\RepeatableInterface;

/**
 * Every committed template of the development instances marks every upgrade
 * wizard of the extensions as done.
 *
 * TYPO3 records a non-repeatable wizard as done in `sys_registry`, namespace
 * `installUpdate`, keyed by its class name, with the serialized value `i:1;`.
 * `typo3 setup` writes that row for the wizards registered when a template is
 * built, so a wizard added later, or moved to a new class, is missing from it.
 * An instance restored from that template then offers the wizard as if the
 * seed still needed it, which is how two wizards went missing unnoticed
 * (ACE-899). The rule is documented in "When an upgrade wizard is added" of
 * "docs/development/environment.md", this is what enforces it.
 *
 * The templates are read as files, read only, through PDO. Every template of
 * the branch is read, not only the one of the installed core, so one run on
 * either core version checks all of them.
 *
 * The wizards come from the code, not from a list: every class the composer
 * autoloader maps below `packages/fgtclb/<package>/Classes/` whose file names
 * `UpgradeWizard` is loaded and reflected. A wizard is a class with the
 * `UpgradeWizard` attribute of TYPO3, however its import is spelled or its
 * attribute is wrapped. One with Symfony's `#[Exclude]` is not registered,
 * and one implementing `RepeatableInterface` is never recorded as done, so
 * neither is expected in a template. Core and third-party wizards are out of
 * scope, they are not below `packages/fgtclb/`.
 */
final class TemplateUpgradeWizardsTest extends TestCase
{
    /**
     * The wizards this check finds today. A discovery that finds fewer is
     * treated as broken rather than as a branch with fewer wizards. Raise it
     * in the change that adds a wizard, so the floor keeps up with the
     * branch, and lower it in the change that removes one.
     */
    private const MINIMUM_WIZARDS = 15;

    private const REGISTRY_NAMESPACE = 'installUpdate';

    private const DONE = 'i:1;';

    private const INSTRUCTIONS = 'See "When an upgrade wizard is added" in docs/development/environment.md.';

    /**
     * @var array<class-string, string>|null
     */
    private static ?array $wizards = null;

    /**
     * Why the discovery failed, thrown again on every later call. Trying
     * again would make the autoloader include a file that declares a class
     * other than the one its path names a second time, which is a fatal error.
     */
    private static ?\RuntimeException $discoveryFailure = null;

    /**
     * @var array<string, array<string, string>>
     */
    private static array $doneEntries = [];

    /**
     * An array and not a generator: PHPUnit 10 iterates a generator outside
     * the guard it puts around a data provider, so a failed discovery would
     * abort the whole run instead of failing this test.
     *
     * @return array<string, array{string, class-string, string}>
     */
    public static function templateWizardDataProvider(): array
    {
        $dataSets = [];
        foreach (self::existingTemplates() as $template) {
            foreach (self::wizardsToMarkDone() as $className => $identifier) {
                $dataSets[basename($template) . ': ' . $className] = [$template, $className, $identifier];
            }
        }

        return $dataSets;
    }

    #[Test]
    public function everyTemplateOfTheBranchExists(): void
    {
        $majorVersions = self::supportedMajorVersions();
        $this->assertNotSame([], $majorVersions, 'No supported TYPO3 version found in packages-dev/monorepo-shared/composer.json.');

        foreach ($majorVersions as $majorVersion) {
            $this->assertFileExists(
                self::templateDirectory() . '/core-' . $majorVersion . '.sqlite',
                sprintf(
                    '"sqlite-databases/core-%1$d.sqlite" is missing. The branch supports TYPO3 v%1$d, see'
                    . ' packages-dev/monorepo-shared/composer.json, and its development instance "core-%1$d" is'
                    . ' restored from that template. See "Rebuilding an instance from nothing" in'
                    . ' docs/development/environment.md.',
                    $majorVersion,
                ),
            );
        }
    }

    #[Test]
    public function theUpgradeWizardsAreFound(): void
    {
        $this->assertGreaterThanOrEqual(
            self::MINIMUM_WIZARDS,
            count(self::wizardsToMarkDone()),
            sprintf(
                "The upgrade wizards of the extensions were not found, only these:\n  %s\n\nThe discovery is"
                . ' broken, or a wizard was removed and %s::MINIMUM_WIZARDS was not lowered with it.',
                implode("\n  ", array_keys(self::wizardsToMarkDone())) ?: '(none)',
                self::class,
            ),
        );
    }

    #[Test]
    #[DataProvider('templateWizardDataProvider')]
    public function theTemplateMarksTheUpgradeWizardDone(string $template, string $className, string $identifier): void
    {
        $instance = basename($template, '.sqlite');
        $this->assertSame(
            self::DONE,
            self::doneEntries($template)[$className] ?? null,
            sprintf(
                '"sqlite-databases/%1$s" does not mark the upgrade wizard "%2$s" as done: sys_registry has no'
                . ' row "%3$s" / "%2$s" with the value "%4$s". An instance restored from it offers the'
                . " wizard as if the seed still needed it.\n\nMark it done in that template:\n\n"
                . "  cd %5\$s\n"
                . "  ddev composer sqlite:apply\n"
                . "  ddev exec vendor/bin/typo3 upgrade:run %6\$s --no-interaction\n"
                . "  ddev composer sqlite:backup\n\n"
                . 'and commit "sqlite-databases/%1$s". %7$s',
                basename($template),
                $className,
                self::REGISTRY_NAMESPACE,
                self::DONE,
                $instance,
                $identifier,
                self::INSTRUCTIONS,
            ),
        );
    }

    /**
     * @return \Generator<string, array{object, string|null}>
     */
    public static function upgradeWizardClassDataProvider(): \Generator
    {
        yield 'a registered wizard' => [
            new #[UpgradeWizard('fixture_registered')] class () {},
            'fixture_registered',
        ];
        yield 'an excluded wizard' => [
            new #[Exclude] #[UpgradeWizard('fixture_excluded')] class () {},
            null,
        ];
        yield 'a repeatable wizard' => [
            new #[UpgradeWizard('fixture_repeatable')] class () implements RepeatableInterface {},
            null,
        ];
        yield 'a class without the attribute' => [
            new class () {},
            null,
        ];
    }

    /**
     * What the discovery makes of a class, shown on classes that are not
     * below `packages/fgtclb/` and are therefore never found by it.
     */
    #[Test]
    #[DataProvider('upgradeWizardClassDataProvider')]
    public function onlyARegisteredNonRepeatableWizardIsExpectedInATemplate(object $class, ?string $expectedIdentifier): void
    {
        $this->assertSame($expectedIdentifier, self::identifierToMarkDone(new \ReflectionObject($class)));
    }

    /**
     * The registered, non-repeatable upgrade wizards of the extensions, by
     * class name, with their identifier.
     *
     * @return array<class-string, string>
     */
    private static function wizardsToMarkDone(): array
    {
        if (self::$wizards !== null) {
            return self::$wizards;
        }
        if (self::$discoveryFailure !== null) {
            throw self::$discoveryFailure;
        }

        $wizards = [];
        foreach (self::candidateClassNames() as $className => $file) {
            if (!class_exists($className)) {
                // An enum counts as a class here, so only an interface or a
                // trait is left out on purpose. Anything else is a file whose
                // class does not match its PSR-4 path, and a wizard in it
                // would be missed without a word. No second autoload: the
                // loader includes the file again, and the class it does
                // declare would be declared twice.
                if (interface_exists($className, false) || trait_exists($className, false)) {
                    continue;
                }
                throw self::$discoveryFailure = new \RuntimeException(
                    sprintf(
                        'The file "%s" does not declare "%s", the class its path names for the composer'
                        . ' autoloader. Fix the namespace or the class name of the file.',
                        $file,
                        $className,
                    ),
                    1791809137,
                );
            }
            $identifier = self::identifierToMarkDone(new \ReflectionClass($className));
            if ($identifier !== null) {
                $wizards[$className] = $identifier;
            }
        }
        ksort($wizards);

        return self::$wizards = $wizards;
    }

    /**
     * The identifier of a class TYPO3 registers as an upgrade wizard and
     * records as done once it ran, null for every other class.
     *
     * @param \ReflectionClass<object> $class
     */
    private static function identifierToMarkDone(\ReflectionClass $class): ?string
    {
        $attributes = $class->getAttributes(UpgradeWizard::class, \ReflectionAttribute::IS_INSTANCEOF);
        if ($attributes === []
            || !$class->isInstantiable()
            || $class->getAttributes(Exclude::class) !== []
            || $class->implementsInterface(RepeatableInterface::class)
        ) {
            return null;
        }

        $attribute = $attributes[0]->newInstance();
        if (!property_exists($attribute, 'identifier') || !is_string($attribute->identifier)) {
            throw new \RuntimeException(
                sprintf('The upgrade wizard attribute of "%s" has no identifier.', $class->getName()),
                1791808454,
            );
        }

        return $attribute->identifier;
    }

    /**
     * Every class name the composer autoloader maps to a `Classes/` folder of
     * a package below `packages/fgtclb/`, derived from its PSR-4 prefixes,
     * with its file.
     *
     * Only a file that names `UpgradeWizard` somewhere is a candidate, so the
     * test loads some fifteen classes rather than every class of the
     * extensions, and an unrelated class that cannot be loaded in the unit
     * suite cannot break it. The match is a substring, not a pattern: an
     * aliased import, an attribute named through an imported namespace and a
     * group import all spell the class name out. It ignores case, as PHP
     * does for class names. Reflection decides what a candidate is.
     *
     * @return array<string, string>
     */
    private static function candidateClassNames(): array
    {
        $packages = realpath(self::repositoryPath() . '/packages/fgtclb');
        if ($packages === false) {
            return [];
        }
        $classesFolder = '#^' . preg_quote($packages, '#') . '/[^/]+/Classes$#';

        $classNames = [];
        foreach (ClassLoader::getRegisteredLoaders() as $loader) {
            foreach ($loader->getPrefixesPsr4() as $prefix => $directories) {
                foreach ($directories as $directory) {
                    $directory = realpath($directory);
                    if ($directory === false || preg_match($classesFolder, $directory) !== 1) {
                        continue;
                    }
                    $files = new \RecursiveIteratorIterator(
                        new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
                    );
                    foreach ($files as $file) {
                        if (!$file instanceof \SplFileInfo
                            || $file->getExtension() !== 'php'
                            || stripos((string)file_get_contents($file->getPathname()), 'UpgradeWizard') === false
                        ) {
                            continue;
                        }
                        $relativePath = substr($file->getPathname(), strlen($directory) + 1, -4);
                        $classNames[$prefix . str_replace('/', '\\', $relativePath)] = $file->getPathname();
                    }
                }
            }
        }
        ksort($classNames);

        return $classNames;
    }

    /**
     * The value of every `installUpdate` row of a template, by class name.
     *
     * @return array<string, string>
     */
    private static function doneEntries(string $template): array
    {
        if (isset(self::$doneEntries[$template])) {
            return self::$doneEntries[$template];
        }

        // Read only, as SqliteFileRowReader of the functional seed checks does:
        // the template is a committed file. The `PDO::SQLITE_*` constants are
        // deprecated from PHP 8.5 on and their replacements on `Pdo\Sqlite` do
        // not exist before PHP 8.4, so the pair is resolved by name.
        $openFlags = defined('Pdo\\Sqlite::ATTR_OPEN_FLAGS')
            ? (int)constant('Pdo\\Sqlite::ATTR_OPEN_FLAGS')
            : (int)constant('PDO::SQLITE_ATTR_OPEN_FLAGS');
        $readOnly = defined('Pdo\\Sqlite::OPEN_READONLY')
            ? (int)constant('Pdo\\Sqlite::OPEN_READONLY')
            : (int)constant('PDO::SQLITE_OPEN_READONLY');
        $connection = new \PDO('sqlite:file:' . $template . '?mode=ro', null, null, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            $openFlags => $readOnly,
        ]);

        $statement = $connection->prepare(
            'SELECT entry_key, entry_value FROM sys_registry WHERE entry_namespace = :namespace ORDER BY uid',
        );
        $statement->execute(['namespace' => self::REGISTRY_NAMESPACE]);

        $entries = [];
        foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $entries[(string)$row['entry_key']] = (string)$row['entry_value'];
        }

        return self::$doneEntries[$template] = $entries;
    }

    /**
     * Every template file there is, so a template of a version the branch no
     * longer names is still checked while it is committed.
     *
     * @return list<string>
     */
    private static function existingTemplates(): array
    {
        $templates = glob(self::templateDirectory() . '/core-*.sqlite') ?: [];
        sort($templates);

        return $templates;
    }

    /**
     * The TYPO3 major versions of the branch, read from the core constraint
     * every package shares.
     *
     * @return list<int>
     */
    private static function supportedMajorVersions(): array
    {
        $composer = json_decode(
            (string)file_get_contents(self::repositoryPath() . '/packages-dev/monorepo-shared/composer.json'),
            true,
        );
        $constraint = is_array($composer) && is_string($composer['require']['typo3/cms-core'] ?? null)
            ? $composer['require']['typo3/cms-core']
            : '';
        preg_match_all('/(?:^|\|\|)\s*[~^]?(\d+)\./', $constraint, $matches);

        return array_values(array_unique(array_map(intval(...), $matches[1])));
    }

    private static function templateDirectory(): string
    {
        return self::repositoryPath() . '/sqlite-databases';
    }

    private static function repositoryPath(): string
    {
        return dirname(__DIR__, 4);
    }
}
