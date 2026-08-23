<?php

declare(strict_types=1);

namespace FGTCLB\AcademicsDevSite\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Configuration\SiteWriter;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;

/**
 * The two page trees of the seed render the same page.
 *
 * This is the reason the `/legacy/` tree is a mirror rather than a smoke test.
 * The `/` tree is delivered through site sets, the `/legacy/` tree through the
 * `include_static_file` of a root `sys_template` record and the
 * `tsconfig_includes` of its root page - and the second mechanism fails
 * silently. Both columns are comma separated lists read with `trimExplode`, an
 * entry that resolves to nothing contributes nothing, and the page still
 * answers 200 with a piece of its configuration missing. No assertion on one
 * tree can see that; only the other tree can.
 *
 * `DeliveryRegistrationTest` checks the entries. This checks the outcome: every
 * mirrored page, in both languages, has to come out of the two trees as the same
 * markup once the things that legitimately differ are normalised away.
 *
 * WHAT IS SUBSTITUTED, AND WHY IT IS SUBSTITUTED ON BOTH SIDES
 *
 * The `/` tree of a development instance is themed by EXT:bootstrap_package and
 * the `/legacy/` tree cannot be, because that extension delivers through site
 * sets only from version 16 on. Comparing the two trees as they stand in an
 * instance would therefore compare two themes. So the theme is replaced here -
 * on both sides, by the same text: the `/` site gets the site set
 * `fgtclb/academics-dev-site-page-object` where its committed configuration
 * names `bootstrap-package/full`, and the legacy tree keeps the static template
 * of the same package that its `sys_template` record already names. Everything
 * academic is delivered exactly as the seed declares it, through both
 * mechanisms, which is what the comparison is about.
 *
 * The site configuration of the `/` tree is read from the committed
 * `core-NN/config/sites/academics/` rather than written here, so a set added
 * there is part of this test the day it is added.
 */
final class LegacyDeliveryTest extends AbstractSeedTestCase
{
    private const BASE = 'https://academics.test';

    private const LEGACY_SEGMENT = '/legacy';

    /**
     * The uid offset of the mirror, as `ScenarioLegacy.yaml` declares it and
     * `Build/Scripts/generateLegacyScenario.php` writes it.
     */
    private const PAGE_OFFSET = 1000;

    /**
     * The profile detail page of the seed, which hosts the detail plugin and
     * nothing else.
     */
    private const PROFILE_DETAIL_PAGE = 205;

    /**
     * The strings the two sites are *supposed* to disagree about, as
     * `search => replacement`. Filled while the site configurations are written,
     * because that is where two of them come from.
     *
     * @var array<string, string>
     */
    private array $siteTitles = [];

    /**
     * The titles of the two root pages and of their German variants, which are
     * two site roots and say so. Read from the seed rather than written down.
     *
     * @var array<string, string>
     */
    private array $rootTitles = [];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = [
            'SYS' => [
                'encryptionKey' => '2ce9b1a02b0e3aca2b64b1b0d0b39cbbcbe4e2df4bd9a0d0eb31e4c4c1e11b31',
                'features' => [
                    // Without it the frontend answers a rendered error page for a
                    // plugin that threw, and a page that failed on both sides
                    // would compare equal.
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
    public function bothTreesDeliverTheSameMarkupForEveryMirroredPage(): void
    {
        $this->importSeed();
        $this->writeSiteConfigurations();

        $differences = [];
        $rendered = 0;
        foreach ($this->mirroredPages() as $uid => $page) {
            foreach ($page['paths'] as $path) {
                $isRoot = $uid === 1;
                $original = $this->render($path, $isRoot);
                $mirror = $this->render(self::LEGACY_SEGMENT . $path, $isRoot);
                $rendered++;

                if ($original['status'] !== $mirror['status']) {
                    $differences[] = sprintf(
                        'page %d ("%s"): %d in "/" and %d in "/legacy/"',
                        $uid,
                        $path,
                        $original['status'],
                        $mirror['status'],
                    );
                    continue;
                }
                if ($original['body'] !== $mirror['body']) {
                    $differences[] = sprintf(
                        'page %d ("%s"): the markup differs%s',
                        $uid,
                        $path,
                        $this->firstDifferingLine($original['body'], $mirror['body']),
                    );
                }
            }
        }

        $this->assertGreaterThan(100, $rendered, 'The mirror lost pages: too few were rendered to be the seed.');
        $this->assertSame(
            [],
            $differences,
            sprintf(
                "%d of %d rendered pages differ between the two delivery mechanisms:\n  %s\n\n"
                . 'The site set driven tree and the "sys_template" driven tree are supposed to be two ways of'
                . ' saying the same thing. A difference here is usually an entry of "include_static_file" or'
                . ' "tsconfig_includes" that resolves to nothing - which raises nothing at runtime.',
                count($differences),
                $rendered,
                implode("\n  ", $differences),
            ),
        );
    }

    /**
     * Every page answers what it is configured to answer, in both trees.
     *
     * The equality check above passes for a page that fails identically on both
     * sides, and it should: it compares delivery mechanisms, and a plugin that
     * throws does so through both. This is the assertion that says the pages
     * render at all, and it is separate so that the two failures read
     * differently.
     *
     * `200`, except for the two pages the seed puts behind a frontend user
     * group, which answer `403` to a visitor who is not logged in - that is
     * what those pages are in the seed for - and the profile detail page, which
     * answers `404` on TYPO3 v14 for the reason given at {@see self::mirroredPages()}.
     */
    #[Test]
    public function everyMirroredPageAnswersItsExpectedStatusInBothTrees(): void
    {
        $this->importSeed();
        $this->writeSiteConfigurations();

        $failures = [];
        foreach ($this->mirroredPages() as $uid => $page) {
            foreach ($page['paths'] as $path) {
                foreach ([$path, self::LEGACY_SEGMENT . $path] as $requested) {
                    $response = $this->render($requested);
                    if ($response['status'] !== $page['status']) {
                        $failures[] = sprintf(
                            'page %d, "%s": %d, expected %d',
                            $uid,
                            $requested,
                            $response['status'],
                            $page['status'],
                        );
                    }
                }
            }
        }

        $this->assertSame([], $failures, "Pages of the seed do not render:\n  " . implode("\n  ", $failures));
    }

    /**
     * The pages of the `/` tree that have a counterpart in `/legacy/`, with the
     * path of every language they exist in and the status they owe a visitor.
     *
     * Read from the database rather than listed: the mirror is generated, and a
     * page added to `Scenario.yaml` is part of this test as soon as the
     * generator has run. A page of the `/` tree whose mirrored uid does not
     * exist is skipped - the nine storage folders below `/data` are exactly that
     * case, they are deliberately not mirrored and they render nothing anyway.
     *
     * The German path is the *translated* slug: the seed translates them
     * (`/persons` becomes `/personal`), and with `fallbackType: strict` the
     * untranslated one is a 404 rather than the English page.
     *
     * @return array<int, array{paths: list<string>, status: int}>
     */
    private function mirroredPages(): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();

        /** @var list<array<string, mixed>> $rows */
        $rows = $queryBuilder
            ->select('uid', 'pid', 'slug', 'fe_group', 'hidden', 'sys_language_uid', 'l10n_parent')
            ->from('pages')
            ->executeQuery()
            ->fetchAllAssociative();

        $default = [];
        $translated = [];
        foreach ($rows as $row) {
            if ((int)$row['hidden'] === 1) {
                continue;
            }
            if ((int)$row['sys_language_uid'] === 0) {
                $default[(int)$row['uid']] = $row;
                continue;
            }
            $translated[(int)$row['l10n_parent']] = $row;
        }

        $pages = [];
        foreach ($default as $uid => $row) {
            if ($uid >= self::PAGE_OFFSET || !isset($default[$uid + self::PAGE_OFFSET])) {
                continue;
            }

            // A "/" slug is the root page and is requested as "/", not as "".
            $paths = [(string)$row['slug']];
            if (isset($translated[$uid])) {
                $paths[] = '/de' . rtrim((string)$translated[$uid]['slug'], '/');
            }

            $pages[$uid] = [
                'paths' => $paths,
                // A page behind a frontend user group answers 403 to a visitor
                // who is not logged in, and the seed has two of them on purpose.
                'status' => trim((string)$row['fe_group'], ' ,0') !== '' ? 403 : 200,
            ];

            // The profile detail page, asked without a profile. The plugin
            // answers `ErrorController::pageNotFoundAction()` by design in that
            // case - ProfileController::detailAction() opens with exactly that -
            // and the two cores do different things with the response it
            // returns: on TYPO3 v13 the page still comes out as 200, on v14 the
            // 404 reaches the response. Same seed, same extension, two cores, so
            // this is stated rather than asserted away.
            if ($uid === self::PROFILE_DETAIL_PAGE && (new Typo3Version())->getMajorVersion() >= 14) {
                $pages[$uid]['status'] = 404;
            }
        }

        return $pages;
    }

    /**
     * @return array{status: int, body: string}
     */
    private function render(string $path, bool $isRoot = false): array
    {
        // A language base is appended to the site base, so the mirror of
        // "/de/persons" is "/legacy/de/persons" and not "/de/legacy/persons".
        $url = self::BASE . ($path === '' ? '/' : $path);
        $response = $this->executeFrontendSubRequest(new InternalRequest($url), new InternalRequestContext());

        return [
            'status' => $response->getStatusCode(),
            'body' => $this->normalise((string)$response->getBody(), $isRoot),
        ];
    }

    /**
     * What the two trees are allowed to differ in.
     *
     * Every entry here is something that differs because the mirror *is* a
     * mirror, and nothing else is touched - a normalisation that removed more
     * would remove the difference the test exists to find.
     */
    private function normalise(string $markup, bool $isRoot): string
    {
        // The path segment that makes the mirror a second site. Every link,
        // canonical URL and form action of the legacy tree carries it.
        $markup = str_replace(self::LEGACY_SEGMENT . '/', '/', $markup);

        // The "websiteTitle" of the two site configurations, which the page
        // title API puts in front of every page title. Two sites have two
        // titles by definition; this is not something the delivery decides.
        // Longest first, so a title that contains another is masked whole.
        $markup = str_replace(array_keys($this->siteTitles), array_values($this->siteTitles), $markup);

        // The nonce of the Content Security Policy, drawn per request.
        $markup = (string)preg_replace('#nonce="[^"]*"#', 'nonce="*"', $markup);

        // The request identifier an error page carries, which is per request as
        // well. The two pages behind a frontend user group answer with one.
        $markup = (string)preg_replace('#(typo3-error-page-requestid">Request: )[0-9a-f]+#', '$1*', $markup);

        // The request token of the login form, a JWT signed over a nonce that is
        // drawn per request - the same reason as the two above.
        $markup = (string)preg_replace('#(name="__RequestToken" value=")[^"]*"#', '$1*"', $markup);

        // The cHash of a link, which is computed over the arguments of that link
        // and therefore differs wherever a mirrored uid is one of them. Masked
        // before the uids themselves, because a cHash may begin with digits and
        // the rule below would take those for a uid and leave the rest standing.
        $markup = (string)preg_replace('#cHash=[0-9a-f]+#', 'cHash=*', $markup);

        // Any uid in a query string - the "tx_...[record]=17" arguments of the
        // detail links. The records of the four page bound tables are mirrored
        // with an offset of their own, and the shared ones are not mirrored at
        // all, so this masks both without having to know which is which.
        $markup = (string)preg_replace('#(=|%5D=)\d+#', '$1*', $markup);

        // An attribute whose value ends in a number, with nothing numeric in
        // front of it: "id=\"c1003\"" of a content element, "id=\"partner-1342\""
        // of the partner map, "data-study-plan=\"1031\"" of the study plan. Every
        // one of them is a record uid, and a mirrored record carries the uid of
        // its original plus a fixed offset.
        //
        // Written as one rule rather than as a list of the three attributes that
        // do it today: a list would have to be extended by whoever adds the
        // fourth, and they would find out through a failure that looks like a
        // delivery difference and is not one. It masks a few numbers that are
        // not uids - an image width, a viewport scale - and those are equal on
        // both sides anyway, so nothing that could differ is hidden by it.
        $markup = (string)preg_replace('#="([^"\d]*)\d+"#', '="$1*"', $markup);

        // The two root pages are two site roots and carry the titles that say so
        // - "Academic extensions" against "Academic extensions (legacy
        // delivery)". Masked for that one page pair only, so the same words
        // elsewhere in the tree still have to match.
        if ($isRoot) {
            $markup = str_replace(array_keys($this->rootTitles), array_values($this->rootTitles), $markup);
        }

        return trim($markup);
    }

    /**
     * The first line the two differ in, as a hint for the failure message.
     *
     * A full diff of two rendered pages is unreadable in a phpunit message; the
     * first differing line names the element and is enough to find the rest.
     */
    private function firstDifferingLine(string $expected, string $actual): string
    {
        $expectedLines = explode("\n", $expected);
        $actualLines = explode("\n", $actual);
        $count = max(count($expectedLines), count($actualLines));
        for ($line = 0; $line < $count; $line++) {
            if (($expectedLines[$line] ?? null) !== ($actualLines[$line] ?? null)) {
                return sprintf(
                    "\n      \"/\":       %s\n      \"/legacy/\": %s",
                    trim(substr($expectedLines[$line] ?? '<missing>', 0, 160)),
                    trim(substr($actualLines[$line] ?? '<missing>', 0, 160)),
                );
            }
        }

        return '';
    }

    /**
     * Both site configurations, built from the committed ones of the instance of
     * the running core version.
     *
     * The dependency list of the `/` site is taken from that file with the theme
     * swapped out, and its settings are copied verbatim: what the site set
     * driven tree is configured with has to be what the instance is configured
     * with, or this test would prove something about a configuration nobody
     * runs.
     */
    private function writeSiteConfigurations(): void
    {
        $instance = sprintf('%s/core-%d/config/sites', dirname(__DIR__, 4), (new Typo3Version())->getMajorVersion());

        $academics = $this->committedSite($instance . '/academics/config.yaml');
        $academics['base'] = self::BASE . '/';
        $academics['dependencies'] = array_map(
            static fn(string $set): string => $set === 'bootstrap-package/full'
                ? 'fgtclb/academics-dev-site-page-object'
                : $set,
            $academics['dependencies'] ?? [],
        );
        $this->writeSite('academics', $academics, $instance . '/academics/settings.yaml');

        $legacy = $this->committedSite($instance . '/academics-legacy/config.yaml');
        $legacy['base'] = self::BASE . self::LEGACY_SEGMENT . '/';
        $this->writeSite('academics-legacy', $legacy, null);

        $this->siteTitles = $this->maskLongestFirst([
            (string)($academics['websiteTitle'] ?? ''),
            (string)($legacy['websiteTitle'] ?? ''),
        ], '<websiteTitle>');
        $this->rootTitles = $this->maskLongestFirst(
            array_values($this->pageTitles([1, 501, 1001, 1501])),
            '<rootTitle>',
        );
    }

    /**
     * @param list<string> $strings
     * @return array<string, string>
     */
    private function maskLongestFirst(array $strings, string $replacement): array
    {
        $strings = array_values(array_filter(array_unique($strings), static fn(string $s): bool => $s !== ''));
        usort($strings, static fn(string $a, string $b): int => strlen($b) <=> strlen($a));

        $map = [];
        foreach ($strings as $string) {
            $map[$string] = $replacement;
        }

        return $map;
    }

    /**
     * @param list<int> $uids
     * @return array<int, string>
     */
    private function pageTitles(array $uids): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();

        /** @var list<array<string, mixed>> $rows */
        $rows = $queryBuilder
            ->select('uid', 'title')
            ->from('pages')
            ->where($queryBuilder->expr()->in('uid', $queryBuilder->quoteArrayBasedValueListToIntegerList($uids)))
            ->executeQuery()
            ->fetchAllAssociative();

        $titles = [];
        foreach ($rows as $row) {
            $titles[(int)$row['uid']] = (string)$row['title'];
        }

        return $titles;
    }

    /**
     * @return array<string, mixed>
     */
    private function committedSite(string $file): array
    {
        $this->assertFileExists($file);
        /** @var array<string, mixed> $configuration */
        $configuration = Yaml::parseFile($file);

        // Taken as it stands, including its "imports" of the route enhancers and
        // its "fallbackType: strict". The language bases are relative in the
        // committed file already, so only the site base has to be rewritten, and
        // the caller does that.
        return $configuration;
    }

    /**
     * Written through `SiteWriter` rather than into a directory of this test's
     * choosing: the writer knows where the installation reads site
     * configurations from and flushes the caches that would otherwise answer
     * with the site list of a moment ago.
     *
     * @param array<string, mixed> $configuration
     */
    private function writeSite(string $identifier, array $configuration, ?string $settingsFile): void
    {
        $writer = $this->get(SiteWriter::class);
        $writer->write($identifier, $configuration);

        if ($settingsFile !== null && is_file($settingsFile)) {
            /** @var array<string, mixed> $settings */
            $settings = Yaml::parseFile($settingsFile);
            $writer->writeSettings($identifier, $settings);
        }
    }
}
