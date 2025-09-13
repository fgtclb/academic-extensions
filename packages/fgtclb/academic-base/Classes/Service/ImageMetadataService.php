<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Service;

use FGTCLB\AcademicBase\Provider\FileMetadataProviderInterface;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;

final readonly class ImageMetadataService
{
    public function setMetadata(FileMetadataProviderInterface $model, FileReference $fileReference): void
    {
        $metadata = $fileReference->getOriginalResource()->getOriginalFile()->getMetaData();

        $title = $metadata->offsetExists('title') && !empty($metadata->offsetGet('title'))
            ? $metadata->offsetGet('title')
            : $model->getTitleForImage();

        $alternative = $metadata->offsetExists('alternative') && !empty($metadata->offsetGet('alternative'))
            ? $metadata->offsetGet('alternative')
            : $model->getAltTextForImage();

        $copyright = $metadata->offsetExists('copyright') && !empty($metadata->offsetGet('copyright'))
            ? $metadata->offsetGet('copyright')
            : $model->getCopyrightForImage();

        $metadata->offsetSet('title', $title);
        $metadata->offsetSet('alternative', $alternative);
        $metadata->offsetSet('copyright', $copyright);
    }
}
