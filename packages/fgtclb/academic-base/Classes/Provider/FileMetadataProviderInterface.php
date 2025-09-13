<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Provider;

use FieldNotDefinedException;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;

interface FileMetadataProviderInterface
{
    public function getImage(): ?FileReference;

    /**
     * @throws FieldNotDefinedException
     */
    public function getCopyrightForImage(): string;

    /**
     * @throws FieldNotDefinedException
     */
    public function getAltTextForImage(): string;

    /**
     * @throws FieldNotDefinedException
     */
    public function getTitleForImage(): string;
}
