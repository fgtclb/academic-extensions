<?php

declare(strict_types=1);

namespace FGTCLB\AcademicsMonorepoShared\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Every `f:translate` of a Fluid template of this repository names its extension, and no
 * template, and no `translate()` call in PHP that names its extension directly, passes an
 * extension name with an underscore.
 *
 * `f:translate` reads a site's `_LOCAL_LANG` overrides from `plugin.tx_<name>` and
 * `plugin.tx_<name>_<plugin>`, and TYPO3 v12 and v13 build that path from the name
 * exactly as the template spells it, only lowercased. With the extension key
 * `academic_partners` it looked in `plugin.tx_academic_partners`, a path no site uses, and
 * every override was ignored (ACE-740). The extension name `AcademicPartners` reads the
 * documented path on every core version and still finds the extension's language file.
 *
 * Without a name, the core picks one itself: TYPO3 v12 and v13 take the name of the plugin
 * request inside a plugin, and the extension key of a full `LLL:EXT:` reference outside of
 * one - underscored again. The functional label tests of each extension prove every kind of
 * call, but only as the first translation of its file in a request, since the core keeps
 * the overrides one translation has read for the ones after it. This test holds the
 * templates, and the names handed to `translate()` directly in PHP.
 */
final class TranslationExtensionNameTest extends TestCase
{
    /**
     * @return \Generator<string, array{string}>
     */
    public static function extensionDirectoriesDataProvider(): \Generator
    {
        $directories = glob(self::repositoryPath() . '/packages/fgtclb/*/Resources/Private', GLOB_ONLYDIR) ?: [];
        sort($directories);
        foreach ($directories as $directory) {
            yield basename(dirname($directory, 2)) => [$directory];
        }
    }

    #[DataProvider('extensionDirectoriesDataProvider')]
    #[Test]
    public function noTemplatePassesAnExtensionNameWithAnUnderscore(string $directory): void
    {
        $found = [];
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
        );
        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            if ($file->getExtension() !== 'html') {
                continue;
            }
            $content = (string)file_get_contents($file->getPathname());
            // Both notations, `extensionName="…"` and `extensionName: '…'`, the latter also
            // with the quotes escaped inside the argument of another view helper.
            preg_match_all('/extensionName\s*[:=]\s*\\\\?["\']([^"\'\\\\]*_[^"\'\\\\]*)\\\\?["\']/', $content, $matches, PREG_OFFSET_CAPTURE);
            foreach ($matches[1] as [$name, $offset]) {
                $found[] = sprintf(
                    '%s:%d %s',
                    substr($file->getPathname(), strlen($directory) + 1),
                    substr_count($content, "\n", 0, $offset) + 1,
                    $name,
                );
            }
        }
        sort($found);

        $this->assertSame(
            [],
            $found,
            'Pass the extension name in UpperCamelCase - "AcademicPartners", not "academic_partners" -'
            . ' or TYPO3 v12 and v13 read the _LOCAL_LANG overrides of these calls from the wrong path.',
        );
    }

    #[DataProvider('extensionDirectoriesDataProvider')]
    #[Test]
    public function everyTranslationNamesItsExtension(string $directory): void
    {
        $found = [];
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
        );
        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            if ($file->getExtension() !== 'html') {
                continue;
            }
            $content = (string)file_get_contents($file->getPathname());
            foreach (self::translateCalls($content) as [$offset, $arguments]) {
                if (!preg_match('/\bextensionName\s*[:=]/', $arguments)) {
                    $found[] = sprintf(
                        '%s:%d',
                        substr($file->getPathname(), strlen($directory) + 1),
                        substr_count($content, "\n", 0, $offset) + 1,
                    );
                }
            }
        }
        sort($found);

        $this->assertSame(
            [],
            $found,
            'Name the extension of every translation - extensionName: \'AcademicPartners\' - or the core'
            . ' picks one, and not the same one inside and outside a plugin.',
        );
    }

    /**
     * @return \Generator<string, array{string}>
     */
    public static function classDirectoriesDataProvider(): \Generator
    {
        $directories = glob(self::repositoryPath() . '/packages/fgtclb/*/Classes', GLOB_ONLYDIR) ?: [];
        sort($directories);
        foreach ($directories as $directory) {
            yield basename(dirname($directory)) => [$directory];
        }
    }

    /**
     * A string literal handed to a `translate()` call that has an underscore but no dot or
     * colon is an extension key passed as the extension name - a label key has dots, a
     * full reference a colon.
     */
    #[DataProvider('classDirectoriesDataProvider')]
    #[Test]
    public function noTranslationInPhpPassesAnExtensionNameWithAnUnderscore(string $directory): void
    {
        $found = [];
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
        );
        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $tokens = \PhpToken::tokenize((string)file_get_contents($file->getPathname()));
            $count = count($tokens);
            for ($index = 0; $index < $count; $index++) {
                if (!$tokens[$index]->is(T_STRING) || $tokens[$index]->text !== 'translate') {
                    continue;
                }
                $next = $index + 1;
                while ($next < $count && $tokens[$next]->isIgnorable()) {
                    $next++;
                }
                if ($next >= $count || $tokens[$next]->text !== '(') {
                    continue;
                }
                $depth = 0;
                for ($position = $next; $position < $count; $position++) {
                    $text = $tokens[$position]->text;
                    if ($text === '(' || $text === '[') {
                        $depth++;
                    } elseif ($text === ')' || $text === ']') {
                        $depth--;
                        if ($depth === 0) {
                            break;
                        }
                    } elseif ($depth === 1 && $tokens[$position]->is(T_CONSTANT_ENCAPSED_STRING)) {
                        $value = substr($text, 1, -1);
                        if (str_contains($value, '_') && !str_contains($value, '.') && !str_contains($value, ':')) {
                            $found[] = sprintf(
                                '%s:%d %s',
                                substr($file->getPathname(), strlen($directory) + 1),
                                $tokens[$position]->line,
                                $value,
                            );
                        }
                    }
                }
            }
        }
        sort($found);

        $this->assertSame(
            [],
            $found,
            'Pass the extension name in UpperCamelCase - "AcademicPartners", not "academic_partners" -'
            . ' or TYPO3 v12 and v13 read the _LOCAL_LANG overrides of these translations from the wrong path.',
        );
    }

    /**
     * The inline calls `f:translate(…)` and the tags `<f:translate …>` of a template, each as
     * its offset and its argument text.
     *
     * @return list<array{int, string}>
     */
    private static function translateCalls(string $content): array
    {
        $calls = [];
        preg_match_all('/f:translate\s*\(/', $content, $matches, PREG_OFFSET_CAPTURE);
        foreach ($matches[0] as [$match, $offset]) {
            $start = $offset + strlen($match);
            $depth = 1;
            $quote = null;
            $length = strlen($content);
            for ($position = $start; $position < $length && $depth > 0; $position++) {
                $character = $content[$position];
                if ($character === '\\') {
                    $position++;
                } elseif ($quote !== null) {
                    if ($character === $quote) {
                        $quote = null;
                    }
                } elseif ($character === '\'' || $character === '"') {
                    $quote = $character;
                } elseif ($character === '(') {
                    $depth++;
                } elseif ($character === ')') {
                    $depth--;
                }
            }
            $calls[] = [$offset, substr($content, $start, $position - $start)];
        }
        preg_match_all('/<f:translate\b[^>]*>/', $content, $matches, PREG_OFFSET_CAPTURE);
        foreach ($matches[0] as [$match, $offset]) {
            $calls[] = [$offset, $match];
        }
        return $calls;
    }

    private static function repositoryPath(): string
    {
        return dirname(__DIR__, 4);
    }
}
