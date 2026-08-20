<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons_edit" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersonsEdit\Controller;

use Psr\Http\Message\ResponseInterface;
use Throwable;
use UnexpectedValueException;
use FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContext;
use FGTCLB\AcademicPersons\{
    Domain\Model\Profile,
    Domain\Repository\ProfileRepository
};
use FGTCLB\AcademicPersonsEdit\{
    Domain\Factory\ProfileFactory,
    Service\ProfileUpdateRequestService,
    Service\ProfileUpdateValidationService,
    Service\ProfileGenderOptionsService
};
use TYPO3\CMS\Core\{
    Utility\GeneralUtility,
    Database\Connection,
    Database\ConnectionPool,
    Database\Query\Restriction\DeletedRestriction,
    Http\RedirectResponse,
    Http\JsonResponse,
    Log\LogManager,
    Resource\Exception\FileDoesNotExistException,
    Resource\File,
    Resource\ResourceFactory
};
use TYPO3\CMS\Extbase\{
    Mvc\Controller\FileUploadConfiguration,
    Validation\Validator\FileSizeValidator,
    Validation\Validator\MimeTypeValidator
};

/**
 * @internal to be used only in `EXT:academic_person_edit` and not part of public API.
 */
final class InlineProfileController extends AbstractActionController
{
    public function __construct(
        private readonly ProfileFactory $profileFactory,
        private readonly ProfileRepository $profileRepository,
        private readonly ResourceFactory $resourceFactory,
        private readonly ProfileUpdateRequestService $profileUpdateRequestService,
        private readonly ProfileUpdateValidationService $profileUpdateValidationService,
        private readonly ProfileGenderOptionsService $profileGenderOptionsService,
    ) {}

    public function initializeAction(): void
    {
        // The JSON update endpoint performs its own authentication check so it
        // can return a machine-readable 401 response instead of the HTML error
        // response propagated by the shared controller initialization.
        if ($this->request->getControllerActionName() === 'update') {
            return;
        }

        parent::initializeAction();
    }

    // =================================================================================================================
    // Initial display of profile data, either for editing or inline display
    // =================================================================================================================

    public function indexAction(): ResponseInterface
    {
        $frontendUserId = (int) $this->context->getPropertyFromAspect('frontend.user', 'id', 0);
        $profile = $this->profileRepository->findByIdentifier($frontendUserId);
        $this->view->assignMultiple([
            'data' => $this->getCurrentContentObjectRenderer()?->data,
            'record' => $this->getCurrentContentRecord($this->getCurrentContentObjectRenderer()),
            'profile' => $profile,
            'genderOptions' => $this->profileGenderOptionsService->getAllowedValues(),
            'validations' => $this->academicPersonsSettings->getValidationSetWithFallback('profile')->validations,
        ]);
        return $this->htmlResponse();
    }

    // =================================================================================================================
    // Update profile data
    // =================================================================================================================
    /**
     * JSON
     *
     * Expected:
     * {
     *   "profile": 123,
     *   "data": {
     *     "firstName": "Max",
     *     "website": "",
     *     .......
     *   }
     * }
     *
     * Property update rules:
     * - missing property: keep current value
     * - property with "": clear current value
     * - property with value: replace current value
     */
    public function updateAction(): ResponseInterface
    {
        $requestResult = $this->profileUpdateRequestService->validate(
            $this->request
        );
        if (!$requestResult->isValid()) {
            return $this->jsonError(
                $requestResult->getError() ?? 'invalid_request',
                $requestResult->getStatusCode(),
            );
        }
        $payload = $requestResult->getPayload();
        $profile = $requestResult->getProfile();
        if ($payload === null || $profile === null) {
            return $this->jsonError('internal_server_error', 500);
        }
        try {
            $pluginControllerActionContext = new PluginControllerActionContext(
                $this->request,
                $this->settings,
            );
            $profileFormData = $this->profileUpdateValidationService->createFormData(
                $pluginControllerActionContext,
                $profile,
                $payload,
            );
            //@todo: Edit validator for links
            $validationResult = $this->profileUpdateValidationService->validate(
                $profileFormData,
            );
            if ($validationResult->hasErrors()) {
                $errors = [];
                foreach ($validationResult->getFlattenedErrors() as $propertyPath => $propertyErrors) {
                    foreach ($propertyErrors as $propertyError) {
                        $errors[$propertyPath][] = $propertyError->getMessage();
                    }
                }

                return $this->jsonError(
                    'validation_failed',
                    422,
                    'The submitted profile data is invalid.',
                    $errors,
                );
            }

            $updatedProfile = $this->profileFactory->updateFromFormData(
                $this->academicPersonsSettings->getValidationSetWithFallback('profile'),
                $profile,
                $profileFormData,
            );

            $this->profileRepository->update($updatedProfile);
            $this->persistenceManager->persistAll();

            return new JsonResponse([
                'success' => true,
                'profile' => $updatedProfile->getUid(),
                'data' => $payload->getData(),
            ]);
        } catch (UnexpectedValueException $exception) {
            return $this->jsonError(
                'invalid_profile_data',
                422,
                $exception->getMessage(),
            );
        } catch (Throwable $exception) {
            GeneralUtility::makeInstance(LogManager::class)
                ->getLogger(self::class)
                ->error('Updating the inline profile failed.', [
                    'exception' => $exception,
                ]);

            return $this->jsonError(
                'internal_server_error',
                500,
                'The profile could not be updated.',
            );
        }
    }

    /**
     * @param array<string, list<string>> $errors
     */
    private function jsonError(
        string $error,
        int $statusCode,
        ?string $message = null,
        array $errors = [],
    ): JsonResponse {
        $body = [
            'success' => false,
            'error' => $error,
        ];

        if ($message !== null) {
            $body['message'] = $message;
        }

        if ($errors !== []) {
            $body['errors'] = $errors;
        }

        return new JsonResponse($body, $statusCode);
    }


    // =================================================================================================================
    //  Handle entity translation
    // =================================================================================================================

    /*
    public function translateAction(int $profileUid, int $languageUid): ResponseInterface
    {
        $this->profileTranslator->translateTo($profileUid, $languageUid);

        return $this->redirectToProfileEditResponse();
    }
    */

    // =================================================================================================================
    //  Handle entity image operations
    // =================================================================================================================

    public function editImageAction(Profile $profile): ResponseInterface
    {
        $this->view->assignMultiple([
            'data' => $this->getCurrentContentObjectRenderer()?->data,
            'record' => $this->getCurrentContentRecord($this->getCurrentContentObjectRenderer()),
            'profile' => $profile,
            'cancelUrl' => $this->userSessionService->loadRefererFromSession($this->request),
        ]);
        return $this->htmlResponse();
    }

    public function initializeAddImageAction(): void
    {
        $this->configureImageFileUpload();
    }

    public function addImageAction(Profile $profile): ResponseInterface
    {
        // The file handling service already stored the uploaded file and rewired the profile
        // image property to it, so the replaced file can only be determined from the state
        // still persisted at this point - which the update below overwrites.
        $replacedImageFile = $this->getPersistedProfileImageFile($profile);

        $this->profileRepository->update($profile);
        $this->persistenceManager->persistAll();

        $this->deleteReplacedProfileImageFile($replacedImageFile, $profile);

        return new RedirectResponse($this->userSessionService->loadRefererFromSession($this->request), 303);
    }

    public function removeImageAction(Profile $profile): ResponseInterface
    {
        $image = $profile->getImage();
        if ($image !== null) {
            $imageFile = $image->getOriginalResource()->getOriginalFile();
            // The relation is dropped first, for two reasons: deleting the file alone leaves
            // the reference count on the profile record pointing at a reference that no longer
            // exists, and the file can only be checked for other usages once this profile does
            // not reference it any more.
            $profile->setImage(null);
            $this->profileRepository->update($profile);
            $this->persistenceManager->remove($image);
            $this->persistenceManager->persistAll();

            if ($this->countFileReferences($imageFile) === 0) {
                $imageFile->getStorage()->deleteFile($imageFile);
            }
        }
        return new RedirectResponse($this->userSessionService->loadRefererFromSession($this->request), 303);
    }

    public function toggleSkipSyncAction(Profile $profile): ResponseInterface
    {
        $profile->setSkipSync(!$profile->getSkipSync());
        $this->profileRepository->update($profile);
        $this->persistenceManager->persistAll();
        return new RedirectResponse($this->userSessionService->loadRefererFromSession($this->request), 303);
    }

    /**
     * Configures the native Extbase file upload handling for the profile image.
     *
     * The configuration is built here instead of using the `#[FileUpload]` attribute, because
     * upload folder and both validation limits are integrator configuration read from TypoScript
     * at runtime, which a static attribute cannot provide - and the attribute would have to be
     * placed on the `Profile` persistence model of `EXT:academic_persons`.
     */
    private function configureImageFileUpload(): void
    {
        $profileArgument = $this->arguments->getArgument('profile');

        $fileUploadConfiguration = (new FileUploadConfiguration('image'))
            // The profile holds a single image, but the limit is validated against the already
            // referenced file plus the upload. Allowing two therefore means "replace", which is
            // what this form does - the file handling service repoints the existing reference to
            // the uploaded file, and `addImageAction()` cleans the replaced file up afterwards.
            // Registering a file deletion instead would delete the replaced file unconditionally,
            // even when another record still references it.
            ->setMaxFiles(2)
            ->setUploadFolder(
                (string) ($this->settings['editForm']['profileImage']['targetFolder'] ?? '1:/user_upload/')
            );

        $fileSizeValidator = GeneralUtility::makeInstance(FileSizeValidator::class);
        $fileSizeValidator->setOptions([
            'maximum' => (string) ($this->settings['editForm']['profileImage']['validation']['maxFileSize'] ?? PHP_INT_MAX . 'B'),
        ]);
        $fileUploadConfiguration->addValidator($fileSizeValidator);

        // An empty list means "no mime type restriction". `MimeTypeValidator` throws
        // for an empty `allowedMimeTypes` option, so it is only added when configured.
        $allowedMimeTypes = GeneralUtility::trimExplode(
            ',',
            (string) ($this->settings['editForm']['profileImage']['validation']['allowedMimeTypes'] ?? ''),
            true
        );
        if ($allowedMimeTypes !== []) {
            $mimeTypeValidator = GeneralUtility::makeInstance(MimeTypeValidator::class);
            $mimeTypeValidator->setOptions(['allowedMimeTypes' => $allowedMimeTypes]);
            $fileUploadConfiguration->addValidator($mimeTypeValidator);
        }

        $profileArgument->getFileHandlingServiceConfiguration()
            ->addFileUploadConfiguration($fileUploadConfiguration);
        // The upload is handled by the file handling service, not by the property mapper.
        $profileArgument->getPropertyMappingConfiguration()->skipProperties('image');
    }

    /**
     * Returns the file currently referenced as profile image according to the database.
     *
     * Reading the persisted state instead of the mapped object is intentional: the in-memory
     * profile already carries the newly uploaded file when an upload action is processed.
     */
    private function getPersistedProfileImageFile(Profile $profile): ?File
    {
        $profileUid = $profile->getUid();
        if ($profileUid === null) {
            return null;
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file_reference');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $fileUid = (int) $queryBuilder
            ->select('uid_local')
            ->from('sys_file_reference')
            ->where(
                $queryBuilder->expr()->eq(
                    'tablenames',
                    $queryBuilder->createNamedParameter('tx_academicpersons_domain_model_profile')
                ),
                $queryBuilder->expr()->eq(
                    'fieldname',
                    $queryBuilder->createNamedParameter('image')
                ),
                $queryBuilder->expr()->eq(
                    'uid_foreign',
                    $queryBuilder->createNamedParameter($profileUid, Connection::PARAM_INT)
                ),
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchOne();
        if ($fileUid <= 0) {
            return null;
        }

        try {
            return $this->resourceFactory->getFileObject($fileUid);
        } catch (FileDoesNotExistException) {
            return null;
        }
    }

    /**
     * Removes the file a profile image upload replaced.
     *
     * The native file upload handling generates the stored file name and therefore always adds
     * a new file instead of overwriting the previous one, so it has to be cleaned up explicitly
     * to avoid orphaned files piling up in the upload folder with every re-upload.
     */
    private function deleteReplacedProfileImageFile(?File $replacedImageFile, Profile $profile): void
    {
        if ($replacedImageFile === null) {
            return;
        }
        $currentImageFile = $profile->getImage()?->getOriginalResource()->getOriginalFile();
        if ($currentImageFile !== null && $currentImageFile->getUid() === $replacedImageFile->getUid()) {
            // The upload did not result in a new file, nothing was replaced.
            return;
        }
        if ($this->countFileReferences($replacedImageFile) > 0) {
            // Still referenced elsewhere, for example by a content element or another record.
            return;
        }
        $replacedImageFile->getStorage()->deleteFile($replacedImageFile);
    }

    private function countFileReferences(File $file): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file_reference');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        return (int) $queryBuilder
            ->count('uid')
            ->from('sys_file_reference')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_local',
                    $queryBuilder->createNamedParameter($file->getUid(), Connection::PARAM_INT)
                ),
            )
            ->executeQuery()
            ->fetchOne();
    }

    /**
     * @return array<int<0, max>, array{
     *      label: string,
     *      labelTranslationIdentifier: string,
     *      value: string,
     *  }>
     * @todo Evaluating TCA in frontend for available options is a hard task to do correctly requiring to execute
     *       TCA item proc functions and so on. It also does not account for eventually FormEngine nodes processing
     *       additional stuff. Current implementation takes only directly added TCA items into account to show them
     *       as valid select options.
     * @todo Use TcaSchema for TYPO3 v13, either as dual version OR when dropping TYPO3 v12 support.
     */
    private function getAvailableGenderSelectItems(): array
    {
        $items = [];
        foreach ($GLOBALS['TCA']['tx_academicpersons_domain_model_profile']['columns']['gender']['config']['items'] ?? [] as $item) {
            $itemValue = (string) ($item['value'] ?? '');
            if ($itemValue === '') {
                // Skip empty string values, handled with `<f:form.select prependOptionLabel="---" />`
                // in the fluid template.
                continue;
            }
            $labelIdentifier = (string) ($item['label'] ?? '');
            $items[] = [
                'label' => ($this->localizationUtility->translate(
                    $labelIdentifier,
                    'persons_edit',
                ) ?? $labelIdentifier) ?: $labelIdentifier,
                'labelTranslationIdentifier' => $labelIdentifier,
                'value' => $itemValue,
            ];
        }
        return $items;
    }
}
