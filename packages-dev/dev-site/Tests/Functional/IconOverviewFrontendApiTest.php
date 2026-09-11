<?php

declare(strict_types=1);

namespace FGTCLB\AcademicsDevSite\Tests\Functional;

use FGTCLB\AcademicBase\Imaging\FrontendIconRenderer;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;

/**
 * The demonstration of the frontend icon API on the icon overview page carries
 * what the module `icon-demo.js` needs to show both paths: a JSON icon map with
 * the icons of the first list, the endpoint and version token for the second
 * list, a slot per identifier, and the module itself. That the module then
 * fills the slots is the factory's behaviour, which the JavaScript tests of
 * academic_base cover, and is looked at in a browser on the instances.
 */
final class IconOverviewFrontendApiTest extends AbstractIconOverviewTestCase
{
    private const MAP_IDENTIFIERS = [
        'tx-academicbase-state-visible',
        'tx-academicbase-state-hidden',
        'tx-academicbase-info-email',
        'tx-academicbase-info-phone',
        'tx-academicbase-info-location',
    ];

    private const ENDPOINT_IDENTIFIERS = [
        'tx-academicbase-action-add',
        'tx-academicbase-action-edit',
        'tx-academicbase-action-delete',
        'tx-academicbase-action-save',
        'tx-academicbase-action-undo',
        'actions-add',
    ];

    #[Test]
    public function answersTheFirstListFromTheJsonMapOfThePage(): void
    {
        [$xpath, $html] = $this->renderDemo();

        $this->assertSame(self::MAP_IDENTIFIERS, $this->slotsOf($xpath, 'map'));
        $maps = $xpath->query('//*[@data-icon-demo="map"]//script[@data-academic-icons]');
        $this->assertNotFalse($maps);
        $this->assertSame(1, $maps->length);
        $map = $maps->item(0);
        $this->assertInstanceOf(\DOMElement::class, $map);
        $this->assertSame('application/json', $map->getAttribute('type'));
        $this->assertFalse($map->hasAttribute('data-academic-icons-url'), 'The first list must not be able to reach the endpoint.');
        $decoded = json_decode($map->textContent, true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($decoded);
        $this->assertSame(self::MAP_IDENTIFIERS, array_keys($decoded));
        $iconFactory = $this->get(IconFactory::class);
        foreach (self::MAP_IDENTIFIERS as $identifier) {
            $this->assertSame($iconFactory->getIcon($identifier, IconSize::SMALL)->render('inline'), $decoded[$identifier]);
        }
        $this->assertStringNotContainsString('default-not-found', $html);
    }

    #[Test]
    public function sendsTheSecondListToTheEndpointTheSecondMapNames(): void
    {
        [$xpath] = $this->renderDemo();

        $this->assertSame(self::ENDPOINT_IDENTIFIERS, $this->slotsOf($xpath, 'endpoint'));
        $maps = $xpath->query('//*[@data-icon-demo="endpoint"]//script[@data-academic-icons]');
        $this->assertNotFalse($maps);
        $this->assertSame(1, $maps->length);
        $map = $maps->item(0);
        $this->assertInstanceOf(\DOMElement::class, $map);
        $this->assertSame('{}', $map->textContent, 'The second list must not be answered from the page.');
        $url = $map->getAttribute('data-academic-icons-url');
        $this->assertSame(self::FRONTEND_PLUGIN_TEST_BASE . FrontendIconRenderer::ENDPOINT_PATH, $url);
        $this->assertSame($this->get(FrontendIconRenderer::class)->getVersion(), $map->getAttribute('data-academic-icons-version'));

        // What the module will ask for, the way it asks: one request for the list.
        $response = $this->requestFrontendPage($url . '?i=' . implode(',', self::ENDPOINT_IDENTIFIERS) . '&s=small&v=' . $map->getAttribute('data-academic-icons-version'));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('public, max-age=31536000, immutable', $response->getHeaderLine('Cache-Control'));
        $answer = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($answer);
        $this->assertSame(array_slice(self::ENDPOINT_IDENTIFIERS, 0, 5), array_keys($answer), 'The core icon is not refused, or a shared one is missing.');
    }

    #[Test]
    public function loadsTheDemoModuleThroughTheImportMap(): void
    {
        [, $html] = $this->renderDemo();

        $this->assertStringContainsString('@fgtclb/academics-dev-site/frontend/icon-demo.js', $html);
        $this->assertStringContainsString('"@fgtclb/academic-base/frontend/"', $html);
        $this->assertStringContainsString('academic_base/Resources/Public/JavaScript/frontend/', $html);
    }

    /**
     * @return array{0: \DOMXPath, 1: string}
     */
    private function renderDemo(): array
    {
        $html = $this->renderFrontendPage(self::FRONTEND_PLUGIN_TEST_BASE);
        $document = new \DOMDocument();
        $useInternalErrors = libxml_use_internal_errors(true);
        try {
            $this->assertTrue($document->loadHTML($html), 'The page is not parseable HTML.');
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($useInternalErrors);
        }
        $xpath = new \DOMXPath($document);
        $sections = $xpath->query('//section[@class="academics-dev-site-icon-demo"]');
        $this->assertNotFalse($sections);
        $this->assertSame(1, $sections->length, 'The demonstration is not on the page.');

        return [$xpath, $html];
    }

    /**
     * @return list<string>
     */
    private function slotsOf(\DOMXPath $xpath, string $demo): array
    {
        $slots = $xpath->query(sprintf('//*[@data-icon-demo="%s"]//*[@data-icon-demo-identifier]', $demo));
        $this->assertNotFalse($slots);
        $identifiers = [];
        foreach ($slots as $slot) {
            $this->assertInstanceOf(\DOMElement::class, $slot);
            $this->assertSame('pending', $slot->getAttribute('data-icon-demo-state'));
            $this->assertSame('', trim($slot->textContent), 'A slot is filled on the server; the module is what fills it.');
            $identifiers[] = $slot->getAttribute('data-icon-demo-identifier');
        }

        return $identifiers;
    }
}
