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
 * package, a shared SCSS partial and one per extension, and a selector of a
 * class that nothing renders styles nothing. Neither PHP suite nor jsdom
 * computes a style, so a template that renames a class leaves its rule behind
 * without a failing test - which is how the study plan stylesheet came to
 * select the classes of its markup before ACE-818. The extensions cannot check
 * this themselves any more: their tests are split out with the package and
 * cannot read `packages-dev/`.
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
 * no part. The page object renders the title and the content elements of a
 * page and nothing else, so the page templates of programs, projects and
 * partners, which refine the page object of the theme, render no page here.
 * Their classes count through their templates only, as those of the views of
 * B-ITE do, and the pages of the three page types are rendered only where a
 * content element on them adds a plugin.
 *
 * Covered is every partial but the one of the profile editor. The shared
 * partial is checked against all extensions it styles together, the partner
 * partial against the partner views and the stylesheets of Leaflet. The
 * partial of the profile editor is not: the editor needs a logged in owner,
 * the partial also corrects classes of the theme, and it styles the widget
 * CropperJS builds and the transition classes the editor derives from a
 * prefix, none of which a template or a module names as written.
 */
final class StylesheetClassesTest extends AbstractSeedTestCase
{
    use CommittedSiteTrait;

    private const BASE = 'https://academics.test';

    private const PARTIALS = __DIR__ . '/../../Resources/Private/Scss/frontend/';

    /**
     * The root elements of the plugins the shared partial styles. The public
     * profile and the study plan are among them for the layout of the
     * "/legacy/" tree only. The roots of the page templates are not, because
     * no page template renders here, see above.
     */
    private const SHARED_ROOT_CLASSES = [
        'academic-bite-jobs-list',
        'academic-contacts4pages-list',
        'academic-jobs-detail',
        'academic-jobs-list',
        'academic-jobs-new',
        'academic-partners-list',
        'academic-partners-map',
        'academic-partnerships-list',
        'academic-partnerships-teaser',
        'academic-persons-card',
        'academic-persons-contracts',
        'academic-persons-detail',
        'academic-persons-list',
        'academic-persons-profiles',
        'academic-programs-detail',
        'academic-programs-finder',
        'academic-programs-list',
        'academic-projects-list',
        'academic-study-plan',
    ];

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
                ['EXT:academic_study_plan/Resources/Private/'],
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
                ['EXT:academic_persons/Resources/Private/'],
                ['EXT:academic_persons/Resources/Public/JavaScript/frontend/profile.js'],
            ),
            'The public profile partial selects classes the detail view neither renders nor writes.',
        );
    }

    /**
     * The four list views of profiles and the contacts of a page share one
     * partial, since the contacts render the item markup of the profile lists.
     * The list is rendered twice, as the plain list and as the one with the
     * letter navigation, so the navigation is part of it.
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
                    '/contacts/list',
                    '/contacts/list-hidden',
                ]),
                [
                    'academic-persons-list',
                    'academic-persons-card',
                    'academic-persons-profiles',
                    'academic-persons-contracts',
                    'academic-contacts4pages-list',
                ],
                ['EXT:academic_persons/Resources/Private/', 'EXT:academic_contacts4pages/Resources/Private/'],
                [],
            ),
            'The profile list partial selects classes the profile lists neither render nor write.',
        );
    }

    /**
     * The shared partial styles what the lists, pages and forms of several
     * extensions have in common, so it is checked against all of them together.
     */
    #[Test]
    public function everyClassTheSharedPartialSelectsIsRenderedOrWrittenByAnExtension(): void
    {
        $this->importSeed();
        $this->writeAcademicsSite(self::BASE . '/');

        $this->assertSame(
            [],
            $this->unrenderedClasses(
                '_academic-base.scss',
                array_map($this->render(...), [
                    '/persons/list',
                    '/persons/list-alphabet',
                    '/persons/card',
                    '/persons/selected-profiles',
                    '/persons/selected-contracts',
                    '/contacts/list',
                    '/jobs/list',
                    '/jobs/detail/web-developer-typo3-web-vision-gmbh',
                    '/jobs/new',
                    '/jobs/b-ite',
                    '/programs/list',
                    '/programs/finder',
                    '/programs/bachelor-of-engineering',
                    '/projects/list',
                    '/partners/list',
                    '/partners/map',
                    '/partners/partnerships',
                    '/partners/partnerships-teaser',
                    '/persons/detail/jane-doe',
                    '/study-plan',
                ]),
                self::SHARED_ROOT_CLASSES,
                [
                    'EXT:academic_base/Resources/Private/',
                    'EXT:academic_bite_jobs/Resources/Private/',
                    'EXT:academic_contacts4pages/Resources/Private/',
                    'EXT:academic_jobs/Resources/Private/',
                    'EXT:academic_partners/Resources/Private/',
                    'EXT:academic_persons/Resources/Private/',
                    'EXT:academic_programs/Resources/Private/',
                    'EXT:academic_projects/Resources/Private/',
                    'EXT:academic_study_plan/Resources/Private/',
                ],
                [
                    'EXT:academic_persons/Resources/Public/JavaScript/frontend/profile.js',
                    'EXT:academic_programs/Resources/Public/JavaScript/frontend/program-finder.js',
                    'EXT:academic_programs/Resources/Public/JavaScript/frontend/program-list.js',
                    'EXT:academic_study_plan/Resources/Public/JavaScript/frontend/academic-study-plan.js',
                ],
            ),
            'The shared partial selects classes no extension renders or writes.',
        );
    }

    /**
     * Jobs and the job offers of B-ITE share one partial. The seed has no offer
     * of B-ITE, so their list renders its empty state only, and the classes of
     * its card, list and table views count through its templates. The form for
     * a new job is built from the form partials of academic_base.
     */
    #[Test]
    public function everyClassTheJobsPartialSelectsIsRenderedOrWritten(): void
    {
        $this->importSeed();
        $this->writeAcademicsSite(self::BASE . '/');

        $this->assertSame(
            [],
            $this->unrenderedClasses(
                '_academic-jobs.scss',
                array_map($this->render(...), [
                    '/jobs/list',
                    '/jobs/list-theses',
                    '/jobs/detail/web-developer-typo3-web-vision-gmbh',
                    '/jobs/new',
                    '/jobs/b-ite',
                ]),
                ['academic-jobs-list', 'academic-jobs-detail', 'academic-jobs-new', 'academic-bite-jobs-list'],
                [
                    'EXT:academic_jobs/Resources/Private/',
                    'EXT:academic_bite_jobs/Resources/Private/',
                    'EXT:academic_base/Resources/Private/',
                ],
                ['EXT:academic_jobs/Resources/Public/JavaScript/frontend/rich-text.js'],
            ),
            'The jobs partial selects classes the job views neither render nor write.',
        );
    }

    /**
     * The two program pages are rendered for the details element they carry.
     * Their page template renders no page here, so its classes count through
     * the templates.
     */
    #[Test]
    public function everyClassTheProgramsPartialSelectsIsRenderedOrWrittenByItsModules(): void
    {
        $this->importSeed();
        $this->writeAcademicsSite(self::BASE . '/');

        $this->assertSame(
            [],
            $this->unrenderedClasses(
                '_academic-programs.scss',
                array_map($this->render(...), [
                    '/programs/list',
                    '/programs/list-preset',
                    '/programs/finder',
                    '/programs/bachelor-of-engineering',
                    '/programs/master-of-science',
                ]),
                ['academic-programs-list', 'academic-programs-finder', 'academic-programs-detail'],
                ['EXT:academic_programs/Resources/Private/', 'EXT:academic_base/Resources/Private/'],
                [
                    'EXT:academic_programs/Resources/Public/JavaScript/frontend/program-finder.js',
                    'EXT:academic_programs/Resources/Public/JavaScript/frontend/program-list.js',
                ],
            ),
            'The programs partial selects classes the program views neither render nor write.',
        );
    }

    /**
     * The page template of a project renders no page here, so its classes count
     * through the templates, and no project page is rendered.
     */
    #[Test]
    public function everyClassTheProjectsPartialSelectsIsRendered(): void
    {
        $this->importSeed();
        $this->writeAcademicsSite(self::BASE . '/');

        $this->assertSame(
            [],
            $this->unrenderedClasses(
                '_academic-projects.scss',
                array_map($this->render(...), [
                    '/projects/list',
                    '/projects/list-active',
                    '/projects/selected',
                ]),
                ['academic-projects-list'],
                ['EXT:academic_projects/Resources/Private/', 'EXT:academic_base/Resources/Private/'],
                [],
            ),
            'The projects partial selects classes the project views neither render nor write.',
        );
    }

    /**
     * The partner partial styles two kinds of markup. The views of the
     * extension carry its own classes, and the map renders no class the partial
     * selects: everything below the map element is built by Leaflet. So every
     * class of the partial has to be one the views render, or one the
     * stylesheets of the libraries select, which the map page links. The element
     * the partial sizes by its id has to be on the map page. The page template of
     * a partner renders no page here, so its classes count through the templates.
     */
    #[Test]
    public function everyClassThePartnersPartialSelectsIsRenderedOrAClassOfTheMapLibraries(): void
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

        $missing = $this->unrenderedClasses(
            '_academic-partners.scss',
            [
                $page,
                ...array_map($this->render(...), [
                    '/partners/list',
                    '/partners/list-preset',
                    '/partners/partnerships',
                    '/partners/partnerships-teaser',
                ]),
            ],
            [
                'academic-partners-list',
                'academic-partners-map',
                'academic-partnerships-list',
                'academic-partnerships-teaser',
            ],
            ['EXT:academic_partners/Resources/Private/', 'EXT:academic_base/Resources/Private/'],
            ['EXT:academic_partners/Resources/Public/JavaScript/frontend/map.js'],
        );
        // The map template builds its layout class from the setting, and the
        // seed has no map set to full width. The class counts while the template
        // still builds it and the setting still offers the value.
        $template = (string)file_get_contents(GeneralUtility::getFileAbsFileName(
            'EXT:academic_partners/Resources/Private/Templates/Partner/Map.html',
        ));
        $settings = (string)file_get_contents(GeneralUtility::getFileAbsFileName(
            'EXT:academic_partners/Configuration/FlexForms/MapSettings.xml',
        ));
        $this->assertStringContainsString('value="layout-{settings.map.layout}"', $template);
        $this->assertStringContainsString('<value>fullWidth</value>', $settings);

        $this->assertSame(
            [],
            array_values(array_diff($missing, $libraryClasses, ['layout-fullWidth'])),
            'The partner partial selects classes neither the partner views nor the map libraries know.',
        );
    }

    /**
     * The classes a partial selects that neither the root elements of the
     * extension on the given pages and their descendants carry, nor a template
     * below the given directory names, nor the given compiled modules write.
     *
     * @param list<string> $pages
     * @param list<string> $rootClasses
     * @param list<string> $templates
     * @param list<string> $modules
     * @return list<string>
     */
    private function unrenderedClasses(string $partial, array $pages, array $rootClasses, array $templates, array $modules): array
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
        foreach ($templates as $directory) {
            $rendered = [...$rendered, ...$this->classesNamedByTemplatesBelow($directory)];
        }

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
