<?php

declare(strict_types=1);

namespace FGTCLB\AcademicsDevSite\Tests\Functional;

use FGTCLB\AcademicsDevSite\Tests\Functional\Support\CommittedSiteTrait;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;

/**
 * Every class the stylesheet of the development instances selects for an
 * extension is one that extension puts on the page.
 *
 * The extensions ship no stylesheet (ACE-890). Their markup is styled by this
 * package, one SCSS partial per extension, and a selector of a class that
 * nothing renders styles nothing. Neither PHP suite nor jsdom computes a style,
 * so a template that renames a class leaves its rule behind without a failing
 * test - which is how the study plan stylesheet came to select the classes of
 * its markup before ACE-818. The extensions cannot check this themselves any
 * more: their tests are split out with the package and cannot read
 * `packages-dev/`.
 *
 * The classes are read from the partial of the extension rather than from the
 * compiled stylesheet, because the compiled file does not say which partial a
 * rule came from. The partials concatenate no class names with `&`, so every
 * class a selector names stands in the source as written. The compiled file is
 * kept in step with them by `checkJsBuildClean`.
 *
 * A selected class counts as rendered when one of three holds:
 *
 * - the seed renders it inside the element of the extension, which covers the
 *   classes a ViewHelper writes, the icon wrappers above all;
 * - a template of the extension names it literally in a `class` attribute,
 *   because the seed does not fill every element - no study plan module of it
 *   carries a note, and no timeline entry of a profile a text;
 * - the compiled module of the extension writes it, quoted, as the classes of a
 *   state are.
 *
 * The pages are those of the `/` tree with the theme swapped for the page
 * object, as `LegacyDeliveryTest` renders them, so the classes of a theme play
 * no part.
 *
 * Covered are the study plan, the public profile, the profile lists and the
 * partner map, the last against the stylesheets of Leaflet. The partial of the
 * profile editor is not: the editor needs a logged in owner, the partial also
 * corrects classes of the theme, and it styles the widget CropperJS builds and
 * the transition classes the editor derives from a prefix, none of which a
 * template or a module names as written.
 */
final class StylesheetClassesTest extends AbstractSeedTestCase
{
    use CommittedSiteTrait;

    private const BASE = 'https://academics.test';

    private const PARTIALS = __DIR__ . '/../../Resources/Private/Scss/frontend/';

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = [
            'SYS' => [
                'encryptionKey' => '2ce9b1a02b0e3aca2b64b1b0d0b39cbbcbe4e2df4bd9a0d0eb31e4c4c1e11b31',
                'features' => [
                    'subrequestPageErrors' => true,
                ],
            ],
            'FE' => ['debug' => false],
        ];
        parent::setUp();
    }

    protected function tearDown(): void
    {
        GeneralUtility::rmdir($this->instancePath . '/config/sites', true);
        GeneralUtility::rmdir($this->instancePath . '/typo3conf/sites', true);
        parent::tearDown();
    }

    #[Test]
    public function everyClassTheStudyPlanPartialSelectsIsRenderedOrWrittenByItsModule(): void
    {
        $this->importSeed();
        $this->writeAcademicsSite(self::BASE . '/');

        $this->assertSame(
            [],
            $this->unrenderedClasses(
                '_academic-study-plan.scss',
                [$this->render('/study-plan')],
                ['academic-study-plan'],
                'EXT:academic_study_plan/Resources/Private/',
                ['EXT:academic_study_plan/Resources/Public/JavaScript/frontend/academic-study-plan.js'],
            ),
            'The study plan partial selects classes the study plan neither renders nor writes.',
        );
    }

    /**
     * Every profile the persons list links is rendered, because no single profile
     * of the seed fills every element of the detail view.
     */
    #[Test]
    public function everyClassThePublicProfilePartialSelectsIsRenderedOrWrittenByItsModule(): void
    {
        $this->importSeed();
        $this->writeAcademicsSite(self::BASE . '/');

        preg_match_all('#href="(/persons/detail/[^"?]+)"#', $this->render('/persons/list'), $matches);
        $paths = array_values(array_unique($matches[1]));
        $this->assertNotSame([], $paths, 'The persons list of the seed links no profile.');

        $this->assertSame(
            [],
            $this->unrenderedClasses(
                '_academic-persons.scss',
                array_map($this->render(...), $paths),
                ['academic-persons-detail'],
                'EXT:academic_persons/Resources/Private/',
                ['EXT:academic_persons/Resources/Public/JavaScript/frontend/profile.js'],
            ),
            'The public profile partial selects classes the detail view neither renders nor writes.',
        );
    }

    /**
     * The four list views of profiles share one partial. The list is rendered
     * twice, as the plain list and as the one with the letter navigation, so the
     * navigation is part of it.
     */
    #[Test]
    public function everyClassTheProfileListPartialSelectsIsRenderedOrWritten(): void
    {
        $this->importSeed();
        $this->writeAcademicsSite(self::BASE . '/');

        $this->assertSame(
            [],
            $this->unrenderedClasses(
                '_academic-persons-list.scss',
                array_map($this->render(...), [
                    '/persons/list',
                    '/persons/list-alphabet',
                    '/persons/card',
                    '/persons/selected-profiles',
                    '/persons/selected-contracts',
                ]),
                ['academic-persons-list', 'academic-persons-card', 'academic-persons-profiles', 'academic-persons-contracts'],
                'EXT:academic_persons/Resources/Private/',
                [],
            ),
            'The profile list partial selects classes the profile lists neither render nor write.',
        );
    }

    /**
     * The map renders no class the partial selects: everything below the map
     * element is built by Leaflet. So every class of the partial has to be one the
     * stylesheets of the libraries select, which the map page links, and the
     * element the partial sizes by its id has to be on the page.
     */
    #[Test]
    public function everyClassThePartnerMapPartialSelectsIsAClassOfTheMapLibraries(): void
    {
        $this->importSeed();
        $this->writeAcademicsSite(self::BASE . '/');

        $page = $this->render('/partners/map');
        $this->assertStringContainsString('id="map"', $page);

        $libraryClasses = [];
        preg_match_all('#<link\b[^>]*\bhref="([^"?]*/JavaScript/vendor/[^"?]+\.css)#', $page, $links);
        $this->assertCount(3, $links[1], 'The map page does not link the three stylesheets of its libraries.');
        foreach ($links[1] as $href) {
            $file = GeneralUtility::getFileAbsFileName(
                'EXT:academic_partners/Resources/Public/JavaScript/vendor/'
                . substr($href, (int)strpos($href, '/JavaScript/vendor/') + strlen('/JavaScript/vendor/')),
            );
            $this->assertFileExists($file);
            $libraryClasses = [...$libraryClasses, ...$this->classesSelectedBy((string)file_get_contents($file))];
        }

        $selected = $this->classesSelectedBy((string)file_get_contents(self::PARTIALS . '_academic-partners.scss'));
        $this->assertNotSame([], $selected);
        $this->assertSame(
            [],
            array_values(array_diff($selected, $libraryClasses)),
            'The partner map partial selects classes the stylesheets of the map libraries do not know.',
        );
    }

    /**
     * The classes a partial selects that neither the root elements of the
     * extension on the given pages and their descendants carry, nor a template
     * below the given directory names, nor the given compiled modules write.
     *
     * @param list<string> $pages
     * @param list<string> $rootClasses
     * @param list<string> $modules
     * @return list<string>
     */
    private function unrenderedClasses(string $partial, array $pages, array $rootClasses, string $templates, array $modules): array
    {
        $selected = $this->classesSelectedBy((string)file_get_contents(self::PARTIALS . $partial));
        $this->assertNotSame([], $selected, sprintf('No class found in "%s".', $partial));

        $rendered = [];
        foreach ($pages as $page) {
            foreach ($rootClasses as $rootClass) {
                $rendered = [...$rendered, ...$this->classesRenderedBelow($page, $rootClass)];
            }
        }
        $this->assertNotSame([], $rendered, sprintf('No element with the class "%s" was rendered.', implode('", "', $rootClasses)));
        $rendered = [...$rendered, ...$this->classesNamedByTemplatesBelow($templates)];

        $script = '';
        foreach ($modules as $module) {
            $file = GeneralUtility::getFileAbsFileName($module);
            $this->assertFileExists($file);
            $script .= (string)file_get_contents($file);
        }

        $missing = [];
        foreach (array_diff($selected, $rendered) as $class) {
            if (preg_match('#["\']' . preg_quote($class, '#') . '["\']#', $script) !== 1) {
                $missing[] = $class;
            }
        }

        return $missing;
    }

    /**
     * Every class name a Fluid file below the directory writes literally into a
     * `class` attribute. A token of a Fluid expression in the attribute is not a
     * class name and is left out.
     *
     * @return list<string>
     */
    private function classesNamedByTemplatesBelow(string $directory): array
    {
        $path = GeneralUtility::getFileAbsFileName($directory);
        $this->assertDirectoryExists($path);

        $classes = [];
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            if (!$file instanceof \SplFileInfo || $file->getExtension() !== 'html') {
                continue;
            }
            preg_match_all('#\bclass="([^"]*)"#', (string)file_get_contents($file->getPathname()), $attributes);
            foreach ($attributes[1] as $attribute) {
                foreach (preg_split('#\s+#', $attribute, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $token) {
                    if (preg_match('#^-?[_a-zA-Z][_a-zA-Z0-9-]*$#', $token) === 1) {
                        $classes[] = $token;
                    }
                }
            }
        }
        $this->assertNotSame([], $classes, sprintf('No template below "%s" names a class.', $directory));

        return array_values(array_unique($classes));
    }

    /**
     * Every class of every element with the root class, and of everything below it.
     *
     * @return list<string>
     */
    private function classesRenderedBelow(string $html, string $rootClass): array
    {
        $document = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8">' . $html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $nodes = (new \DOMXPath($document))->query(sprintf(
            '//*[contains(concat(" ", normalize-space(@class), " "), " %s ")]/descendant-or-self::*[@class]',
            $rootClass,
        ));
        $this->assertInstanceOf(\DOMNodeList::class, $nodes);

        $classes = [];
        foreach ($nodes as $node) {
            $this->assertInstanceOf(\DOMElement::class, $node);
            $classes = [
                ...$classes,
                ...(preg_split('#\s+#', trim($node->getAttribute('class')), -1, PREG_SPLIT_NO_EMPTY) ?: []),
            ];
        }

        return array_values(array_unique($classes));
    }

    /**
     * Every class name the selectors of a stylesheet name, once. Works on SCSS as
     * well as on CSS: comments of both kinds are dropped, and every prelude of a
     * block that is not an at-rule is a selector list, nested or not.
     *
     * @return list<string>
     */
    private function classesSelectedBy(string $stylesheet): array
    {
        $stylesheet = (string)preg_replace('#/\*.*?\*/#s', '', $stylesheet);
        $stylesheet = (string)preg_replace('#(?<![:"\'])//[^\n]*#', '', $stylesheet);
        preg_match_all('#([^{};]+)\{#', $stylesheet, $preludes);

        $classes = [];
        foreach ($preludes[1] as $prelude) {
            if (str_starts_with(trim($prelude), '@')) {
                continue;
            }
            preg_match_all('#\.(-?[_a-zA-Z][_a-zA-Z0-9-]*)#', $prelude, $names);
            $classes = [...$classes, ...$names[1]];
        }

        return array_values(array_unique($classes));
    }

    private function render(string $path): string
    {
        $response = $this->executeFrontendSubRequest(
            new InternalRequest(self::BASE . $path),
            new InternalRequestContext(),
        );
        $this->assertSame(200, $response->getStatusCode(), sprintf('"%s" did not render.', $path));

        return (string)$response->getBody();
    }
}
