<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional\Partials;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Domain\Model\FileReference as ExtbaseFileReference;

/**
 * The responsive image partial on an installation without EXT:filemetadata.
 *
 * The widths of each preset are asserted through the fallback image of an uncropped
 * rendering - the crop variant `unknown` has no crop area - so the numbers are the preset
 * widths themselves, capped at the 800 pixels of the original.
 */
final class ImagePartialTest extends AbstractImagePartialTestCase
{
    /**
     * @return array<string, array{string, list<string>, int, int}>
     */
    public static function presetDataProvider(): array
    {
        return [
            'card' => ['card', ['(min-width: 992px)', '(min-width: 768px)', '(min-width: 576px)', ''], 690, 518],
            'detail' => ['detail', ['(min-width: 992px)', '(min-width: 576px)', ''], 800, 600],
            'logo' => ['logo', ['(min-width: 768px)', ''], 320, 240],
            'teaser' => ['teaser', ['(min-width: 992px)', '(min-width: 576px)', ''], 720, 540],
        ];
    }

    /**
     * @param list<string> $expectedMedia
     */
    #[DataProvider('presetDataProvider')]
    #[Test]
    public function presetRendersAPictureWithOneWebpSourcePerBreakpoint(
        string $preset,
        array $expectedMedia,
        int $expectedWidth,
        int $expectedHeight,
    ): void {
        $xpath = $this->parse($this->renderPartial([
            'image' => $this->reference(self::JPEG_REFERENCE),
            'preset' => $preset,
            'cropVariant' => 'unknown',
        ]));

        $this->assertSame(1, $this->countNodes($xpath, '//picture'));
        $media = [];
        foreach ($this->nodes($xpath, '//picture/source') as $source) {
            $this->assertInstanceOf(\DOMElement::class, $source);
            $this->assertSame('image/webp', $source->getAttribute('type'));
            $this->assertStringEndsWith('.webp', $source->getAttribute('srcset'));
            $media[] = $source->getAttribute('media');
        }
        $this->assertSame($expectedMedia, $media);

        $this->assertSame(1, $this->countNodes($xpath, '//picture/img'));
        $this->assertStringEndsWith('.jpg', $this->attribute($xpath, '//picture/img', 'src'));
        $this->assertSame('lazy', $this->attribute($xpath, '//picture/img', 'loading'));
        $this->assertSame((string)$expectedWidth, $this->attribute($xpath, '//picture/img', 'width'));
        $this->assertSame((string)$expectedHeight, $this->attribute($xpath, '//picture/img', 'height'));
    }

    #[Test]
    public function withoutCropVariantTheDefaultCropAreaIsApplied(): void
    {
        $xpath = $this->parse($this->renderPartial([
            'image' => $this->reference(self::JPEG_REFERENCE),
            'preset' => 'card',
        ]));

        // The left three quarters of the image: 600 x 600, below the 690 of the fallback.
        $this->assertSame('600', $this->attribute($xpath, '//picture/img', 'width'));
        $this->assertSame('600', $this->attribute($xpath, '//picture/img', 'height'));
        $this->assertProcessedWebpFilesHaveTheRatio(4, 1.0);
    }

    #[Test]
    public function namedCropVariantIsAppliedToEverySourceAndTheFallback(): void
    {
        $xpath = $this->parse($this->renderPartial([
            'image' => $this->reference(self::JPEG_REFERENCE),
            'preset' => 'card',
            'cropVariant' => 'portrait',
        ]));

        $this->assertSame('400', $this->attribute($xpath, '//picture/img', 'width'));
        $this->assertSame('600', $this->attribute($xpath, '//picture/img', 'height'));
        $this->assertProcessedWebpFilesHaveTheRatio(4, 1.5);
    }

    #[Test]
    public function cropVariantWithoutCropAreaRendersTheImageUncropped(): void
    {
        $xpath = $this->parse($this->renderPartial([
            'image' => $this->reference(self::JPEG_REFERENCE),
            'preset' => 'card',
            'cropVariant' => 'unknown',
        ]));

        $this->assertSame('690', $this->attribute($xpath, '//picture/img', 'width'));
        $this->assertProcessedWebpFilesHaveTheRatio(4, 0.75);
    }

    #[Test]
    public function svgIsRenderedAsTheOriginalFileWithoutSources(): void
    {
        $xpath = $this->parse($this->renderPartial([
            'image' => $this->reference(self::SVG_REFERENCE),
            'preset' => 'logo',
        ]));

        $this->assertSame(0, $this->countNodes($xpath, '//picture'));
        $this->assertSame(0, $this->countNodes($xpath, '//source'));
        $this->assertSame(1, $this->countNodes($xpath, '//img'));
        $this->assertStringEndsWith('fileadmin/images/logo.svg', $this->attribute($xpath, '//img', 'src'));
        $this->assertSame('A logo', $this->attribute($xpath, '//img', 'alt'));
        $this->assertSame(0, $this->getConnectionPool()->getConnectionForTable('sys_file_processedfile')->count('*', 'sys_file_processedfile', []));
    }

    #[Test]
    public function missingImageRendersThePlaceholder(): void
    {
        $xpath = $this->parse($this->renderPartial([
            'image' => null,
            'preset' => 'card',
            'placeholder' => 'EXT:academic_base/Resources/Public/Icons/Extension.svg',
            'class' => 'card-img-top',
        ]));

        $this->assertSame(0, $this->countNodes($xpath, '//source'));
        $this->assertSame(1, $this->countNodes($xpath, '//img'));
        $this->assertStringEndsWith('Icons/Extension.svg', $this->attribute($xpath, '//img', 'src'));
        $this->assertSame('card-img-top', $this->attribute($xpath, '//img', 'class'));
    }

    #[Test]
    public function missingImageWithoutPlaceholderRendersNothing(): void
    {
        $this->assertSame('', $this->renderPartial([
            'image' => null,
            'preset' => 'card',
            'placeholder' => '',
        ]));
    }

    #[Test]
    public function altTextAndClassReachTheFallbackImage(): void
    {
        $xpath = $this->parse($this->renderPartial([
            'image' => $this->reference(self::JPEG_REFERENCE),
            'preset' => 'card',
            'alt' => 'Given alternative',
            'class' => 'card-img-top img-fluid',
        ]));

        $this->assertSame('Given alternative', $this->attribute($xpath, '//picture/img', 'alt'));
        $this->assertSame('card-img-top img-fluid', $this->attribute($xpath, '//picture/img', 'class'));
    }

    #[Test]
    public function withoutAltTextTheFallbackImageUsesTheAlternativeOfTheFile(): void
    {
        $xpath = $this->parse($this->renderPartial([
            'image' => $this->reference(self::JPEG_REFERENCE),
            'preset' => 'card',
        ]));

        $this->assertSame('A landscape in two colours', $this->attribute($xpath, '//picture/img', 'alt'));
    }

    #[Test]
    public function captionIsRenderedWhenRequested(): void
    {
        $xpath = $this->parse($this->renderPartial([
            'image' => $this->reference(self::JPEG_REFERENCE),
            'preset' => 'card',
            'showCaption' => true,
        ]));

        $this->assertSame(1, $this->countNodes($xpath, '//figure/picture'));
        $this->assertStringContainsString('Two colours side by side', $this->text($xpath, '//figure/figcaption'));
    }

    /**
     * An Extbase file reference - the profile image of academic_persons - has no description
     * of its own; the partial reads it from the core reference the Extbase one wraps.
     */
    #[Test]
    public function captionOfAnExtbaseFileReferenceComesFromItsOriginalResource(): void
    {
        $extbaseReference = new ExtbaseFileReference();
        $extbaseReference->setOriginalResource($this->reference(self::JPEG_REFERENCE));

        $xpath = $this->parse($this->renderPartial([
            'image' => $extbaseReference,
            'preset' => 'card',
            'showCaption' => true,
        ]));

        $this->assertGreaterThan(0, $this->countNodes($xpath, '//figure/picture/source'));
        $this->assertStringContainsString('Two colours side by side', $this->text($xpath, '//figure/figcaption'));
    }

    #[Test]
    public function captionIsNotRenderedUnlessRequested(): void
    {
        $html = $this->renderPartial([
            'image' => $this->reference(self::JPEG_REFERENCE),
            'preset' => 'card',
        ]);

        $this->assertStringNotContainsString('<figure', $html);
        $this->assertStringNotContainsString('Two colours side by side', $html);
    }

    #[Test]
    public function requestedCopyrightRendersNothingWithoutTheFileMetadataExtension(): void
    {
        $html = $this->renderPartial([
            'image' => $this->reference(self::JPEG_REFERENCE),
            'preset' => 'card',
            'showCopyright' => true,
        ]);

        $this->assertStringContainsString('<picture', $html);
        $this->assertStringNotContainsString('<figcaption', $html);
        $this->assertStringNotContainsString('©', $html);
    }

    /**
     * WebP is part of `imagefile_ext` in the core default configuration. An installation that
     * removed it gets the exception of the image view helpers rather than a silent fallback.
     */
    #[Test]
    public function withoutWebpAmongTheImageFileExtensionsTheRenderingFails(): void
    {
        $imageFileExtensions = $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'];
        $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'] = 'gif,jpg,jpeg,png,svg';
        $this->expectExceptionCode(1618992262);
        try {
            $this->renderPartial([
                'image' => $this->reference(self::JPEG_REFERENCE),
                'preset' => 'card',
            ]);
        } finally {
            $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'] = $imageFileExtensions;
        }
    }

    private function assertProcessedWebpFilesHaveTheRatio(int $expectedCount, float $expectedRatio): void
    {
        $rows = $this->getConnectionPool()
            ->getConnectionForTable('sys_file_processedfile')
            ->select(['name', 'width', 'height'], 'sys_file_processedfile')
            ->fetchAllAssociative();
        $webp = array_values(array_filter(
            $rows,
            static fn(array $row): bool => str_ends_with((string)$row['name'], '.webp'),
        ));

        $this->assertCount($expectedCount, $webp);
        foreach ($webp as $row) {
            $this->assertGreaterThan(0, (int)$row['width']);
            $this->assertEqualsWithDelta(
                $expectedRatio,
                (int)$row['height'] / (int)$row['width'],
                0.01,
                sprintf('The processed file "%s" is %d x %d.', $row['name'], $row['width'], $row['height']),
            );
        }
    }
}
