<?php

declare(strict_types=1);

namespace FGTCLB\AcademicsMonorepoShared\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Every Fluid template of an extension renders its icons with the icon ViewHelper of
 * academic_base, from the frontend icon registry, and names only identifiers that registry
 * knows.
 *
 * Neither mistake fails a rendering. A template on `core:icon` asks the icon registry of
 * the backend and gets the backend drawing, or TYPO3's `default-not-found` placeholder for
 * an icon that is registered for the frontend only (ACE-812 to ACE-814). The icon
 * ViewHelper of academic_base answers an identifier it does not know with the same
 * placeholder, and its registry has no fallback, so an identifier registered only in
 * `Configuration/Icons.php`, or misspelled, renders it as well. A functional test notices
 * either only when its fixture reaches the branch that renders the icon.
 *
 * Every template below `packages/fgtclb/<package>/Resources/Private/` counts as a frontend
 * template, unless it is listed in {@see self::BACKEND_TEMPLATES}. An identifier built from
 * a variable is not checked: which value it takes is decided at runtime, and the functional
 * tests of each extension render it.
 */
final class FrontendTemplateIconTest extends TestCase
{
    /**
     * The templates that are rendered in the backend, keyed by their path below
     * `packages/fgtclb/`, with the reason. They stay on the icon registry of the backend.
     */
    private const BACKEND_TEMPLATES = [
        'typo3-category-types/Resources/Private/Templates/PageCategorySummary.html' => 'The category summary above the grid of the page module, a backend view, rendered by'
            . ' PageCategorySummaryRenderer. It shows the type icons of the backend registry, as the type select of the category form does.',
    ];

    private const CORE_NAMESPACE_URI = 'http://typo3.org/ns/TYPO3/CMS/Core/ViewHelpers';
    private const CORE_NAMESPACE_PHP = 'TYPO3\\CMS\\Core\\ViewHelpers';
    private const ICON_NAMESPACE_URI = 'http://typo3.org/ns/FGTCLB/AcademicBase/ViewHelpers';
    private const ICON_NAMESPACE_PHP = 'FGTCLB\\AcademicBase\\ViewHelpers';

    /**
     * The arguments of the icon ViewHelper of academic_base that name an icon of its registry.
     */
    private const ICON_ARGUMENTS = ['identifier', 'overlay'];

    /**
     * @return \Generator<string, array{string}>
     */
    public static function extensionDirectoriesDataProvider(): \Generator
    {
        $directories = glob(self::packagesPath() . '/*/Resources/Private', GLOB_ONLYDIR) ?: [];
        sort($directories);
        foreach ($directories as $directory) {
            yield basename(dirname($directory, 2)) => [$directory];
        }
    }

    /**
     * `core` is a global namespace, so it needs no declaration. A template can bind another
     * prefix to the same namespace, which is found as well. Matched are `icon`,
     * `iconForRecord` and `iconForResource`, which all ask the icon factory of the backend,
     * as a tag and as an inline call, also inside the argument of another ViewHelper. The
     * name is matched in any letter case, because Fluid finds the ViewHelper for `Icon` as
     * well.
     */
    #[DataProvider('extensionDirectoriesDataProvider')]
    #[Test]
    public function noFrontendTemplateRendersACoreIcon(string $directory): void
    {
        $found = [];
        foreach (self::templates($directory) as $path => $content) {
            if (array_key_exists($path, self::BACKEND_TEMPLATES)) {
                continue;
            }
            $prefixes = self::prefixesOf($content, self::CORE_NAMESPACE_URI, self::CORE_NAMESPACE_PHP);
            $prefixes[] = 'core';
            foreach (array_unique($prefixes) as $prefix) {
                $quoted = preg_quote($prefix, '/');
                preg_match_all('/<' . $quoted . ':((?i:icon)\w*)\b|(?<![\w.:-])' . $quoted . ':((?i:icon)\w*)\s*\(/', $content, $matches, PREG_OFFSET_CAPTURE | PREG_UNMATCHED_AS_NULL);
                foreach ($matches[0] as $index => [, $offset]) {
                    $found[] = sprintf(
                        '%s:%d %s:%s',
                        $path,
                        self::lineOf($content, (int)$offset),
                        $prefix,
                        $matches[1][$index][0] ?? $matches[2][$index][0],
                    );
                }
            }
        }
        sort($found);

        $this->assertSame(
            [],
            $found,
            'Render a frontend icon with the icon ViewHelper of academic_base,'
            . ' xmlns:ab="' . self::ICON_NAMESPACE_URI . '" and <ab:icon identifier="…" />, and register it in'
            . ' Configuration/FrontendIcons.php. A template rendered in the backend goes into BACKEND_TEMPLATES of this'
            . ' test, with the reason.',
        );
    }

    /**
     * A listed file that is gone would otherwise exempt the next file created at its path.
     */
    #[Test]
    public function everyListedBackendTemplateExists(): void
    {
        foreach (array_keys(self::BACKEND_TEMPLATES) as $path) {
            $this->assertFileExists(self::packagesPath() . '/' . $path, 'Remove the entry from BACKEND_TEMPLATES.');
        }
    }

    /**
     * One test over all packages, because a template may name an icon another package
     * registers. Accepted are the keys of every `Configuration/FrontendIcons.php` and the
     * identifiers category_types contributes for a type or group of a
     * `Configuration/CategoryTypes.yaml` that declares an icon file.
     */
    #[Test]
    public function everyLiteralIconIdentifierIsRegisteredForTheFrontend(): void
    {
        $files = glob(self::packagesPath() . '/*/Configuration/FrontendIcons.php') ?: [];
        $this->assertNotSame([], $files, 'No Configuration/FrontendIcons.php was found.');
        $registered = self::frontendIconIdentifiers($files);

        $checked = 0;
        $found = [];
        foreach (self::extensionDirectoriesDataProvider() as [$directory]) {
            foreach (self::templates($directory) as $path => $content) {
                foreach (self::prefixesOf($content, self::ICON_NAMESPACE_URI, self::ICON_NAMESPACE_PHP) as $prefix) {
                    foreach (self::iconArguments($content, $prefix) as [$offset, $identifier]) {
                        $checked++;
                        if (!array_key_exists($identifier, $registered)) {
                            $found[] = sprintf('%s:%d %s', $path, self::lineOf($content, $offset), $identifier);
                        }
                    }
                }
            }
        }
        sort($found);

        $this->assertGreaterThan(
            0,
            $checked,
            'No literal identifier of the icon ViewHelper of academic_base was found. Its namespace or its arguments'
            . ' have changed, adjust this test.',
        );
        $this->assertSame(
            [],
            $found,
            'Register these identifiers in a Configuration/FrontendIcons.php. The frontend icon registry does not'
            . ' read Configuration/Icons.php and renders the placeholder for an identifier it does not know.',
        );
    }

    /**
     * The `*.html` files below the `Resources/Private/` directory of a package, keyed by
     * their path below `packages/fgtclb/`, with the content of every `<f:comment>` blanked.
     * Fluid never renders it, so a ViewHelper there is not used. The line breaks are kept,
     * so a reported line stays right. An HTML comment is kept, because Fluid parses and
     * renders a ViewHelper in it.
     *
     * @return \Generator<string, string>
     */
    private static function templates(string $directory): \Generator
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
        );
        $paths = [];
        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            if ($file->getExtension() === 'html') {
                $paths[] = $file->getPathname();
            }
        }
        sort($paths);
        foreach ($paths as $path) {
            $content = (string)preg_replace_callback(
                '/<f:comment(?:\s[^>]*)?(?<!\/)>.*?<\/f:comment>/s',
                static fn(array $match): string => (string)preg_replace('/[^\n]/', ' ', $match[0]),
                (string)file_get_contents($path),
            );
            yield substr($path, strlen(self::packagesPath()) + 1) => $content;
        }
    }

    /**
     * The prefixes a template binds to a namespace, with `xmlns:` or `{namespace …}`.
     *
     * @return list<string>
     */
    private static function prefixesOf(string $content, string $uri, string $phpNamespace): array
    {
        preg_match_all('/\bxmlns:([\w.-]+)\s*=\s*["\']' . preg_quote($uri, '/') . '["\']/', $content, $declared);
        preg_match_all('/\{namespace\s+([\w.-]+)\s*=\s*\\\\?' . preg_quote($phpNamespace, '/') . '\\\\?\s*\}/', $content, $legacy);
        return array_values(array_unique([...$declared[1], ...$legacy[1]]));
    }

    /**
     * The literal values of the icon arguments of every `<prefix:icon>` tag and every
     * `prefix:icon(…)` call of a template, each as its offset and its value, the name in
     * any letter case. A value with a `{` is built at runtime and left out, so is an empty
     * `overlay` and a value that is not quoted, which Fluid reads as a variable.
     *
     * @return list<array{int, string}>
     */
    private static function iconArguments(string $content, string $prefix): array
    {
        $arguments = [];
        $quoted = preg_quote($prefix, '/');
        preg_match_all('/<' . $quoted . ':(?i:icon)(?=[\s\/>])/', $content, $matches, PREG_OFFSET_CAPTURE);
        foreach ($matches[0] as [$match, $offset]) {
            $start = $offset + strlen($match);
            $tag = substr($content, $start, self::endOf($content, $start, '>') - $start);
            preg_match_all('/([\w:.-]+)\s*=\s*(?:"((?:[^"\\\\]|\\\\.)*)"|\'((?:[^\'\\\\]|\\\\.)*)\')/', $tag, $attributes, PREG_SET_ORDER | PREG_UNMATCHED_AS_NULL);
            foreach ($attributes as $attribute) {
                $value = $attribute[2] ?? $attribute[3] ?? '';
                if (in_array($attribute[1], self::ICON_ARGUMENTS, true) && self::isLiteral($attribute[1], $value)) {
                    $arguments[] = [$offset, $value];
                }
            }
        }
        preg_match_all('/(?<![\w.:-])' . $quoted . ':(?i:icon)\s*\(/', $content, $matches, PREG_OFFSET_CAPTURE);
        foreach ($matches[0] as [$match, $offset]) {
            $start = $offset + strlen($match);
            $call = substr($content, $start, self::endOf($content, $start, ')') - $start);
            foreach (self::topLevelArguments($call) as $argument) {
                // Fluid separates name and value with `:` or `=`. Inside the argument of
                // another ViewHelper the quotes are escaped, and the backslashes are no part
                // of the value.
                if (preg_match('/^\s*(\w+)\s*[:=]\s*(\\\\?)(["\'])(.*?)\2\3\s*$/s', $argument, $parts)
                    && in_array($parts[1], self::ICON_ARGUMENTS, true)
                    && self::isLiteral($parts[1], $parts[4])
                ) {
                    $arguments[] = [$offset, $parts[4]];
                }
            }
        }
        return $arguments;
    }

    /**
     * Whether a value names an icon of the registry. A value with a `{` is built at
     * runtime. An empty `overlay` asks for no overlay, which the icon factory skips.
     */
    private static function isLiteral(string $argument, string $value): bool
    {
        return !str_contains($value, '{') && ($argument !== 'overlay' || $value !== '');
    }

    /**
     * The position of the character that closes what starts at `$start`: the `>` of a tag,
     * or the `)` of an inline call, which counts the parentheses it passes. Quoted text,
     * also with escaped quotes, is skipped, so a `->` chain or a nested call inside an
     * argument value does not end it early.
     */
    private static function endOf(string $content, int $start, string $closing): int
    {
        $depth = 1;
        $quote = null;
        $length = strlen($content);
        for ($position = $start; $position < $length; $position++) {
            $character = $content[$position];
            if ($character === '\\') {
                $position++;
            } elseif ($quote !== null) {
                if ($character === $quote) {
                    $quote = null;
                }
            } elseif ($character === '\'' || $character === '"') {
                $quote = $character;
            } elseif ($closing === ')' && $character === '(') {
                $depth++;
            } elseif ($character === $closing && --$depth === 0) {
                return $position;
            }
        }
        return $length;
    }

    /**
     * The arguments of an inline call, split at the commas outside quotes, braces and
     * parentheses.
     *
     * @return list<string>
     */
    private static function topLevelArguments(string $call): array
    {
        $arguments = [];
        $depth = 0;
        $quote = null;
        $current = '';
        $length = strlen($call);
        for ($position = 0; $position < $length; $position++) {
            $character = $call[$position];
            if ($character === '\\' && $position + 1 < $length) {
                $current .= $character . $call[++$position];
                continue;
            }
            if ($quote !== null) {
                if ($character === $quote) {
                    $quote = null;
                }
            } elseif ($character === '\'' || $character === '"') {
                $quote = $character;
            } elseif ($character === '(' || $character === '{' || $character === '[') {
                $depth++;
            } elseif ($character === ')' || $character === '}' || $character === ']') {
                $depth--;
            } elseif ($character === ',' && $depth === 0) {
                $arguments[] = $current;
                $current = '';
                continue;
            }
            $current .= $character;
        }
        $arguments[] = $current;
        return $arguments;
    }

    /**
     * The identifiers of the frontend icon registry the repository ships: the keys of every
     * `Configuration/FrontendIcons.php`, and `category_types.<group>.<type>` and
     * `category_types_group.<group>` for every type and group of a
     * `Configuration/CategoryTypes.yaml` that declares an `icon` or a `frontendIcon`.
     *
     * @param list<string> $files The `Configuration/FrontendIcons.php` files
     * @return array<string, true>
     */
    private static function frontendIconIdentifiers(array $files): array
    {
        $identifiers = [];
        foreach ($files as $file) {
            $icons = require $file;
            if (!is_array($icons)) {
                throw new \UnexpectedValueException($file . ' does not return an array.', 1791116350);
            }
            foreach (array_keys($icons) as $identifier) {
                $identifiers[(string)$identifier] = true;
            }
        }
        foreach (glob(self::packagesPath() . '/*/Configuration/CategoryTypes.yaml') ?: [] as $file) {
            $configuration = Yaml::parseFile($file);
            foreach (is_array($configuration['groups'] ?? null) ? $configuration['groups'] : [] as $group) {
                if (is_array($group) && self::declaresAnIcon($group)) {
                    $identifiers['category_types_group.' . $group['identifier']] = true;
                }
            }
            foreach (is_array($configuration['types'] ?? null) ? $configuration['types'] : [] as $type) {
                if (is_array($type) && is_string($type['group'] ?? null) && self::declaresAnIcon($type)) {
                    $identifiers['category_types.' . $type['group'] . '.' . $type['identifier']] = true;
                }
            }
        }
        return $identifiers;
    }

    /**
     * @param array<mixed> $declaration
     * @phpstan-assert-if-true array{identifier: string} $declaration
     */
    private static function declaresAnIcon(array $declaration): bool
    {
        return is_string($declaration['identifier'] ?? null)
            && (($declaration['icon'] ?? '') !== '' || ($declaration['frontendIcon'] ?? '') !== '');
    }

    private static function lineOf(string $content, int $offset): int
    {
        return substr_count($content, "\n", 0, $offset) + 1;
    }

    private static function packagesPath(): string
    {
        return dirname(__DIR__, 4) . '/packages/fgtclb';
    }
}
