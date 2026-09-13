<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional\Partials;

use PHPUnit\Framework\Attributes\Test;

/**
 * The copyright of the responsive image partial, which only exists as a column of
 * `sys_file_metadata` when EXT:filemetadata is installed.
 */
final class ImagePartialWithFileMetadataTest extends AbstractImagePartialTestCase
{
    protected array $coreExtensionsToLoad = [
        'typo3/cms-install',
        'typo3/cms-filemetadata',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->getConnectionPool()
            ->getConnectionForTable('sys_file_metadata')
            ->update('sys_file_metadata', ['copyright' => 'Jane Doe'], ['file' => 1]);
    }

    #[Test]
    public function copyrightIsRenderedWhenRequested(): void
    {
        $xpath = $this->parse($this->renderPartial([
            'image' => $this->reference(self::JPEG_REFERENCE),
            'preset' => 'card',
            'showCopyright' => true,
        ]));

        $caption = $this->text($xpath, '//figure/figcaption');
        $this->assertStringContainsString('Jane Doe', $caption);
        // Only the copyright was requested, not the description.
        $this->assertStringNotContainsString('Two colours side by side', $caption);
    }

    #[Test]
    public function copyrightIsNotRenderedUnlessRequested(): void
    {
        $html = $this->renderPartial([
            'image' => $this->reference(self::JPEG_REFERENCE),
            'preset' => 'card',
            'showCaption' => true,
        ]);

        $this->assertStringContainsString('Two colours side by side', $html);
        $this->assertStringNotContainsString('Jane Doe', $html);
    }

    #[Test]
    public function captionAndCopyrightAreRenderedTogether(): void
    {
        $xpath = $this->parse($this->renderPartial([
            'image' => $this->reference(self::JPEG_REFERENCE),
            'preset' => 'card',
            'showCaption' => true,
            'showCopyright' => true,
        ]));

        $caption = $this->text($xpath, '//figure/figcaption');
        $this->assertStringContainsString('Two colours side by side', $caption);
        $this->assertStringContainsString('Jane Doe', $caption);
    }
}
