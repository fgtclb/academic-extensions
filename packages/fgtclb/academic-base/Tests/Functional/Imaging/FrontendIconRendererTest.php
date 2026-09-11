<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional\Imaging;

use FGTCLB\AcademicBase\Imaging\FrontendIconRenderer;
use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;
use FGTCLB\AcademicBase\Tests\Functional\AbstractAcademicBaseTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Package\Cache\PackageDependentCacheIdentifier;

/**
 * Which icons may leave the server for a frontend page, and what markup they leave it
 * with. The fixture extension `test_frontend_icons` overrides a shared icon the way a
 * project does, registers icons that must be refused for each reason there is, and
 * listens to the allow-list event.
 *
 * A deprecated identifier is refused without the deprecation being raised. That is
 * asserted by the suite itself: it fails on every `E_USER_DEPRECATED`.
 */
final class FrontendIconRendererTest extends AbstractAcademicBaseTestCase
{
    protected array $testExtensionsToLoad = [
        'fgtclb/academic-base',
        'tests/frontend-icons',
    ];

    #[Test]
    public function rendersTheInlineMarkupOfEachIdentifierInTheOrderAskedFor(): void
    {
        $identifiers = ['tx-academicbase-info-email', 'tx-academicbase-action-add', 'tx-academicbase-state-hidden'];

        $map = $this->get(FrontendIconRenderer::class)->render($identifiers, IconSize::MEDIUM);

        $this->assertSame($identifiers, array_keys($map));
        $iconFactory = $this->get(IconFactory::class);
        foreach ($identifiers as $identifier) {
            $this->assertSame($iconFactory->getIcon($identifier, IconSize::MEDIUM)->render('inline'), $map[$identifier]);
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

    #[Test]
    public function rendersTheOverrideOfAProject(): void
    {
        $map = $this->get(FrontendIconRenderer::class)->render(['tx-academicbase-info-phone']);

        $this->assertArrayHasKey('tx-academicbase-info-phone', $map);
        $this->assertStringContainsString('M1 1h14v14H1z', $map['tx-academicbase-info-phone']);
    }

    #[Test]
    public function rendersACategoryTypeIconRegisteredWithAnInliningProvider(): void
    {
        $this->get(IconRegistry::class)->registerIcon(
            'category_types.test.type',
            CurrentColorSvgIconProvider::class,
            ['source' => 'EXT:test_frontend_icons/Resources/Public/Icons/star.svg'],
        );

        $this->assertSame(
            ['category_types.test.type'],
            array_keys($this->get(FrontendIconRenderer::class)->render(['category_types.test.type'])),
        );
    }

    #[Test]
    public function rendersAnIconOfTheCoreSvgProviderInlined(): void
    {
        $map = $this->get(FrontendIconRenderer::class)->render(['tx-academictest-action-core-svg']);

        $this->assertArrayHasKey('tx-academictest-action-core-svg', $map);
        $this->assertStringContainsString('<svg', $map['tx-academictest-action-core-svg']);
        $this->assertStringNotContainsString('<img', $map['tx-academictest-action-core-svg']);
    }

    #[Test]
    public function servesAPrefixAListenerAdds(): void
    {
        $renderer = $this->get(FrontendIconRenderer::class);

        $this->assertTrue($renderer->isServable('tx-testproject-action-star'));
        $this->assertSame(['tx-testproject-action-star'], array_keys($renderer->render(['tx-testproject-action-star'])));
    }

    /**
     * @return \Generator<string, array{string}>
     */
    public static function refusedIdentifiers(): \Generator
    {
        yield 'a core icon' => ['actions-add'];
        yield 'the core placeholder' => ['default-not-found'];
        yield 'registered, but no allowed prefix' => ['tx-testforeign-action-star'];
        yield 'allowed prefix, but not registered' => ['tx-academicbase-action-unknown'];
        yield 'allowed prefix, but deprecated' => ['tx-academictest-action-deprecated'];
        yield 'allowed prefix, but a bitmap provider' => ['tx-academictest-action-bitmap'];
        yield 'allowed prefix, but the sprite provider of the core icons' => ['tx-academictest-action-sprite'];
        yield 'upper case' => ['TX-ACADEMICBASE-ACTION-ADD'];
        yield 'a path' => ['tx-academicbase/../action-add'];
        yield 'empty' => [''];
        yield 'allowed prefix and registered, but longer than 100 characters' => ['tx-academictest-action-' . str_repeat('x', 80)];
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

    #[Test]
    public function versionIsStable(): void
    {
        $renderer = $this->get(FrontendIconRenderer::class);

        $this->assertMatchesRegularExpression('/^[0-9a-f]{16}$/', $renderer->getVersion());
        $this->assertSame($renderer->getVersion(), $renderer->getVersion());
    }

    #[Test]
    public function versionChangesWhenARegistrationIsReplaced(): void
    {
        $renderer = $this->get(FrontendIconRenderer::class);
        $before = $renderer->getVersion();

        $this->get(IconRegistry::class)->registerIcon(
            'tx-academicbase-info-phone',
            CurrentColorSvgIconProvider::class,
            ['source' => 'EXT:test_frontend_icons/Resources/Public/Icons/star.svg'],
        );

        $this->assertNotSame($before, $renderer->getVersion());
    }

    #[Test]
    public function versionChangesWhenASourceFileChangesOnDisk(): void
    {
        $path = $this->instancePath . '/typo3temp/frontend-icon-renderer-test.svg';
        file_put_contents($path, '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><path d="M0 0h1v1H0z"/></svg>');
        touch($path, 1700000000);
        clearstatcache(true, $path);
        $this->get(IconRegistry::class)->registerIcon(
            'tx-academictest-action-on-disk',
            CurrentColorSvgIconProvider::class,
            ['source' => $path],
        );
        $renderer = $this->get(FrontendIconRenderer::class);
        $before = $renderer->getVersion();

        touch($path, 1700000100);
        clearstatcache(true, $path);

        $this->assertNotSame($before, $renderer->getVersion());
    }

    #[Test]
    public function versionIgnoresAnIconThatIsNotServed(): void
    {
        $renderer = $this->get(FrontendIconRenderer::class);
        $before = $renderer->getVersion();

        $this->get(IconRegistry::class)->registerIcon(
            'tx-testforeign-action-other',
            CurrentColorSvgIconProvider::class,
            ['source' => 'EXT:test_frontend_icons/Resources/Public/Icons/star.svg'],
        );

        $this->assertSame($before, $renderer->getVersion());
    }

    /**
     * The package dependent cache identifier changes with `composer.lock`, and so with
     * an update that changes a provider class but no registration and no source file.
     * A second renderer with a different identifier stands in for that deployment.
     */
    #[Test]
    public function versionChangesWithThePackageDependentCacheIdentifier(): void
    {
        $identifier = $this->get(PackageDependentCacheIdentifier::class);
        $rendererWith = fn(PackageDependentCacheIdentifier $identifier): FrontendIconRenderer => new FrontendIconRenderer(
            $this->get(IconFactory::class),
            $this->get(IconRegistry::class),
            $this->get(EventDispatcherInterface::class),
            $identifier,
        );

        $this->assertSame($this->get(FrontendIconRenderer::class)->getVersion(), $rendererWith($identifier)->getVersion());
        $this->assertNotSame(
            $rendererWith($identifier)->getVersion(),
            $rendererWith($identifier->withAdditionalHashedIdentifier('another deployment'))->getVersion(),
        );
    }
}
