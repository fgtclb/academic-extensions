<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional\ViewHelpers;

use FGTCLB\AcademicBase\Imaging\FrontendIconRenderer;
use FGTCLB\AcademicBase\Tests\Functional\AbstractAcademicBaseTestCase;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;

/**
 * Renders the JSON icon map the way a template uses it, from the fixture templates in
 * `Tests/Functional/Fixtures/Templates/`, and reads the result back with a DOM parser -
 * which is also what a browser does with it before the TypeScript module sees it.
 */
final class FrontendIconMapViewHelperTest extends AbstractAcademicBaseTestCase
{
    protected array $testExtensionsToLoad = [
        'fgtclb/academic-base',
        'tests/frontend-icons',
    ];

    #[Test]
    public function rendersTheInlineMarkupOfTheNamedIdentifiers(): void
    {
        $identifiers = ['tx-academicbase-action-add', 'tx-academicbase-info-phone'];

        $element = $this->renderMap('FrontendIconMap', ['identifiers' => $identifiers]);

        $this->assertSame('application/json', $element->getAttribute('type'));
        $this->assertTrue($element->hasAttribute('data-academic-icons'));
        $this->assertSame('small', $element->getAttribute('data-academic-icons-size'));
        $map = $this->decode($element);
        $this->assertSame($identifiers, array_keys($map));
        $iconFactory = $this->get(IconFactory::class);
        foreach ($identifiers as $identifier) {
            $this->assertSame($iconFactory->getIcon($identifier, IconSize::SMALL)->render('inline'), $map[$identifier]);
        }
        // The project override of the fixture extension, not the shared drawing.
        $this->assertStringContainsString('M1 1h14v14H1z', $map['tx-academicbase-info-phone']);
    }

    #[Test]
    public function leavesOutWhatMayNotBeServed(): void
    {
        $element = $this->renderMap('FrontendIconMap', ['identifiers' => [
            'actions-add',
            'tx-academicbase-action-unknown',
            'tx-academictest-action-deprecated',
            'tx-testforeign-action-star',
            'tx-academicbase-action-add',
        ]]);

        $this->assertSame(['tx-academicbase-action-add'], array_keys($this->decode($element)));
    }

    #[Test]
    public function rendersAnEmptyObjectWhenNothingMayBeServed(): void
    {
        $element = $this->renderMap('FrontendIconMap', ['identifiers' => ['actions-add']]);

        $this->assertSame('{}', $element->textContent);
    }

    /**
     * The markup of an icon carries `<`, `>` and quotes. Unescaped, a `</script>` in any
     * of it would end the data block early and put the rest into the page as markup.
     */
    #[Test]
    public function escapesTheMarkupSoItCannotEndTheElement(): void
    {
        $output = $this->render('FrontendIconMap', ['identifiers' => ['tx-academicbase-action-add']]);

        $this->assertSame(1, substr_count($output, '</script>'));
        $this->assertStringEndsWith('</script>', $output);
        $json = substr($output, (int)strpos($output, '>') + 1, -strlen('</script>'));
        $this->assertStringNotContainsString('<', $json);
        $this->assertStringNotContainsString('>', $json);
        $this->assertStringContainsString('\\u003Csvg', $json);
    }

    #[Test]
    public function rendersTheRequestedSize(): void
    {
        $element = $this->renderMap('FrontendIconMapWithSize', [
            'identifiers' => ['tx-academicbase-action-add'],
            'size' => 'large',
        ]);

        $this->assertSame('large', $element->getAttribute('data-academic-icons-size'));
        $this->assertStringContainsString('icon-size-large', $this->decode($element)['tx-academicbase-action-add']);
    }

    #[Test]
    public function refusesAnUnknownSize(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1789120001);

        $this->render('FrontendIconMapWithSize', ['identifiers' => ['tx-academicbase-action-add'], 'size' => 'overlay']);
    }

    #[Test]
    public function namesNoEndpointUnlessAskedTo(): void
    {
        $element = $this->renderMap('FrontendIconMap', ['identifiers' => ['tx-academicbase-action-add']], $this->siteRequest());

        $this->assertFalse($element->hasAttribute('data-academic-icons-url'));
        $this->assertFalse($element->hasAttribute('data-academic-icons-version'));
    }

    #[Test]
    public function namesTheEndpointBelowTheBaseOfTheSiteLanguage(): void
    {
        $element = $this->renderMap('FrontendIconMapWithEndpoint', ['identifiers' => ['tx-academicbase-action-add']], $this->siteRequest(1));

        $this->assertSame('https://www.acme.com/sub/de/_academic/icons.json', $element->getAttribute('data-academic-icons-url'));
        $this->assertSame($this->get(FrontendIconRenderer::class)->getVersion(), $element->getAttribute('data-academic-icons-version'));
    }

    #[Test]
    public function namesTheEndpointBelowTheBaseOfTheSiteWithoutALanguage(): void
    {
        $request = $this->siteRequest()->withoutAttribute('language');

        $element = $this->renderMap('FrontendIconMapWithEndpoint', ['identifiers' => []], $request);

        $this->assertSame('https://www.acme.com/sub/_academic/icons.json', $element->getAttribute('data-academic-icons-url'));
    }

    #[Test]
    public function namesNoEndpointOutsideASite(): void
    {
        $element = $this->renderMap('FrontendIconMapWithEndpoint', ['identifiers' => ['tx-academicbase-action-add']]);

        $this->assertFalse($element->hasAttribute('data-academic-icons-url'));
        $this->assertFalse($element->hasAttribute('data-academic-icons-version'));
        $this->assertSame(['tx-academicbase-action-add'], array_keys($this->decode($element)));
    }

    private function siteRequest(int $languageId = 0): ServerRequestInterface
    {
        $site = new Site('acme', 1, [
            'base' => 'https://www.acme.com/sub/',
            'languages' => [
                ['languageId' => 0, 'title' => 'English', 'locale' => 'en_US.UTF-8', 'base' => '/'],
                ['languageId' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF-8', 'base' => '/de/'],
            ],
        ]);

        return $this->frontendRequest()
            ->withAttribute('site', $site)
            ->withAttribute('language', $site->getLanguageById($languageId));
    }

    private function frontendRequest(): ServerRequestInterface
    {
        return (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
    }

    /**
     * @param array<string, mixed> $variables
     */
    private function render(string $template, array $variables, ?ServerRequestInterface $request = null): string
    {
        $view = $this->get(ViewFactoryInterface::class)->create(new ViewFactoryData(
            templateRootPaths: [__DIR__ . '/../Fixtures/Templates/'],
            request: $request ?? $this->frontendRequest(),
        ));
        $view->assignMultiple($variables);

        return trim($view->render($template));
    }

    /**
     * @param array<string, mixed> $variables
     */
    private function renderMap(string $template, array $variables, ?ServerRequestInterface $request = null): \DOMElement
    {
        $document = new \DOMDocument();
        $useInternalErrors = libxml_use_internal_errors(true);
        try {
            $document->loadHTML('<!DOCTYPE html><html><head>' . $this->render($template, $variables, $request) . '</head></html>');
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($useInternalErrors);
        }
        $elements = $document->getElementsByTagName('script');
        $this->assertSame(1, $elements->length, 'Not exactly one element rendered.');
        $element = $elements->item(0);
        $this->assertInstanceOf(\DOMElement::class, $element);

        return $element;
    }

    /**
     * @return array<string, string>
     */
    private function decode(\DOMElement $element): array
    {
        $map = json_decode($element->textContent, true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($map);

        return $map;
    }
}
