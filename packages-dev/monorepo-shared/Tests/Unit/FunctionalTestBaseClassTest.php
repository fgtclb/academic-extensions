<?php

declare(strict_types=1);

namespace FGTCLB\AcademicsMonorepoShared\Tests\Unit;

use FGTCLB\TestingHelper\TestCase\FunctionalTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase as TestingFrameworkFunctionalTestCase;

/**
 * Every functional test case of the repository extends the shared base class of
 * `packages-dev/testing-helper`, directly or through the abstract test case of its
 * extension, and so gets the configuration every test instance starts from.
 *
 * The base class keeps the Extbase class schema cache in memory. A class that extends the
 * testing framework without it builds an instance that persists the class schemata again,
 * and the core defect behind that fails a test only when a certain set of classes ran
 * before it in the same process. It would surface weeks later in another class, and only in
 * CI. See {@see FunctionalTestCase}.
 *
 * A functional test case is a class below `Tests/Functional/` that extends the functional
 * test case of the testing framework. Fixture extensions below a `Fixtures/` directory are
 * not read.
 */
final class FunctionalTestBaseClassTest extends TestCase
{
    /**
     * @return \Generator<string, array{string}>
     */
    public static function functionalTestDirectoriesDataProvider(): \Generator
    {
        $directories = array_merge(
            glob(self::repositoryPath() . '/packages/fgtclb/*/Tests/Functional', GLOB_ONLYDIR) ?: [],
            glob(self::repositoryPath() . '/packages-dev/*/Tests/Functional', GLOB_ONLYDIR) ?: [],
        );
        sort($directories);
        foreach ($directories as $directory) {
            yield substr(dirname($directory, 2), strlen(self::repositoryPath()) + 1) => [$directory];
        }
    }

    #[Test]
    public function everyPackageWithFunctionalTestsIsRead(): void
    {
        $packages = array_keys(iterator_to_array(self::functionalTestDirectoriesDataProvider()));
        $this->assertContains('packages-dev/dev-site', $packages);
        $this->assertContains('packages-dev/testing-helper', $packages);
        $this->assertContains('packages/fgtclb/academic-base', $packages);
        $this->assertGreaterThanOrEqual(14, count($packages));
    }

    #[DataProvider('functionalTestDirectoriesDataProvider')]
    #[Test]
    public function everyFunctionalTestCaseExtendsTheSharedBaseClass(string $directory): void
    {
        $functionalTestCases = [];
        $missing = [];
        foreach (self::declaredClasses($directory) as $className => $file) {
            if (!class_exists($className)) {
                $missing[] = sprintf('%s does not autoload %s', $file, $className);
                continue;
            }
            if (!is_subclass_of($className, TestingFrameworkFunctionalTestCase::class)) {
                continue;
            }
            $functionalTestCases[] = $className;
            if (!is_subclass_of($className, FunctionalTestCase::class)) {
                $missing[] = sprintf('%s: %s', $file, $className);
            }
        }

        $this->assertNotSame([], $functionalTestCases, 'No functional test case was found, so nothing was checked.');
        $this->assertSame(
            [],
            $missing,
            sprintf(
                "These functional test cases do not extend %s:\n  %s\n\n"
                . 'Extend it, or the abstract test case of the extension, instead of the test case of the'
                . ' testing framework or of sbuerk/typo3-site-based-test-trait. It keeps the Extbase class'
                . ' schema cache of the test instance in memory, see docs/testing/functional-tests.md.',
                FunctionalTestCase::class,
                implode("\n  ", $missing),
            ),
        );
    }

    /**
     * The class each PHP file below the directory declares, outside of `Fixtures/`, as class
     * name => the file below the repository root.
     *
     * @return array<string, string>
     */
    private static function declaredClasses(string $directory): array
    {
        $classes = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
        );
        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            $path = $file->getPathname();
            if ($file->getExtension() !== 'php' || str_contains(substr($path, strlen($directory)), '/Fixtures/')) {
                continue;
            }
            $tokens = \PhpToken::tokenize((string)file_get_contents($path));
            $namespace = '';
            foreach ($tokens as $index => $token) {
                if ($token->is(T_NAMESPACE)) {
                    $namespace = self::nextName($tokens, $index);
                    continue;
                }
                if (!$token->is(T_CLASS) || self::previousToken($tokens, $index)?->is([T_DOUBLE_COLON, T_NEW]) === true) {
                    continue;
                }
                $classes[ltrim($namespace . '\\' . self::nextName($tokens, $index), '\\')] = substr($path, strlen(self::repositoryPath()) + 1);
                break;
            }
        }
        ksort($classes);
        return $classes;
    }

    /**
     * @param list<\PhpToken> $tokens
     */
    private static function nextName(array $tokens, int $index): string
    {
        $count = count($tokens);
        for ($position = $index + 1; $position < $count; $position++) {
            if (!$tokens[$position]->isIgnorable()) {
                return ltrim($tokens[$position]->text, '\\');
            }
        }
        return '';
    }

    /**
     * @param list<\PhpToken> $tokens
     */
    private static function previousToken(array $tokens, int $index): ?\PhpToken
    {
        for ($position = $index - 1; $position >= 0; $position--) {
            if (!$tokens[$position]->isIgnorable()) {
                return $tokens[$position];
            }
        }
        return null;
    }

    private static function repositoryPath(): string
    {
        return dirname(__DIR__, 4);
    }
}
