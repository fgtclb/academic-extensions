<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional\Extbase\Property\TypeConverter;

use FGTCLB\AcademicBase\Extbase\Property\TypeConverter\FileUploadConverter;
use FGTCLB\AcademicBase\Tests\Functional\AbstractAcademicBaseTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\UploadedFile;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Property\TypeConverterRegistry;

final class FileUploadConverterTest extends AbstractAcademicBaseTestCase
{
    #[Test]
    public function canBeInstanciatedWithGeneralUtilityMakeinstance(): void
    {
        $this->assertInstanceOf(
            FileUploadConverter::class,
            GeneralUtility::makeInstance(FileUploadConverter::class),
        );
    }

    #[Test]
    public function typeConverterRegistryReturnsFileUploadConverterForUploadedFileSourceAndFileReferenceTarget(): void
    {
        $this->assertInstanceOf(
            FileUploadConverter::class,
            $this->typeConverterRegistry()->findTypeConverter(UploadedFile::class, FileReference::class)
        );
    }

    private function typeConverterRegistry(): TypeConverterRegistry
    {
        return $this->get(TypeConverterRegistry::class);
    }
}
