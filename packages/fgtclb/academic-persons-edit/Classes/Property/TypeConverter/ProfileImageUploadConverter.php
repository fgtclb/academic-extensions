<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons_edit" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fgtclb\AcademicPersonsEdit\Property\TypeConverter;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Resource\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileReference as CoreFileReference;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\Security\FileNameValidator;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference as ExtbaseFileReference;
use TYPO3\CMS\Extbase\Error\Error;
use TYPO3\CMS\Extbase\Property\Exception\TypeConverterException;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface;
use TYPO3\CMS\Extbase\Property\TypeConverter\AbstractTypeConverter;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

final class ProfileImageUploadConverter extends AbstractTypeConverter implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const CONFIGURATION_TARGET_DIRECTORY_COMBINED_IDENTIFIER = 'targetFolderCombinedIdentifier';
    public const CONFIGURATION_MAX_UPLOAD_SIZE = 'maxUploadSize';
    public const CONFIGURATION_ALLOWED_MIME_TYPES = 'allowedMimeTypes';
    public const CONFIGURATION_TARGET_FILE_NAME_WITHOUT_EXTENSION = 'targetFileNameWithoutExtension';

    protected $sourceTypes = ['array'];

    protected $targetType = ExtbaseFileReference::class;

    protected $priority = 10;

    public function __construct(protected ResourceFactory $resourceFactory) {}

    /**
     * Actually convert from $source to $targetType, taking into account the fully
     * built $convertedChildProperties and $configuration.
     *
     * @param array{name?: string, type: string, tmp_name?: string, error?: int, size: int, __identity?: string} $source
     * @param array<string, mixed> $convertedChildProperties
     */
    public function convertFrom(
        $source,
        string $targetType,
        array $convertedChildProperties = [],
        ?PropertyMappingConfigurationInterface $configuration = null
    ): Error|ExtbaseFileReference|null {
        $uploadedFileInformation = $source;

        $targetFolderIdentifier = null;
        $maxFileSize = '0k';
        $allowedMimeTypes = '';
        $targetFileNameWithoutExtension = null;
        if ($configuration !== null) {
            $targetFolderIdentifier = $configuration->getConfigurationValue(
                self::class,
                self::CONFIGURATION_TARGET_DIRECTORY_COMBINED_IDENTIFIER
            );
            $maxFileSize = $configuration->getConfigurationValue(
                self::class,
                self::CONFIGURATION_MAX_UPLOAD_SIZE
            );
            $allowedMimeTypes = $configuration->getConfigurationValue(
                self::class,
                self::CONFIGURATION_ALLOWED_MIME_TYPES
            );
            $targetFileNameWithoutExtension = $configuration->getConfigurationValue(
                self::class,
                self::CONFIGURATION_TARGET_FILE_NAME_WITHOUT_EXTENSION
            );
        }

        if (!isset($uploadedFileInformation['error']) || $uploadedFileInformation['error'] === \UPLOAD_ERR_NO_FILE) {
            return $this->handleNoFileUploaded($uploadedFileInformation);
        }

        if ($uploadedFileInformation['error'] !== \UPLOAD_ERR_OK) {
            return GeneralUtility::makeInstance(
                Error::class,
                $this->getUploadErrorMessage($uploadedFileInformation['error']),
                1471715915
            );
        }

        if (empty($targetFileNameWithoutExtension) || !is_string($targetFileNameWithoutExtension)) {
            return GeneralUtility::makeInstance(
                Error::class,
                'Target filename could not be generated.',
                1695106476
            );
        }

        if (!isset($uploadedFileInformation['tmp_name']) || !isset($uploadedFileInformation['name'])) {
            return null;
        }

        $fileExtension = pathinfo($uploadedFileInformation['name'], PATHINFO_EXTENSION);
        $targetFileName = strtolower(sprintf('%s.%s', $targetFileNameWithoutExtension, $fileExtension));

        try {
            $this->validateUploadedFile($uploadedFileInformation, $maxFileSize, $allowedMimeTypes);
            return $this->importUploadedResource($uploadedFileInformation, $targetFolderIdentifier, $targetFileName);
        } catch (TypeConverterException $e) {
            return GeneralUtility::makeInstance(
                Error::class,
                $e->getMessage(),
                $e->getCode()
            );
        }
    }

    /**
     * @param array{name: string, tmp_name: string, __identity?: string} $uploadedFileInformation
     * @throws TypeConverterException
     */
    private function importUploadedResource(
        array $uploadedFileInformation,
        string $targetFolderIdentifier,
        string $targetFileName
    ): ExtbaseFileReference {
        if (!GeneralUtility::makeInstance(FileNameValidator::class)->isValid($uploadedFileInformation['name'])) {
            throw new TypeConverterException('Uploading files with PHP file extensions is not allowed!', 1690525745);
        }

        $targetFolder = $this->getOrCreateTargetFolder($targetFolderIdentifier);
        /** @var File $uploadedFile */
        $uploadedFile = $targetFolder->getStorage()->addUploadedFile(
            $uploadedFileInformation,
            $targetFolder,
            $targetFileName,
            DuplicationBehavior::REPLACE
        );

        $resourcePointer = isset($uploadedFileInformation['__identity']) ? (int)$uploadedFileInformation['__identity'] : null;
        return $this->createFileReferenceFromFalFileObject($uploadedFile, $resourcePointer);
    }

    /**
     * @param array{size: int, type: string} $uploadedFileInformation
     * @throws TypeConverterException
     */
    private function validateUploadedFile(array $uploadedFileInformation, string $maxFileSize, string $allowedMimeTypes): void
    {
        $typoScriptFrontendController = $this->getTypo3Request()->getAttribute('frontend.controller') ?? $GLOBALS['TSFE'] ?? null;
        $maxFileSizeInBytes = GeneralUtility::getBytesFromSizeMeasurement($maxFileSize);
        $allowedMimeTypesArray = GeneralUtility::trimExplode(',', $allowedMimeTypes);

        if ($uploadedFileInformation['size'] > $maxFileSizeInBytes) {
            throw new TypeConverterException(
                $typoScriptFrontendController?->sL(
                    'LLL:EXT:form/Resources/Private/Language/locallang.xlf:upload.error.150530345'
                ) ?? 'Upload error',
                1690538138
            );
        }
        if (!in_array($uploadedFileInformation['type'], $allowedMimeTypesArray, true)) {
            throw new TypeConverterException(
                $typoScriptFrontendController?->sL(
                    'LLL:EXT:form/Resources/Private/Language/locallang.xlf:validation.error.1471708998',
                    null,
                    $uploadedFileInformation['type'],
                ) ?? 'Validation error',
                1695047315
            );
        }
    }

    /**
     * @throws TypeConverterException
     */
    private function getOrCreateTargetFolder(string $targetFolderIdentifier): Folder
    {
        if (empty($targetFolderIdentifier)) {
            throw new TypeConverterException(
                'Missing TypoScript configuration "editForm.profileImage.targetFolder".',
                1690527282
            );
        }

        try {
            $uploadFolder = $this->resourceFactory->retrieveFileOrFolderObject($targetFolderIdentifier);
        } catch (ResourceDoesNotExistException) {
            $parts = GeneralUtility::trimExplode(':', $targetFolderIdentifier);
            if (count($parts) === 2) {
                $storageUid = (int)$parts[0];
                $folderIdentifier = $parts[1];
                $uploadFolder = $this->resourceFactory->getStorageObject($storageUid)->createFolder($folderIdentifier);
            } else {
                throw new TypeConverterException(
                    sprintf(
                        'Target upload folder "%s" does not exist and creation of forbidden by TypeConverter configuration',
                        $targetFolderIdentifier
                    ),
                    1690527439
                );
            }
        }

        if (!$uploadFolder instanceof Folder) {
            throw new TypeConverterException(
                sprintf('Target upload folder "%s" is not accessible', $targetFolderIdentifier),
                1690527459
            );
        }

        return $uploadFolder;
    }

    /**
     * @param array{__identity?: string} $uploadedFileInformation
     */
    private function handleNoFileUploaded(array $uploadedFileInformation): ?ExtbaseFileReference
    {
        if (!empty($uploadedFileInformation['__identity'])) {
            try {
                $fileReferenceUid = (int)$uploadedFileInformation['__identity'];
                $fileReference = GeneralUtility::makeInstance(ExtbaseFileReference::class);
                $fileReference->setOriginalResource($this->resourceFactory->getFileReferenceObject($fileReferenceUid));
                return $fileReference;
            } catch (\Exception) {
                return null;
            }
        }
        return null;
    }

    private function createFileReferenceFromFalFileReferenceObject(
        CoreFileReference $falFileReference,
        ?int $resourcePointer = null
    ): ExtbaseFileReference {
        if ($resourcePointer !== null) {
            try {
                // Delete the current profile image with its file reference.
                $this->resourceFactory->getFileReferenceObject($resourcePointer)->getOriginalFile()->delete();
            } catch (ResourceDoesNotExistException) {
            }
        }

        $fileReference = GeneralUtility::makeInstance(ExtbaseFileReference::class);
        $fileReference->setOriginalResource($falFileReference);
        return $fileReference;
    }

    private function createFileReferenceFromFalFileObject(
        File $file,
        ?int $resourcePointer = null
    ): ExtbaseFileReference {
        $fileReference = $this->resourceFactory->createFileReferenceObject(
            [
                'uid_local' => $file->getUid(),
                'uid_foreign' => uniqid('NEW_'),
                'uid' => uniqid('NEW_'),
                'crop' => null,
            ]
        );

        return $this->createFileReferenceFromFalFileReferenceObject($fileReference, $resourcePointer);
    }

    /**
     * Returns a human-readable message for the given PHP file upload error
     * constant.
     */
    protected function getUploadErrorMessage(int $errorCode): string
    {
        /** @var TypoScriptFrontendController $typoScriptFrontendController */
        $typoScriptFrontendController = $this->getTypo3Request()->getAttribute('frontend.controller');

        switch ($errorCode) {
            case \UPLOAD_ERR_INI_SIZE:
                $this->logger?->error('The uploaded file exceeds the upload_max_filesize directive in php.ini.');
                return $typoScriptFrontendController->sL('LLL:EXT:form/Resources/Private/Language/locallang.xlf:upload.error.150530345');
            case \UPLOAD_ERR_FORM_SIZE:
                $this->logger?->error('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.');
                return $typoScriptFrontendController->sL('LLL:EXT:form/Resources/Private/Language/locallang.xlf:upload.error.150530345');
            case \UPLOAD_ERR_PARTIAL:
                $this->logger?->error('The uploaded file was only partially uploaded.');
                return $typoScriptFrontendController->sL('LLL:EXT:form/Resources/Private/Language/locallang.xlf:upload.error.150530346');
            case \UPLOAD_ERR_NO_FILE:
                $this->logger?->error('No file was uploaded.');
                return $typoScriptFrontendController->sL('LLL:EXT:form/Resources/Private/Language/locallang.xlf:upload.error.150530347');
            case \UPLOAD_ERR_NO_TMP_DIR:
                $this->logger?->error('Missing a temporary folder.');
                return $typoScriptFrontendController->sL('LLL:EXT:form/Resources/Private/Language/locallang.xlf:upload.error.150530348');
            case \UPLOAD_ERR_CANT_WRITE:
                $this->logger?->error('Failed to write file to disk.');
                return $typoScriptFrontendController->sL('LLL:EXT:form/Resources/Private/Language/locallang.xlf:upload.error.150530348');
            case \UPLOAD_ERR_EXTENSION:
                $this->logger?->error('File upload stopped by extension.');
                return $typoScriptFrontendController->sL('LLL:EXT:form/Resources/Private/Language/locallang.xlf:upload.error.150530348');
            default:
                $this->logger?->error('Unknown upload error.');
                return $typoScriptFrontendController->sL('LLL:EXT:form/Resources/Private/Language/locallang.xlf:upload.error.150530348');
        }
    }

    private function getTypo3Request(): ServerRequestInterface
    {
        if (!isset($GLOBALS['TYPO3_REQUEST'])) {
            throw new \RuntimeException('Missing array key "TYPO3_REQUEST" in $GLOBALS.', 1690525047);
        }
        return $GLOBALS['TYPO3_REQUEST'];
    }
}
