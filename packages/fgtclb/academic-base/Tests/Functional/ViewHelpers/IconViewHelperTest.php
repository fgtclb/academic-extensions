<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional\ViewHelpers;

use FGTCLB\AcademicBase\Tests\Functional\AbstractAcademicBaseTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;

/**
 * `ab:icon` renders an icon of the frontend registry with the markup `core:icon`
 * renders for the backend registry. The fixture extension `tests/frontend-icons`
 * registers `test-frontend-both` identically in both files, and each template of
 * `Identity/` renders it once through each view helper, separated by a line `===`.
 * Each case is a template of its own, so a title `core:icon` leaves on its runtime
 * cached icon cannot reach another case.
 */
final class IconViewHelperTest extends AbstractAcademicBaseTestCase
{
    protected array $testExtensionsToLoad = [
        'fgtclb/environment-state-manager',
        'fgtclb/academic-base',
        'tests/frontend-icons',
    ];

    public static function identityCasesDataProvider(): \Generator
    {
        yield 'defaults' => ['Identity/Default'];
        yield 'size medium' => ['Identity/Medium'];
        yield 'state disabled' => ['Identity/Disabled'];
        yield 'inline markup' => ['Identity/Inline'];
        yield 'title' => ['Identity/Title'];
        yield 'overlay' => ['Identity/Overlay'];
    }

    #[DataProvider('identityCasesDataProvider')]
    #[Test]
    public function rendersWhatCoreIconRendersForTheSameRegistration(string $template): void
    {
        [$frontend, $backend] = $this->renderParts($template);

        $this->assertStringContainsString('data-identifier="test-frontend-both"', $frontend);
        $this->assertSame($backend, $frontend);
    }

    /**
     * The wrapper written out once, so a change of core's `Icon::wrappedIcon()`, which
     * site stylesheets select, is visible here before it reaches a site.
     */
    #[Test]
    public function theWrapperIsTheMarkupOfCore(): void
    {
        [$frontend] = $this->renderParts('Identity/Default');

        $this->assertStringStartsWith(
            '<span class="t3js-icon icon icon-size-small icon-state-default icon-test-frontend-both"'
            . ' data-identifier="test-frontend-both" aria-hidden="true">' . "\n"
            . "\t" . '<span class="icon-markup">' . "\n"
            . '<img src="',
            $frontend,
        );
        $this->assertStringEndsWith("\n\t</span>\n\t\n</span>", $frontend);
    }

    #[Test]
    public function inlineMarkupHoldsTheFile(): void
    {
        [$frontend] = $this->renderParts('Identity/Inline');

        $this->assertStringContainsString('<span class="icon-markup">' . "\n" . '<svg', $frontend);
        $this->assertStringNotContainsString('<img', $frontend);
    }

    #[Test]
    public function anIconOfTheBackendRegistryRendersThePlaceholder(): void
    {
        $rendered = $this->renderTemplate('Placeholder/BackendOnly');

        $this->assertStringContainsString('data-identifier="default-not-found"', $rendered);
        $this->assertStringContainsString('icon-default-not-found', $rendered);
        $this->assertStringNotContainsString('test-backend-only', $rendered);
    }

    #[Test]
    public function anUnknownIdentifierRendersTheVisiblePlaceholder(): void
    {
        $rendered = $this->renderTemplate('Placeholder/Unknown');

        $this->assertStringContainsString('data-identifier="default-not-found"', $rendered);
        $this->assertStringContainsString('default-not-found.svg', $rendered);
        $this->assertStringNotContainsString('test-frontend-fone', $rendered);
    }

    #[Test]
    public function anUnknownOverlayRendersThePlaceholder(): void
    {
        $rendered = $this->renderTemplate('Placeholder/UnknownOverlay');

        $this->assertStringContainsString('data-identifier="test-frontend-current-color"', $rendered);
        $this->assertStringContainsString('<span class="icon-overlay icon-default-not-found"><img src="', $rendered);
        $this->assertStringNotContainsString('test-frontend-unknown-overlay', $rendered);
    }

    #[Test]
    public function anIconContributedByAListenerRenders(): void
    {
        $rendered = $this->renderTemplate('Contributed');

        $this->assertStringContainsString('data-identifier="test-frontend-contributed"', $rendered);
        $this->assertStringContainsString('<svg', $rendered);
    }

    /**
     * `core:icon` sets the title on the icon its factory caches for the request, so the
     * next rendering of the same icon without a title still carries it. This one does not.
     */
    #[Test]
    public function aTitleAppearsOnlyOnTheIconRenderedWithIt(): void
    {
        [$withTitle, $withoutTitle] = $this->renderParts('TitleOnce');

        $this->assertStringContainsString('title="Call"', $withTitle);
        $this->assertStringNotContainsString('title=', $withoutTitle);
    }

    #[Test]
    public function anInvalidSizeFailsAsInCoreIcon(): void
    {
        $this->expectException(\ValueError::class);

        $this->renderTemplate('InvalidSize');
    }

    /**
     * @return list<string>
     */
    private function renderParts(string $template): array
    {
        return array_map(trim(...), explode("\n===\n", $this->renderTemplate($template)));
    }

    private function renderTemplate(string $template): string
    {
        $request = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $view = $this->get(ViewFactoryInterface::class)->create(new ViewFactoryData(
            templateRootPaths: ['EXT:test_frontend_icons/Resources/Private/Templates/'],
            request: $request,
        ));

        return trim($view->render($template));
    }
}
