<?php

declare(strict_types=1);

namespace FGTCLB\AcademicsMonorepoShared\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;

/**
 * The extension point policy of the academic extensions, as far as a test can hold it.
 *
 * The extension points page of academic_base lists the public API of every extension of
 * this repository, and each class, interface, trait and enum it lists carries an `@api`
 * tag in its docblock. The page is what integrators read and the tag is what a
 * contributor sees; the two must not drift apart, so they are compared here.
 *
 * Every event class - a class named `…Event` below an `Event/` directory of `Classes/` -
 * is `final`, and production code other than its own file creates it at least once.
 * "Created" stands in for "dispatched": several events are handed to the dispatcher
 * through a variable, so a search for `dispatch(new …)` would miss them, but none is
 * dispatched without a `new`.
 * An event that is created somewhere else, by a factory for instance, goes into
 * {@see self::CREATED_ELSEWHERE} with the place that creates it.
 *
 * Every Extbase domain model is public API, because a project adds fields to one by
 * registering a subclass as its XCLASS, and `academic:upgrade:check` reports exactly that
 * XCLASS as a notice rather than a warning. The page and the check therefore have to agree
 * on which classes are models, so each class implementing Extbase's
 * `DomainObjectInterface` carries `@api`. And no class is both `@api` and `@internal`.
 *
 * The rules are documented in `docs/architecture/class-design.md`, section
 * "Extension points".
 */
final class ExtensionPointTest extends TestCase
{
    private const PAGE = 'packages/fgtclb/academic-base/Documentation/Developers/ExtensionPoints/Index.rst';

    /**
     * Event classes production code does not create with `new`, as class name => the
     * place that creates them instead.
     *
     * @var array<class-string, string>
     */
    private const CREATED_ELSEWHERE = [];

    /**
     * @var array<class-string, array{path: string, api: bool, internal: bool}>|null
     */
    private static ?array $declarations = null;

    /**
     * @var array<string, list<string>>|null
     */
    private static ?array $createdClasses = null;

    /**
     * @return \Generator<string, array{class-string}>
     */
    public static function eventClassesDataProvider(): \Generator
    {
        foreach (self::declarations() as $className => $declaration) {
            if (str_contains($declaration['path'], '/Event/') && str_ends_with($className, 'Event')) {
                yield $className => [$className];
            }
        }
    }

    /**
     * @param class-string $className
     */
    #[DataProvider('eventClassesDataProvider')]
    #[Test]
    public function eventClassIsFinal(string $className): void
    {
        $this->assertTrue(
            (new \ReflectionClass($className))->isFinal(),
            sprintf('%s is an event and has to be final, see "Extension points" in docs/architecture/class-design.md.', $className),
        );
    }

    /**
     * @param class-string $className
     */
    #[DataProvider('eventClassesDataProvider')]
    #[Test]
    public function eventClassIsCreatedByProductionCode(string $className): void
    {
        if (isset(self::CREATED_ELSEWHERE[$className])) {
            $this->assertTrue(class_exists($className));
            return;
        }
        $ownFile = self::declarations()[$className]['path'];
        $this->assertNotSame(
            [],
            array_values(array_diff(self::createdClasses()[$className] ?? [], [$ownFile])),
            sprintf(
                '%s is never created by another class below packages/fgtclb/*/Classes, so no listener of it is ever called.'
                . ' Dispatch it, or remove it. An event created without "new" goes into %s::CREATED_ELSEWHERE.',
                $className,
                self::class,
            ),
        );
    }

    /**
     * @return \Generator<string, array{class-string}>
     */
    public static function domainModelsDataProvider(): \Generator
    {
        foreach (self::declarations() as $className => $declaration) {
            if (is_subclass_of($className, DomainObjectInterface::class)) {
                yield $className => [$className];
            }
        }
    }

    /**
     * @param class-string $className
     */
    #[DataProvider('domainModelsDataProvider')]
    #[Test]
    public function domainModelIsTaggedAsApi(string $className): void
    {
        $this->assertTrue(
            self::declarations()[$className]['api'],
            sprintf(
                '%s is a domain model, which a project may extend, and has to carry @api and be listed on %s.',
                $className,
                self::PAGE,
            ),
        );
    }

    #[Test]
    public function noClassIsTaggedAsApiAndInternal(): void
    {
        $both = [];
        foreach (self::declarations() as $className => $declaration) {
            if ($declaration['api'] && $declaration['internal']) {
                $both[] = $className;
            }
        }

        $this->assertSame([], $both, 'These classes carry @api and @internal. Decide for one of them.');
    }

    #[Test]
    public function thePageListsExactlyTheClassesTaggedAsApi(): void
    {
        $tagged = [];
        foreach (self::declarations() as $className => $declaration) {
            if ($declaration['api']) {
                $tagged[] = $className;
            }
        }
        $listed = self::classesNamedOnThePage();

        $this->assertSame(
            [],
            array_values(array_diff($tagged, $listed)),
            sprintf('These classes carry @api and are missing on %s. List them, or remove the tag.', self::PAGE),
        );
        $this->assertSame(
            [],
            array_values(array_diff($listed, $tagged)),
            sprintf(
                'These classes are named on %s and carry no @api tag in their docblock. Tag them, or name a class'
                . ' that is not API without its namespace.',
                self::PAGE,
            ),
        );
    }

    /**
     * The class names the page names with the `:php:` role and a namespace of this
     * repository. A class that is not API is named without its namespace.
     *
     * @return list<string>
     */
    private static function classesNamedOnThePage(): array
    {
        $path = self::repositoryPath() . '/' . self::PAGE;
        if (!is_file($path)) {
            return [];
        }
        preg_match_all('/:php:`\\\\?(FGTCLB\\\\[A-Za-z0-9_\\\\]+)`/', (string)file_get_contents($path), $matches);
        $classNames = array_values(array_unique($matches[1]));
        sort($classNames);
        return $classNames;
    }

    /**
     * Every class, interface, trait and enum declared below `packages/fgtclb/*\/Classes`, as
     * its name => the file it is declared in and whether its docblock carries `@api`.
     *
     * @return array<class-string, array{path: string, api: bool, internal: bool}>
     */
    private static function declarations(): array
    {
        if (self::$declarations !== null) {
            return self::$declarations;
        }
        $declarations = [];
        foreach (self::classFiles() as $path) {
            $tokens = \PhpToken::tokenize((string)file_get_contents($path));
            $namespace = '';
            $docComment = null;
            foreach ($tokens as $index => $token) {
                if ($token->is(T_NAMESPACE)) {
                    $namespace = self::nextName($tokens, $index);
                    continue;
                }
                if ($token->is(T_DOC_COMMENT)) {
                    $docComment = $token->text;
                    continue;
                }
                if ($token->text === ';') {
                    $docComment = null;
                    continue;
                }
                if (!$token->is([T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM]) || self::previousToken($tokens, $index)?->is(T_DOUBLE_COLON)) {
                    continue;
                }
                /** @var class-string $className */
                $className = ltrim($namespace . '\\' . self::nextName($tokens, $index), '\\');
                $declarations[$className] = [
                    'path' => substr($path, strlen(self::repositoryPath()) + 1),
                    'api' => $docComment !== null && preg_match(self::tagPattern('api'), $docComment) === 1,
                    'internal' => $docComment !== null && preg_match(self::tagPattern('internal'), $docComment) === 1,
                ];
                break;
            }
        }
        ksort($declarations);
        return self::$declarations = $declarations;
    }

    /**
     * The fully qualified name of every class a file below `packages/fgtclb/*\/Classes`
     * creates with `new`, resolved through the namespace and the imports of the file, as
     * class name => the files that create it.
     *
     * @return array<string, list<string>>
     */
    private static function createdClasses(): array
    {
        if (self::$createdClasses !== null) {
            return self::$createdClasses;
        }
        $created = [];
        foreach (self::classFiles() as $path) {
            $file = substr($path, strlen(self::repositoryPath()) + 1);
            $tokens = \PhpToken::tokenize((string)file_get_contents($path));
            $namespace = '';
            $imports = [];
            $depth = 0;
            foreach ($tokens as $index => $token) {
                if ($token->text === '{' || $token->is([T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES])) {
                    $depth++;
                } elseif ($token->text === '}') {
                    $depth--;
                } elseif ($token->is(T_NAMESPACE)) {
                    $namespace = self::nextName($tokens, $index);
                } elseif ($token->is(T_USE) && $depth === 0) {
                    $name = self::nextName($tokens, $index);
                    $alias = substr($name, (int)strrpos('\\' . $name, '\\'));
                    $next = self::nextToken($tokens, $index, 2);
                    if ($next?->is(T_AS)) {
                        $alias = self::nextName($tokens, array_search($next, $tokens, true) ?: $index);
                    }
                    $imports[strtolower($alias)] = $name;
                } elseif ($token->is(T_NEW)) {
                    $next = self::nextToken($tokens, $index);
                    if ($next === null) {
                        continue;
                    }
                    if ($next->is(T_NAME_FULLY_QUALIFIED)) {
                        $created[ltrim($next->text, '\\')][] = $file;
                    } elseif ($next->is([T_STRING, T_NAME_QUALIFIED])) {
                        $parts = explode('\\', $next->text, 2);
                        $first = strtolower($parts[0]);
                        if (isset($imports[$first])) {
                            $created[$imports[$first] . (isset($parts[1]) ? '\\' . $parts[1] : '')][] = $file;
                        } else {
                            $created[ltrim($namespace . '\\' . $next->text, '\\')][] = $file;
                        }
                    }
                }
            }
        }
        return self::$createdClasses = $created;
    }

    /**
     * A tag at the start of a docblock line, not a mention of it in the text.
     */
    private static function tagPattern(string $tag): string
    {
        return '/^\s*(?:\/\*\*|\*)\s*@' . $tag . '\b/m';
    }

    /**
     * @return list<string>
     */
    private static function classFiles(): array
    {
        $files = [];
        foreach (glob(self::repositoryPath() . '/packages/fgtclb/*/Classes', GLOB_ONLYDIR) ?: [] as $directory) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            );
            /** @var \SplFileInfo $file */
            foreach ($iterator as $file) {
                if ($file->getExtension() === 'php') {
                    $files[] = $file->getPathname();
                }
            }
        }
        sort($files);
        return $files;
    }

    /**
     * @param list<\PhpToken> $tokens
     */
    private static function nextName(array $tokens, int $index): string
    {
        return ltrim(self::nextToken($tokens, $index)->text ?? '', '\\');
    }

    /**
     * The `$offset`-th token after `$index` that is neither whitespace nor a comment.
     *
     * @param list<\PhpToken> $tokens
     */
    private static function nextToken(array $tokens, int $index, int $offset = 1): ?\PhpToken
    {
        $count = count($tokens);
        for ($position = $index + 1; $position < $count; $position++) {
            if ($tokens[$position]->isIgnorable()) {
                continue;
            }
            if (--$offset === 0) {
                return $tokens[$position];
            }
        }
        return null;
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
