<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Service;

use TYPO3\CMS\Extbase\Mvc\Controller\FileUploadConfiguration;
// @todo Replace with TYPO3\CMS\Extbase\Validation\Validator\FileSizeValidator when dropping TYPO3 v12 support
use FGTCLB\AcademicBase\Validation\Validator\FileSizeValidator;
// @todo Replace with TYPO3\CMS\Extbase\Validation\Validator\MimeTypeValidator when dropping TYPO3 v12 support
use FGTCLB\AcademicBase\Validation\Validator\MimeTypeValidator;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class FileUploadService
{
    /**
     * @param string $propertyName The name of the property to map
     * @param array $settings The TypoScript settings array for the upload configuration
     */
    public function configureFileUpload(
        string $propertyName,
        array $settings
    ): FileUploadConfiguration {
        $fileUploadConfiguration = new FileUploadConfiguration($propertyName);
        $fileUploadConfiguration
            ->resetValidators()
            ->setMaxFiles((int) $settings['maxFiles'])
            ->setUploadFolder($settings['uploadFolder']);

        $allowedMimeTypes = GeneralUtility::trimExplode(',', $settings['validation']['mimeType']['allowedMimeTypes'], true);
        if (!empty($allowedMimeTypes)) {
            $mimeTypeValidator = GeneralUtility::makeInstance(MimeTypeValidator::class);
            $mimeTypeValidator->setOptions(['allowedMimeTypes' => $allowedMimeTypes]);
            $fileUploadConfiguration->addValidator($mimeTypeValidator);
        }

        $fileSizeMaximumInBytes = $settings['validation']['fileSize']['maximum'];
        if (!empty($fileSizeMaximumInBytes)) {
            $fileSizeValidator = GeneralUtility::makeInstance(FileSizeValidator::class);
            $fileSizeValidator->setOptions(['maximum' => $fileSizeMaximumInBytes]);
            $fileUploadConfiguration->addValidator($fileSizeValidator);
        }

        return $fileUploadConfiguration;
    }
}
