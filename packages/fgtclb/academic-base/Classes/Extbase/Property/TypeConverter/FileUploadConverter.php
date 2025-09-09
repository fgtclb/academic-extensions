<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Extbase\Property\TypeConverter;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Http\UploadedFile;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Resource\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileReference as CoreFileReference;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\Security\FileNameValidator;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference as ExtbaseFileReference;
use TYPO3\CMS\Extbase\Error\Error;
use TYPO3\CMS\Extbase\Property\Exception\TypeConverterException;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface;
use TYPO3\CMS\Extbase\Property\TypeConverter\AbstractTypeConverter;

/**
 * Provides an extbase type converter to map uploaded files to file reference properties.
 *
 * @internal to be used within `EXT:academic_*` extensions only and not part of public API. That means it is
 *           not covered by breaking policy and semver and can change at any time.
 *
 * @todo The implementation is based `EXT:form/Classes/Mvc/Property/TypeConverter/UploadedFileReferenceConverter.php`,
 *       but the exact version is not known. The implementation differs and misses quite some stuff existing in the
 *       TYPO3 v12 and v13 implementation, which requires a revisit with deeper investigation along with dedicated
 *       adoption of these changes.
 */
final class FileUploadConverter extends AbstractTypeConverter
{
    public const CONFIGURATION_UPLOAD_FOLDER = 'uploadFolder';
    public const CONFIGURATION_VALIDATION_FILESIZE_MAXIMUM = 'validationFileSizeMaximum';
    public const CONFIGURATION_VALIDATION_MIME_TYPE_ALLOWED_MIME_TYPES = 'validationMimeTypeAllowedMimeTypes';
    public const CONFIGURATION_TARGET_FILE_NAME_WITHOUT_EXTENSION = 'targetFileNameWithoutExtension';

    public function __construct(
        private readonly ResourceFactory $resourceFactory,
        private readonly LanguageServiceFactory $languageServiceFactory,
        private readonly LoggerInterface $logger,
    ) {
        // @todo Remove method call and method when TYPO3 v12 support is dropped.
        //       https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/12.0/Deprecation-94117-RegisterExtbaseTypeConvertersAsServices.html
        $this->configureProperties();
    }

    /**
     * Actually convert from $source to $targetType, taking into account the fully
     * built $convertedChildProperties and $configuration.
     *
     * @param UploadedFile $source
     * @param array<string, mixed> $convertedChildProperties
     */
    public function convertFrom(
        $source,
        string $targetType,
        array $convertedChildProperties = [],
        ?PropertyMappingConfigurationInterface $configuration = null
    ): Error|ExtbaseFileReference|null {
        if (! $source instanceof UploadedFile) {
            throw new \RuntimeException(
                sprintf(
                    '$source must be instance of "%s"',
                    UploadedFile::class,
                ),
                1756381290,
            );
        }
        $uploadedFileInformation = $source;
        $targetFolderIdentifier = '1:user_upload/';
        $maxFileSize = PHP_INT_MAX . 'B';
        $allowedMimeTypes = '';
        $targetFileNameWithoutExtension = null;
        if ($configuration !== null) {
            $targetFolderIdentifier = $configuration->getConfigurationValue(
                self::class,
                self::CONFIGURATION_UPLOAD_FOLDER,
            );
            $maxFileSize = $configuration->getConfigurationValue(
                self::class,
                self::CONFIGURATION_VALIDATION_FILESIZE_MAXIMUM,
            );
            $allowedMimeTypes = $configuration->getConfigurationValue(
                self::class,
                self::CONFIGURATION_VALIDATION_MIME_TYPE_ALLOWED_MIME_TYPES,
            );
            $targetFileNameWithoutExtension = $configuration->getConfigurationValue(
                self::class,
                self::CONFIGURATION_TARGET_FILE_NAME_WITHOUT_EXTENSION
            );
        }

        if ($uploadedFileInformation->getError() !== \UPLOAD_ERR_OK) {
            return GeneralUtility::makeInstance(
                Error::class,
                $this->getUploadErrorMessage($uploadedFileInformation->getError()),
                1756373245,
            );
        }

        if ($uploadedFileInformation->getClientFilename() === null) {
            return null;
        }
        $targetFileName = $uploadedFileInformation->getClientFilename();
        if (is_string($targetFileNameWithoutExtension) && $targetFileNameWithoutExtension !== '') {
            $targetFileName = strtolower(sprintf(
                '%s.%s',
                $targetFileNameWithoutExtension,
                pathinfo($uploadedFileInformation->getClientFilename(), PATHINFO_EXTENSION),
            ));
        }

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
     * @throws TypeConverterException
     */
    private function importUploadedResource(
        UploadedFile $uploadedFileInformation,
        string $targetFolderIdentifier,
        ?string $targetFileName
    ): ExtbaseFileReference {
        if (!GeneralUtility::makeInstance(FileNameValidator::class)->isValid((string)$uploadedFileInformation->getClientFilename())) {
            throw new TypeConverterException('Uploading files with PHP file extensions is not allowed!', 1753712929);
        }

        $targetFolder = $this->getOrCreateTargetFolder($targetFolderIdentifier);
        /** @var File $uploadedFile */
        $uploadedFile = $targetFolder->getStorage()->addUploadedFile(
            $uploadedFileInformation,
            $targetFolder,
            $targetFileName,
            DuplicationBehavior::REPLACE
        );

        return $this->createFileReferenceFromFalFileObject($uploadedFile);
    }

    /**
     * @throws TypeConverterException
     */
    private function validateUploadedFile(UploadedFile $uploadedFileInformation, string $maxFileSize, string $allowedMimeTypes): void
    {
        $languageService = $this->getLanguageService();
        $maxFileSizeInBytes = GeneralUtility::getBytesFromSizeMeasurement($maxFileSize);
        $allowedMimeTypesArray = GeneralUtility::trimExplode(',', $allowedMimeTypes);

        if ($uploadedFileInformation->getSize() > $maxFileSizeInBytes) {
            throw new TypeConverterException(
                $languageService->sL(
                    'LLL:EXT:academic_base/Resources/Private/Language/locallang.xlf:upload.error.150530345'
                ),
                1756373506,
            );
        }

        if (!in_array($uploadedFileInformation->getClientMediaType(), $allowedMimeTypesArray, true)) {
            throw new TypeConverterException(
                sprintf(
                    $languageService->sL(
                        'LLL:EXT:academic_base/Resources/Private/Language/locallang.xlf:validation.error.1471708998'
                    ),
                    $uploadedFileInformation->getClientMediaType()
                ),
                1756373500,
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
                'Missing target upload folder "uploadFolder"  in TypeConverter configuration',
                1756373407,
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
                    1756373399,
                );
            }
        }

        if (!$uploadFolder instanceof Folder) {
            throw new TypeConverterException(
                sprintf('Target upload folder "%s" is not accessible', $targetFolderIdentifier),
                1756373390,
            );
        }

        return $uploadFolder;
    }

    private function createFileReferenceFromFalFileReferenceObject(CoreFileReference $falFileReference): ExtbaseFileReference
    {
        $fileReference = GeneralUtility::makeInstance(ExtbaseFileReference::class);
        $fileReference->setOriginalResource($falFileReference);
        return $fileReference;
    }

    private function createFileReferenceFromFalFileObject(File $file): ExtbaseFileReference
    {
        $fileReference = $this->resourceFactory->createFileReferenceObject(
            [
                'uid_local' => $file->getUid(),
                'uid_foreign' => StringUtility::getUniqueId('NEW_'),
                'uid' => StringUtility::getUniqueId('NEW_'),
                'crop' => null,
            ]
        );

        return $this->createFileReferenceFromFalFileReferenceObject($fileReference);
    }

    /**
     * Returns a human-readable message for the given PHP file upload error
     * constant.
     */
    protected function getUploadErrorMessage(int $errorCode): string
    {
        $languageService = $this->getLanguageService();
        switch ($errorCode) {
            case \UPLOAD_ERR_INI_SIZE:
                $this->logger->error('The uploaded file exceeds the upload_max_filesize directive in php.ini.');
                return $languageService->sL('LLL:EXT:academic_base/Resources/Private/Language/locallang.xlf:upload.error.150530345');
            case \UPLOAD_ERR_FORM_SIZE:
                $this->logger->error('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.');
                return $languageService->sL('LLL:EXT:academic_base/Resources/Private/Language/locallang.xlf:upload.error.150530345');
            case \UPLOAD_ERR_PARTIAL:
                $this->logger->error('The uploaded file was only partially uploaded.');
                return $languageService->sL('LLL:EXT:academic_base/Resources/Private/Language/locallang.xlf:upload.error.150530346');
            case \UPLOAD_ERR_NO_FILE:
                $this->logger->error('No file was uploaded.');
                return $languageService->sL('LLL:EXT:academic_base/Resources/Private/Language/locallang.xlf:upload.error.150530347');
            case \UPLOAD_ERR_NO_TMP_DIR:
                $this->logger->error('Missing a temporary folder.');
                return $languageService->sL('LLL:EXT:academic_base/Resources/Private/Language/locallang.xlf:upload.error.150530348');
            case \UPLOAD_ERR_CANT_WRITE:
                $this->logger->error('Failed to write file to disk.');
                return $languageService->sL('LLL:EXT:academic_base/Resources/Private/Language/locallang.xlf:upload.error.150530348');
            case \UPLOAD_ERR_EXTENSION:
                $this->logger->error('File upload stopped by extension.');
                return $languageService->sL('LLL:EXT:academic_base/Resources/Private/Language/locallang.xlf:upload.error.150530348');
            default:
                $this->logger->error('Unknown upload error.');
                return $languageService->sL('LLL:EXT:academic_base/Resources/Private/Language/locallang.xlf:upload.error.150530348');
        }
    }

    private function getLanguageService(): LanguageService
    {
        $request = ($GLOBALS['TYPO3_REQUEST'] ?? null);
        if ($request instanceof ServerRequestInterface && $request->getAttribute('language') instanceof SiteLanguage) {
            return $this->languageServiceFactory->createFromSiteLanguage($request->getAttribute('language'));
        }
        if ($request instanceof ServerRequestInterface && $request->getAttribute('site') instanceof Site) {
            return $this->languageServiceFactory->createFromSiteLanguage($request->getAttribute('site')->getDefaultLanguage());
        }
        return $this->languageServiceFactory->create('default');
    }

    /**
     * Set deprecated properties for TYPO3 v12 in cases some code will request deprecated methods to retrieve the
     * information. Properties are now configured within `EXT:academic_base/Configuration/Services.yaml`.
     *
     * @todo Remove method together with call in {@see self::__construct()} when TYPO3 v12 support is dropped.
     *       https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/12.0/Deprecation-94117-RegisterExtbaseTypeConvertersAsServices.html
     */
    private function configureProperties(): void
    {
        if ((new Typo3Version())->getMajorVersion() < 13) {
            $this->sourceTypes = ['array'];
            $this->targetType = ExtbaseFileReference::class;
            $this->priority = 10;
        }
    }
}
