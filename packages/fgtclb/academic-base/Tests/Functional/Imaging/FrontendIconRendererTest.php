<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional\Imaging;

use FGTCLB\AcademicBase\Imaging\FrontendIconFactory;
use FGTCLB\AcademicBase\Imaging\FrontendIconRenderer;
use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;
use FGTCLB\AcademicBase\Tests\Functional\AbstractAcademicBaseTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Package\PackageManager;

/**
 * Which icons may leave the server for a frontend page, and what markup they leave it
 * with. The icons are those of the frontend icon registry of academic_base: the shared
 * set, and the fixture extension `test_frontend_icon_api`, which replaces a shared icon
 * the way a site package does and registers one icon per reason to serve or refuse it.
 */
final class FrontendIconRendererTest extends AbstractAcademicBaseTestCase
{
    protected array $testExtensionsToLoad = [
        'fgtclb/environment-state-manager',
        'fgtclb/academic-base',
        'tests/frontend-icon-api',
    ];

    #[Test]
    public function rendersTheInlineMarkupOfEachIdentifierInTheOrderAskedFor(): void
    {
        $identifiers = ['tx-academicbase-info-email', 'tx-academicbase-action-add', 'tx-academicbase-state-hidden'];

        $map = $this->get(FrontendIconRenderer::class)->render($identifiers, IconSize::MEDIUM);

        $this->assertSame($identifiers, array_keys($map));
        $frontendIconFactory = $this->get(FrontendIconFactory::class);
        foreach ($identifiers as $identifier) {
            $this->assertSame($frontendIconFactory->getIcon($identifier, IconSize::MEDIUM)->render('inline'), $map[$identifier]);
            $this->assertStringContainsString('data-identifier="' . $identifier . '"', $map[$identifier]);
            $this->assertStringContainsString('icon-size-medium', $map[$identifier]);
            $this->assertStringContainsString('<svg', $map[$identifier]);
        }
    }

    #[Test]
    public function rendersEachIdentifierOnce(): void
    {
        $map = $this->get(FrontendIconRenderer::class)->render([
            'tx-academicbase-action-add',
            'tx-academicbase-action-add',
        ]);

        $this->assertSame(['tx-academicbase-action-add'], array_keys($map));
    }

    /**
     * The replacement is read after the shared icon only when the fixture loads after
     * academic_base, which a TYPO3 v14 test instance decides by the extension key.
     */
    #[Test]
    public function theFixtureLoadsAfterAcademicBase(): void
    {
        $packageKeys = array_keys($this->get(PackageManager::class)->getActivePackages());

        $this->assertGreaterThan(
            array_search('academic_base', $packageKeys, true),
            array_search('test_frontend_icon_api', $packageKeys, true),
        );
    }

    #[Test]
    public function rendersTheReplacementOfASitePackage(): void
    {
        $map = $this->get(FrontendIconRenderer::class)->render(['tx-academicbase-info-phone']);

        $this->assertArrayHasKey('tx-academicbase-info-phone', $map);
        $this->assertStringContainsString('M1 1h14v14H1z', $map['tx-academicbase-info-phone']);
    }

    /**
     * @return \Generator<string, array{string}>
     */
    public static function categoryTypeIdentifiers(): \Generator
    {
        yield 'a category type' => ['category_types.test.type'];
        yield 'a category group' => ['category_types_group.test'];
    }

    #[DataProvider('categoryTypeIdentifiers')]
    #[Test]
    public function rendersTheIdentifierShapesOfCategoryTypes(string $identifier): void
    {
        $this->assertSame([$identifier], array_keys($this->get(FrontendIconRenderer::class)->render([$identifier])));
    }

    #[Test]
    public function rendersAnIconOfTheCoreSvgProviderInlined(): void
    {
        $map = $this->get(FrontendIconRenderer::class)->render(['test-frontend-icon-api-core-svg']);

        $this->assertArrayHasKey('test-frontend-icon-api-core-svg', $map);
        $this->assertStringContainsString('<svg', $map['test-frontend-icon-api-core-svg']);
        $this->assertStringNotContainsString('<img', $map['test-frontend-icon-api-core-svg']);
    }

    /**
     * @return \Generator<string, array{string}>
     */
    public static function refusedIdentifiers(): \Generator
    {
        yield 'a core icon, which only the backend registry knows' => ['actions-add'];
        yield 'the placeholder of the frontend registry' => ['default-not-found'];
        yield 'not registered' => ['tx-academicbase-action-unknown'];
        yield 'a bitmap provider' => ['test-frontend-icon-api-bitmap'];
        yield 'the sprite provider' => ['test-frontend-icon-api-sprite'];
        yield 'registered, but longer than 100 characters' => ['test-frontend-icon-api-' . str_repeat('x', 80)];
        yield 'upper case' => ['TX-ACADEMICBASE-ACTION-ADD'];
        yield 'a trailing line feed' => ["tx-academicbase-action-add\n"];
        yield 'a path' => ['tx-academicbase/../action-add'];
        yield 'empty' => [''];
    }

    #[DataProvider('refusedIdentifiers')]
    #[Test]
    public function refusesAnIdentifierThatMayNotBeServed(string $identifier): void
    {
        $renderer = $this->get(FrontendIconRenderer::class);

        $this->assertFalse($renderer->isServable($identifier));
        // Left out of the map, and the identifier next to it is still answered.
        $this->assertSame(
            ['tx-academicbase-action-add'],
            array_keys($renderer->render([$identifier, 'tx-academicbase-action-add'])),
        );
    }

    /**
     * The icon registry of TYPO3 knows `actions-add`, whose sprite provider would be
     * refused anyway. An icon registered there with an inlining provider is refused
     * all the same: the frontend registry is the only source.
     */
    #[Test]
    public function neverServesAnIconOfTheBackendRegistry(): void
    {
        $iconRegistry = $this->get(IconRegistry::class);
        $iconRegistry->registerIcon(
            'tx-academicbase-test-backend-only',
            CurrentColorSvgIconProvider::class,
            ['source' => 'EXT:test_frontend_icon_api/Resources/Public/Icons/star.svg'],
        );

        $this->assertTrue($iconRegistry->isRegistered('tx-academicbase-test-backend-only'));
        $this->assertSame([], $this->get(FrontendIconRenderer::class)->render(['tx-academicbase-test-backend-only']));
    }
}
