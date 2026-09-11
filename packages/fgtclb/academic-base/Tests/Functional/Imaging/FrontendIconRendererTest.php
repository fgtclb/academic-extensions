<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional\Imaging;

use FGTCLB\AcademicBase\Imaging\FrontendIconRenderer;
use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;
use FGTCLB\AcademicBase\Tests\Functional\AbstractAcademicBaseTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Imaging\IconSize;

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
}
