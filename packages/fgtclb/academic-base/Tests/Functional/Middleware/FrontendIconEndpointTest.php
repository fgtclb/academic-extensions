<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional\Middleware;

use FGTCLB\AcademicBase\Imaging\FrontendIconRenderer;
use FGTCLB\AcademicBase\Middleware\FrontendIconEndpoint;
use FGTCLB\AcademicBase\Tests\Functional\AbstractAcademicBaseTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Routing\SiteRouteResult;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

/**
 * The icon endpoint through the real frontend application: the whole middleware stack
 * of the core version under test, with this extension's middleware where
 * `Configuration/RequestMiddlewares.php` puts it.
 *
 * Two sites: `https://www.acme.com/` with a `/de/` language, and the subfolder
 * installation `https://www.example.com/sub/`. The icons come from the fixture
 * extension `test_frontend_icons`, see {@see \FGTCLB\AcademicBase\Tests\Functional\Imaging\FrontendIconRendererTest}.
 */
final class FrontendIconEndpointTest extends AbstractAcademicBaseTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
    ];

    private const ENDPOINT = 'https://www.acme.com/_academic/icons.json';

    protected array $testExtensionsToLoad = [
        'fgtclb/academic-base',
        'tests/frontend-icons',
    ];

    protected array $configurationToUseInTestInstance = [
        'SYS' => [
            'encryptionKey' => '4408d27a916d51e624b69af3554f516dbab61037a9f7b9fd6f81b4d3bedeccb6',
        ],
        'FE' => [
            'debug' => false,
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Database/FrontendIconEndpointPages.csv');
        foreach ([1, 2] as $rootPageId) {
            $this->setUpFrontendRootPage(
                pageId: $rootPageId,
                typoScriptFiles: ['setup' => ['EXT:academic_base/Tests/Functional/Fixtures/TypoScript/page.typoscript']],
            );
        }
        $this->writeSiteConfiguration(
            identifier: 'acme',
            site: $this->buildSiteConfiguration(rootPageId: 1, base: 'https://www.acme.com/'),
            languages: [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DE', '/de/'),
            ],
        );
        $this->writeSiteConfiguration(
            identifier: 'example',
            site: $this->buildSiteConfiguration(rootPageId: 2, base: 'https://www.example.com/sub/'),
            languages: [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
            ],
        );
    }

    protected function tearDown(): void
    {
        GeneralUtility::rmdir($this->instancePath . '/typo3conf/sites', true);
        parent::tearDown();
    }

    #[Test]
    public function answersTheInlineMarkupOfTheIdentifiersInTheOrderAskedFor(): void
    {
        $identifiers = ['tx-academicbase-info-email', 'tx-academicbase-action-add', 'category_types.none', 'tx-academicbase-state-hidden'];

        $response = $this->request(self::ENDPOINT . '?i=' . implode(',', $identifiers) . '&s=medium');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/json; charset=utf-8', $response->getHeaderLine('Content-Type'));
        $this->assertSame('nosniff', $response->getHeaderLine('X-Content-Type-Options'));
        $map = $this->decode($response);
        $this->assertSame(['tx-academicbase-info-email', 'tx-academicbase-action-add', 'tx-academicbase-state-hidden'], array_keys($map));
        $iconFactory = $this->get(IconFactory::class);
        foreach ($map as $identifier => $markup) {
            $this->assertSame($iconFactory->getIcon($identifier, IconSize::MEDIUM)->render('inline'), $markup);
        }
    }

    #[Test]
    public function answersTheSmallSizeByDefault(): void
    {
        $map = $this->decode($this->request(self::ENDPOINT . '?i=tx-academicbase-action-add'));

        $this->assertStringContainsString('icon-size-small', $map['tx-academicbase-action-add']);
    }

    #[Test]
    public function answersTheOverrideOfAProject(): void
    {
        $map = $this->decode($this->request(self::ENDPOINT . '?i=tx-academicbase-info-phone'));

        $this->assertStringContainsString('M1 1h14v14H1z', $map['tx-academicbase-info-phone']);
    }

    /**
     * @return \Generator<string, array{string}>
     */
    public static function bases(): \Generator
    {
        yield 'the default language of a site' => ['https://www.acme.com/_academic/icons.json'];
        yield 'a language with a base of its own' => ['https://www.acme.com/de/_academic/icons.json'];
        yield 'a site in a subfolder' => ['https://www.example.com/sub/_academic/icons.json'];
        // The site matcher leaves the tail "/_academic/icons.json" for it.
        yield 'a doubled slash after the base' => ['https://www.acme.com//_academic/icons.json'];
    }

    #[DataProvider('bases')]
    #[Test]
    public function answersBelowEveryBase(string $url): void
    {
        $response = $this->request($url . '?i=tx-academicbase-action-add');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['tx-academicbase-action-add'], array_keys($this->decode($response)));
    }

    /**
     * The deprecated identifier is refused without the deprecation being raised, which
     * the suite asserts on its own: it fails on every `E_USER_DEPRECATED`.
     */
    #[Test]
    public function leavesOutWhatMayNotBeServed(): void
    {
        $response = $this->request(self::ENDPOINT
            . '?i=actions-add,default-not-found,tx-academicbase-action-unknown,tx-academictest-action-deprecated,'
            . 'tx-academictest-action-bitmap,tx-testforeign-action-star,tx-academicbase-action-add');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['tx-academicbase-action-add'], array_keys($this->decode($response)));
    }

    #[Test]
    public function answersAnEmptyObjectWhenNothingMayBeServed(): void
    {
        $response = $this->request(self::ENDPOINT . '?i=actions-add');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('{}', (string)$response->getBody());
    }

    #[Test]
    public function answersThirtyTwoIdentifiers(): void
    {
        $identifiers = array_fill(0, FrontendIconEndpoint::MAX_IDENTIFIERS, 'tx-academicbase-action-add');

        $response = $this->request(self::ENDPOINT . '?i=' . implode(',', $identifiers));

        $this->assertSame(200, $response->getStatusCode());
    }

    /**
     * @return \Generator<string, array{string}>
     */
    public static function badRequests(): \Generator
    {
        yield 'no identifier' => [''];
        yield 'an empty identifier list' => ['?i='];
        yield 'an identifier list that is not a string' => ['?i[]=tx-academicbase-action-add'];
        yield 'more than 32 identifiers' => ['?i=' . implode(',', array_fill(0, 33, 'tx-academicbase-action-add'))];
        yield 'an empty identifier in the list' => ['?i=tx-academicbase-action-add,'];
        yield 'an upper case identifier' => ['?i=TX-ACADEMICBASE-ACTION-ADD'];
        yield 'an identifier with a trailing line feed' => ['?i=tx-academicbase-action-add%0A'];
        yield 'a path' => ['?i=' . rawurlencode('../../typo3conf/system/settings.php')];
        yield 'markup' => ['?i=' . rawurlencode('<script>')];
        yield 'an unknown size' => ['?i=tx-academicbase-action-add&s=huge'];
        yield 'the overlay size' => ['?i=tx-academicbase-action-add&s=overlay'];
        yield 'a size that is not a string' => ['?i=tx-academicbase-action-add&s[]=small'];
    }

    #[DataProvider('badRequests')]
    #[Test]
    public function answersABadRequestWith400(string $query): void
    {
        $response = $this->request(self::ENDPOINT . $query);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('no-store', $response->getHeaderLine('Cache-Control'));
        $this->assertArrayHasKey('error', $this->decode($response));
    }

    /**
     * @return \Generator<string, array{string}>
     */
    public static function refusedMethods(): \Generator
    {
        yield 'POST' => ['POST'];
        yield 'PUT' => ['PUT'];
        yield 'DELETE' => ['DELETE'];
        yield 'PATCH' => ['PATCH'];
        yield 'OPTIONS' => ['OPTIONS'];
    }

    #[DataProvider('refusedMethods')]
    #[Test]
    public function answersAnyOtherMethodThanGetAndHeadWith405(string $method): void
    {
        $response = $this->request((new InternalRequest(self::ENDPOINT . '?i=tx-academicbase-action-add'))->withMethod($method));

        $this->assertSame(405, $response->getStatusCode());
        $this->assertSame('GET, HEAD', $response->getHeaderLine('Allow'));
    }

    #[Test]
    public function answersHeadWithTheHeadersOfGetAndNoBody(): void
    {
        $get = $this->request(self::ENDPOINT . '?i=tx-academicbase-action-add');
        $head = $this->request((new InternalRequest(self::ENDPOINT . '?i=tx-academicbase-action-add'))->withMethod('HEAD'));

        $this->assertSame(200, $head->getStatusCode());
        $this->assertSame('', (string)$head->getBody());
        $this->assertSame($get->getHeaderLine('ETag'), $head->getHeaderLine('ETag'));
        $this->assertSame($get->getHeaderLine('Content-Length'), $head->getHeaderLine('Content-Length'));
        $this->assertSame((string)strlen((string)$get->getBody()), $get->getHeaderLine('Content-Length'));
    }

    #[Test]
    public function answersTheCurrentVersionAsImmutable(): void
    {
        $version = $this->get(FrontendIconRenderer::class)->getVersion();

        $response = $this->request(self::ENDPOINT . '?i=tx-academicbase-action-add&v=' . $version);

        $this->assertSame('public, max-age=31536000, immutable', $response->getHeaderLine('Cache-Control'));
        $this->assertFalse($response->hasHeader('Vary'));
    }

    /**
     * @return \Generator<string, array{string}>
     */
    public static function otherVersions(): \Generator
    {
        yield 'no version' => [''];
        yield 'an empty version' => ['&v='];
        yield 'a version that is not the current one' => ['&v=0123456789abcdef'];
        yield 'a version that is not a string' => ['&v[]=0123456789abcdef'];
    }

    #[DataProvider('otherVersions')]
    #[Test]
    public function answersAnyOtherVersionForFiveMinutes(string $version): void
    {
        $response = $this->request(self::ENDPOINT . '?i=tx-academicbase-action-add' . $version);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('public, max-age=300', $response->getHeaderLine('Cache-Control'));
    }

    #[Test]
    public function tagsTheBody(): void
    {
        $add = $this->request(self::ENDPOINT . '?i=tx-academicbase-action-add');
        $again = $this->request(self::ENDPOINT . '?i=tx-academicbase-action-add&v=another');
        $delete = $this->request(self::ENDPOINT . '?i=tx-academicbase-action-delete');

        $this->assertMatchesRegularExpression('/^"[0-9a-f]{16}"$/', $add->getHeaderLine('ETag'));
        $this->assertSame($add->getHeaderLine('ETag'), $again->getHeaderLine('ETag'));
        $this->assertNotSame($add->getHeaderLine('ETag'), $delete->getHeaderLine('ETag'));
    }

    /**
     * @return \Generator<string, array{callable(string): string}>
     */
    public static function matchingValidators(): \Generator
    {
        yield 'the tag' => [static fn(string $etag): string => $etag];
        yield 'the tag as a weak validator' => [static fn(string $etag): string => 'W/' . $etag];
        yield 'the tag in a list' => [static fn(string $etag): string => '"other", ' . $etag];
        yield 'any tag' => [static fn(string $etag): string => '*'];
    }

    /**
     * @param callable(string): string $ifNoneMatch
     */
    #[DataProvider('matchingValidators')]
    #[Test]
    public function answersAMatchingValidatorWith304(callable $ifNoneMatch): void
    {
        $etag = $this->request(self::ENDPOINT . '?i=tx-academicbase-action-add')->getHeaderLine('ETag');

        $response = $this->request(
            (new InternalRequest(self::ENDPOINT . '?i=tx-academicbase-action-add'))->withHeader('If-None-Match', $ifNoneMatch($etag)),
        );

        $this->assertSame(304, $response->getStatusCode());
        $this->assertSame('', (string)$response->getBody());
        $this->assertSame($etag, $response->getHeaderLine('ETag'));
        $this->assertSame('public, max-age=300', $response->getHeaderLine('Cache-Control'));
    }

    /**
     * A revalidation for the current token keeps the year: a 304 without `immutable`
     * would shorten what the cache keeps.
     */
    #[Test]
    public function answersAMatchingValidatorForTheCurrentVersionWith304AndTheYear(): void
    {
        $url = self::ENDPOINT . '?i=tx-academicbase-action-add&v=' . $this->get(FrontendIconRenderer::class)->getVersion();
        $etag = $this->request($url)->getHeaderLine('ETag');

        $response = $this->request((new InternalRequest($url))->withHeader('If-None-Match', $etag));

        $this->assertSame(304, $response->getStatusCode());
        $this->assertSame($etag, $response->getHeaderLine('ETag'));
        $this->assertSame('public, max-age=31536000, immutable', $response->getHeaderLine('Cache-Control'));
    }

    #[Test]
    public function answersAValidatorThatDoesNotMatchWithTheBody(): void
    {
        $response = $this->request(
            (new InternalRequest(self::ENDPOINT . '?i=tx-academicbase-action-add'))->withHeader('If-None-Match', '"0123456789abcdef"'),
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['tx-academicbase-action-add'], array_keys($this->decode($response)));
    }

    /**
     * A logout request that carries a session cookie makes the frontend user
     * authenticator remove the cookie with a `Set-Cookie` header. The page proves the
     * request provokes one; the endpoint, placed before the authenticators, must not
     * send it.
     */
    #[Test]
    public function neverStartsASessionOrSendsACookie(): void
    {
        $page = $this->request(
            (new InternalRequest('https://www.acme.com/?logintype=logout'))->withCookieParams(['fe_typo_user' => 'a-session-cookie']),
        );
        $endpoint = $this->request(
            (new InternalRequest(self::ENDPOINT . '?i=tx-academicbase-action-add&logintype=logout'))->withCookieParams(['fe_typo_user' => 'a-session-cookie']),
        );

        $this->assertSame(200, $page->getStatusCode());
        $this->assertTrue($page->hasHeader('Set-Cookie'), 'The probe does not provoke a cookie; the assertion below proves nothing.');
        $this->assertSame(200, $endpoint->getStatusCode());
        $this->assertFalse($endpoint->hasHeader('Set-Cookie'));
    }

    #[Test]
    public function passesAPageRequestOn(): void
    {
        $response = $this->request('https://www.acme.com/');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('The page was rendered.', (string)$response->getBody());
        $this->assertFalse($response->hasHeader('ETag'));
    }

    /**
     * @return \Generator<string, array{string}>
     */
    public static function otherPaths(): \Generator
    {
        yield 'another file in the same directory' => ['https://www.acme.com/_academic/other.json?i=tx-academicbase-action-add'];
        yield 'the path below a page' => ['https://www.acme.com/some-page/_academic/icons.json?i=tx-academicbase-action-add'];
        yield 'the path with a suffix' => ['https://www.acme.com/_academic/icons.json/more?i=tx-academicbase-action-add'];
        yield 'a host without a site' => ['https://www.unknown.example/_academic/icons.json?i=tx-academicbase-action-add'];
    }

    #[DataProvider('otherPaths')]
    #[Test]
    public function passesAnyOtherPathOn(string $url): void
    {
        $response = $this->request($url);

        $this->assertNotSame(200, $response->getStatusCode());
        $this->assertFalse($response->hasHeader('ETag'));
    }

    /**
     * A request without a site gets an empty route tail from the site matcher of either
     * core version, so this case cannot be produced through the stack. The middleware
     * must not rely on that, so it is called directly here.
     */
    #[Test]
    public function passesTheEndpointPathOutsideASiteOn(): void
    {
        $uri = new Uri(self::ENDPOINT);
        $request = (new ServerRequest($uri))
            ->withQueryParams(['i' => 'tx-academicbase-action-add'])
            ->withAttribute('site', new NullSite())
            ->withAttribute('routing', new SiteRouteResult($uri, new NullSite(), null, '_academic/icons.json'));

        $response = $this->get(FrontendIconEndpoint::class)->process($request, $this->handlerAnswering(404));

        $this->assertSame(404, $response->getStatusCode());
    }

    private function handlerAnswering(int $status): RequestHandlerInterface
    {
        return new class ($status) implements RequestHandlerInterface {
            public function __construct(private readonly int $status) {}

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(null, $this->status);
            }
        };
    }

    private function request(string|InternalRequest $request): ResponseInterface
    {
        return $this->executeFrontendSubRequest(is_string($request) ? new InternalRequest($request) : $request);
    }

    /**
     * @return array<string, string>
     */
    private function decode(ResponseInterface $response): array
    {
        $decoded = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($decoded);

        return $decoded;
    }
}
