<?php

declare(strict_types=1);

namespace FGTCLB\AcademicsMonorepoShared\Tests\Unit;

use FGTCLB\AcademicBase\Security\InlineEventHandlerMutation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\HashValue;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationCollection;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Scope;
use TYPO3\CMS\Core\Type\Map;

/**
 * Every inline event handler a template of an extension renders is allowed by the
 * Content Security Policy of TYPO3, through the `Configuration/ContentSecurityPolicies.php`
 * of the same extension (ACE-924).
 *
 * A nonce does not cover an event handler attribute, so the frontend policy TYPO3 ships
 * blocks every one of them once a site enforces it. Nothing fails visibly: the select of a
 * filter form simply stops submitting the form, and the browser reports the violation in
 * its console. What allows a handler is `'unsafe-hashes'` and the sha256 hash of its exact
 * text in `script-src-attr`, for the generic frontend scope, so every site that inherits
 * the default policy gets it. The extensions declare it with the mutation of academic_base
 * for inline event handlers, which its listener recognises on a site whose policy allows
 * inline scripts already, so only that mutation counts here.
 *
 * Found are an `on…` attribute of an HTML tag or of a ViewHelper, which Fluid passes on to
 * the tag, and an `on…` key of a Fluid array, as `additionalAttributes` takes it. The
 * name is matched in any letter case, as HTML does, so an argument of a ViewHelper whose
 * name starts with `on` is reported as well. A Fluid array is found in both notations, as
 * an attribute value and as an argument of an inline call. A handler whose text is built
 * at runtime cannot be allowed by a hash and is reported as such.
 */
final class InlineEventHandlerTest extends TestCase
{
    /**
     * Packages whose inline event handlers are not allowed, by directory name, with the
     * number of handlers their templates render and the reason.
     *
     * The profile editor of `academic_persons_edit` on this branch opens its confirmation
     * dialogs and submits a checkbox through inline handlers, most of them built from a
     * variable, which no hash can allow. It also loads CKEditor 4 from `cdn.ckeditor.com`,
     * which the frontend policy of TYPO3 blocks as well, so allowing the handlers would not
     * make the editor work under an enforced policy. The editor of 3.0 has none of them.
     * An entry is accepted only while its package renders exactly that number of
     * handlers, so a new handler there, and the last one going, do not pass unnoticed.
     */
    private const NOT_ALLOWED = [
        'academic-persons-edit' => [
            'handlers' => 8,
            'reason' => 'The profile editor of 2.x is left out of ACE-924, it loads CKEditor 4 from a CDN'
                . ' that the frontend policy of TYPO3 blocks anyway.',
        ],
    ];

    /**
     * @return \Generator<string, array{string}>
     */
    public static function packageDirectoriesDataProvider(): \Generator
    {
        $directories = glob(self::packagesPath() . '/*', GLOB_ONLYDIR) ?: [];
        sort($directories);
        foreach ($directories as $directory) {
            if (is_dir($directory . '/Resources/Private') || is_file(self::policyFile($directory))) {
                yield basename($directory) => [$directory];
            }
        }
    }

    #[DataProvider('packageDirectoriesDataProvider')]
    #[Test]
    public function everyInlineEventHandlerIsAllowedByTheContentSecurityPolicyOfItsExtension(string $directory): void
    {
        $handlers = self::handlersOf($directory);
        if (array_key_exists(basename($directory), self::NOT_ALLOWED)) {
            $this->assertCount(
                self::NOT_ALLOWED[basename($directory)]['handlers'],
                $handlers,
                sprintf(
                    'The templates of %s render another number of inline event handlers than NOT_ALLOWED names.'
                    . ' Allow a new one by hash instead, and remove the entry once none is left.',
                    basename($directory),
                ),
            );
            return;
        }
        if ($handlers === []) {
            $this->addToAssertionCount(1);
            return;
        }
        $allowed = self::allowedHashesOf($directory);

        $found = [];
        foreach ($handlers as [$location, $name, $text]) {
            if ($text === null) {
                $found[] = sprintf('%s %s: built at runtime, no hash can allow it', $location, $name);
            } elseif (!array_key_exists((string)HashValue::hash($text), $allowed)) {
                $found[] = sprintf('%s %s="%s" needs %s', $location, $name, $text, HashValue::hash($text));
            }
        }

        $this->assertSame(
            [],
            $found,
            'Allow every inline event handler in Configuration/ContentSecurityPolicies.php of the extension: an'
            . ' InlineEventHandlerMutation of academic_base with the exact handler text, for the frontend scope.'
            . ' Better still, use one handler text the extension allows already.',
        );
    }

    /**
     * A hash that no handler needs any more widens the policy of every site for nothing.
     */
    #[DataProvider('packageDirectoriesDataProvider')]
    #[Test]
    public function everyAllowedHashBelongsToAnInlineEventHandlerOfItsExtension(string $directory): void
    {
        $needed = [];
        foreach (self::handlersOf($directory) as [, , $text]) {
            if ($text !== null) {
                $needed[(string)HashValue::hash($text)] = true;
            }
        }

        $this->assertSame(
            [],
            array_keys(array_diff_key(self::allowedHashesOf($directory), $needed)),
            'Remove these hashes from Configuration/ContentSecurityPolicies.php, no template of the extension'
            . ' renders a handler with that text any more.',
        );
    }

    /**
     * @return \Generator<string, array{string, list<array{string, string|null}>}>
     */
    public static function templateDataProvider(): \Generator
    {
        yield 'attribute of a tag' => [
            '<select name="a" onchange="this.form.submit()">',
            [['onchange', 'this.form.submit()']],
        ];
        yield 'attribute of a ViewHelper, single quotes, other letter case' => [
            "<f:form.select property=\"a\"\n    onChange='this.form.submit()' />",
            [['onChange', 'this.form.submit()']],
        ];
        yield 'attribute of a tag with a character reference' => [
            '<button onclick="return confirm(&quot;Sure?&quot;)">',
            [['onclick', 'return confirm("Sure?")']],
        ];
        yield 'attribute of a ViewHelper with a character reference, which Fluid encodes again' => [
            '<f:form.button onclick="return confirm(&quot;Sure?&quot;)">',
            [['onclick', 'return confirm(&quot;Sure?&quot;)']],
        ];
        yield 'unquoted attribute' => [
            '<select name="a" onchange=this.form.submit()>',
            [['onchange', 'this.form.submit()']],
        ];
        yield 'attribute built at runtime' => [
            '<input onchange="{element.onChange}">',
            [['onchange', null]],
        ];
        yield 'quoted key of additionalAttributes' => [
            '<f:form.select additionalAttributes="{\'onchange\': \'this.form.submit()\'}" />',
            [['onchange', 'this.form.submit()']],
        ];
        yield 'unquoted key after another key, escaped quotes of an inline call' => [
            '{f:form.select(additionalAttributes: {class: \'a\', onchange: \\\'this.form.submit()\\\'})}',
            [['onchange', 'this.form.submit()']],
        ];
        yield 'key as the first argument of an inline call' => [
            '{f:form.select(onchange: \'this.form.submit()\', property: \'a\')}',
            [['onchange', 'this.form.submit()']],
        ];
        yield 'data attribute and argument names that only contain "on"' => [
            '<div data-onchange="x" condition="{a}" action="list" option="b"></div>',
            [],
        ];
        yield 'Fluid comment' => [
            "<f:comment>\n    <select onchange=\"this.form.submit()\">\n</f:comment>",
            [],
        ];
    }

    /**
     * @param list<array{string, string|null}> $expected
     */
    #[DataProvider('templateDataProvider')]
    #[Test]
    public function handlersAreFoundInATemplate(string $template, array $expected): void
    {
        $this->assertSame(
            $expected,
            array_map(
                static fn(array $handler): array => [$handler[1], $handler[2]],
                self::handlersIn(self::withoutFluidComments($template)),
            ),
        );
    }

    /**
     * The inline event handlers of the templates below `Resources/Private/` of a package,
     * each as its location, its attribute name and its text, the text `null` where it is
     * built at runtime.
     *
     * @return list<array{string, string, string|null}>
     */
    private static function handlersOf(string $directory): array
    {
        $handlers = [];
        foreach (self::templates($directory . '/Resources/Private') as $path => $content) {
            foreach (self::handlersIn($content) as [$offset, $name, $text]) {
                $handlers[] = [sprintf('%s:%d', $path, self::lineOf($content, $offset)), $name, $text];
            }
        }
        return $handlers;
    }

    /**
     * The handlers of one template, each as its offset, its attribute name and its text,
     * the text as the browser hashes it. A character reference in an attribute of a plain
     * HTML tag is decoded, as the browser decodes it. In an attribute of a ViewHelper it is
     * not: Fluid takes the value as it is written and encodes it again when it renders the
     * tag, so the browser reads the reference itself. The value of a Fluid array is a
     * Fluid string, so its escaping backslashes are no part of it.
     *
     * @return list<array{int, string, string|null}>
     */
    private static function handlersIn(string $content): array
    {
        $handlers = [];
        preg_match_all(
            '/(?<=\s)(on[a-z]+)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>"\']+))/i',
            $content,
            $attributes,
            PREG_SET_ORDER | PREG_OFFSET_CAPTURE | PREG_UNMATCHED_AS_NULL,
        );
        foreach ($attributes as $attribute) {
            $value = $attribute[2][0] ?? $attribute[3][0] ?? $attribute[4][0] ?? '';
            $offset = (int)$attribute[0][1];
            if (!str_contains($value, '{') && !self::isInViewHelperTag($content, $offset)) {
                $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5);
            }
            $handlers[$offset] = [
                $offset,
                (string)$attribute[1][0],
                str_contains($value, '{') ? null : $value,
            ];
        }
        preg_match_all(
            '/(?<=[{,(])\s*([\'"]?)(on[a-z]+)\1\s*[:=]\s*(\\\\?)([\'"])(.*?)\3\4/is',
            $content,
            $keys,
            PREG_SET_ORDER | PREG_OFFSET_CAPTURE,
        );
        foreach ($keys as $key) {
            $value = (string)$key[5][0];
            $handlers[(int)$key[2][1]] = [
                (int)$key[2][1],
                (string)$key[2][0],
                str_contains($value, '{') ? null : stripslashes($value),
            ];
        }
        ksort($handlers);
        return array_values($handlers);
    }

    /**
     * Whether the attribute at the offset belongs to the tag of a ViewHelper, a tag whose
     * name carries a namespace prefix.
     */
    private static function isInViewHelperTag(string $content, int $offset): bool
    {
        $start = strrpos(substr($content, 0, $offset), '<');
        return $start !== false && preg_match('/^<[\w.-]+:[\w.]+/', substr($content, $start)) === 1;
    }

    /**
     * The sha256 hashes the `Configuration/ContentSecurityPolicies.php` of a package allows
     * for event handler attributes on every site: those of an inline event handler mutation
     * of academic_base for the generic frontend scope. That mutation extends `script-src-attr`
     * by `'unsafe-hashes'` and the hashes, and its listener recognises it by its class.
     *
     * @return array<string, true>
     */
    private static function allowedHashesOf(string $directory): array
    {
        $file = self::policyFile($directory);
        if (!is_file($file)) {
            return [];
        }
        $policies = require $file;
        if (!$policies instanceof Map) {
            throw new \UnexpectedValueException($file . ' does not return a ' . Map::class . '.', 1792152001);
        }

        $hashes = [];
        foreach ($policies as $scope => $collection) {
            if ($scope !== Scope::frontend()) {
                continue;
            }
            if (!$collection instanceof MutationCollection) {
                throw new \UnexpectedValueException($file . ' maps a scope to something else than a mutation collection.', 1792152002);
            }
            foreach ($collection->mutations as $mutation) {
                if (!$mutation instanceof InlineEventHandlerMutation) {
                    continue;
                }
                foreach ($mutation->sources as $source) {
                    if ($source instanceof HashValue) {
                        $hashes[(string)$source] = true;
                    }
                }
            }
        }
        return $hashes;
    }

    /**
     * The `*.html` files below a directory, keyed by their path below `packages/fgtclb/`,
     * with the content of every `<f:comment>` blanked, which Fluid never renders. The line
     * breaks are kept, so a reported line stays right.
     *
     * @return \Generator<string, string>
     */
    private static function templates(string $directory): \Generator
    {
        if (!is_dir($directory)) {
            return;
        }
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
            yield substr($path, strlen(self::packagesPath()) + 1) => self::withoutFluidComments((string)file_get_contents($path));
        }
    }

    private static function withoutFluidComments(string $content): string
    {
        return (string)preg_replace_callback(
            '/<f:comment(?:\s[^>]*)?(?<!\/)>.*?<\/f:comment>/s',
            static fn(array $match): string => (string)preg_replace('/[^\n]/', ' ', $match[0]),
            $content,
        );
    }

    private static function policyFile(string $directory): string
    {
        return $directory . '/Configuration/ContentSecurityPolicies.php';
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
