<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional\Partials;

use FGTCLB\AcademicBase\Tests\Functional\AbstractAcademicBaseTestCase;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;

/**
 * Renders `EXT:academic_base/Resources/Private/Partials/Academic/Image.html` through the
 * fixture template `Fixtures/Templates/Image.html`, the way a plugin template renders it.
 *
 * The fixture has two file references on two real files below `fileadmin/images/`, so the
 * image processing of the test container runs for real:
 *
 * - reference 1: an 800 x 600 JPEG with a description, an alternative text and two crop
 *   areas - `default`, the left three quarters (600 x 600), and `portrait`, the middle half
 *   (400 x 600);
 * - reference 2: an SVG without a crop.
 */
abstract class AbstractImagePartialTestCase extends AbstractAcademicBaseTestCase
{
    protected const JPEG_REFERENCE = 1;
    protected const SVG_REFERENCE = 2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Images.csv');
        $folder = $this->instancePath . '/fileadmin/images';
        GeneralUtility::mkdir_deep($folder);
        foreach (['landscape.jpg', 'logo.svg'] as $fileName) {
            copy(__DIR__ . '/Fixtures/Files/' . $fileName, $folder . '/' . $fileName);
        }
    }

    protected function reference(int $uid): FileReference
    {
        return $this->get(ResourceFactory::class)->getFileReferenceObject($uid);
    }

    /**
     * @param array<string, mixed> $arguments
     */
    protected function renderPartial(array $arguments): string
    {
        $view = $this->get(ViewFactoryInterface::class)->create(new ViewFactoryData(
            templateRootPaths: [__DIR__ . '/Fixtures/Templates/'],
            partialRootPaths: ['EXT:academic_base/Resources/Private/Partials/'],
            request: (new ServerRequest('https://www.acme.com/'))
                ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE),
        ));
        $view->assignMultiple($arguments);

        return trim($view->render('Image'));
    }

    protected function parse(string $html): \DOMXPath
    {
        $document = new \DOMDocument();
        $document->loadHTML('<?xml encoding="utf-8"?><body>' . $html . '</body>', LIBXML_NOERROR);

        return new \DOMXPath($document);
    }

    /**
     * @return \DOMNodeList<\DOMNode>
     */
    protected function nodes(\DOMXPath $xpath, string $query, ?\DOMNode $context = null): \DOMNodeList
    {
        $nodes = $xpath->query($query, $context);
        $this->assertInstanceOf(\DOMNodeList::class, $nodes, sprintf('The query "%s" is invalid.', $query));

        return $nodes;
    }

    protected function countNodes(\DOMXPath $xpath, string $query, ?\DOMNode $context = null): int
    {
        return $this->nodes($xpath, $query, $context)->length;
    }

    protected function text(\DOMXPath $xpath, string $query): string
    {
        return (string)$this->nodes($xpath, $query)->item(0)?->textContent;
    }

    protected function attribute(\DOMXPath $xpath, string $query, string $attribute): string
    {
        $node = $this->nodes($xpath, $query)->item(0);
        $this->assertInstanceOf(\DOMElement::class, $node, sprintf('Nothing matches "%s".', $query));

        return $node->getAttribute($attribute);
    }
}
