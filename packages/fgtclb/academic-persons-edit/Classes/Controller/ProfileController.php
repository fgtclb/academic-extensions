<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons_edit" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersonsEdit\Controller;

use FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContext;
use FGTCLB\AcademicPersons\Domain\Model\Profile;
use FGTCLB\AcademicPersons\Domain\Repository\ProfileRepository;
use FGTCLB\AcademicPersons\Event\AfterProfileUpdateEvent;
use FGTCLB\AcademicPersons\Service\ProfileImageMetadataService;
use FGTCLB\AcademicPersonsEdit\Domain\Factory\ProfileFactory;
use FGTCLB\AcademicPersonsEdit\Domain\Factory\ProfileFormDataFactoryInterface;
use FGTCLB\AcademicPersonsEdit\Domain\Model\Dto\ProfileFormData;
use FGTCLB\AcademicPersonsEdit\Domain\Validator\ProfileFormDataValidator;
use FGTCLB\AcademicPersonsEdit\Service\LocalizedProfileUidResolver;
use FGTCLB\AcademicPersonsEdit\Service\ProfileImageRelationWriter;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\FileUploadConfiguration;
use TYPO3\CMS\Extbase\Validation\Validator\FileSizeValidator;
use TYPO3\CMS\Extbase\Validation\Validator\MimeTypeValidator;

/**
 * @internal to be used only in `EXT:academic_person_edit` and not part of public API.
 */
final class ProfileController extends AbstractActionController
{
    public function __construct(
        private readonly ProfileFactory $profileFactory,
        private readonly ProfileRepository $profileRepository,
        private readonly ProfileFormDataFactoryInterface $profileFormDataFactory,
        private readonly ResourceFactory $resourceFactory,
        private readonly ProfileImageMetadataService $profileImageMetadataService,
        private readonly LocalizedProfileUidResolver $localizedProfileUidResolver,
        private readonly ProfileImageRelationWriter $profileImageRelationWriter,
    ) {}

    // =================================================================================================================
    // Handle readonly display like list forms and detail view
    // =================================================================================================================

    public function listAction(): ResponseInterface
    {
        $profiles = $this->profileRepository->findByFrontendUser(
            $this->context->getPropertyFromAspect('frontend.user', 'id')
        );

        $this->userSessionService->saveRefererToSession($this->request);

        $this->view->assignMultiple([
            'data' => $this->getCurrentContentObjectRenderer()?->data,
            'record' => $this->getCurrentContentRecord($this->getCurrentContentObjectRenderer()),
            'profiles' => $profiles,
        ]);

        return $this->htmlResponse();
    }

    public function showAction(Profile $profile): ResponseInterface
    {
        $pluginControllerActionContext = new PluginControllerActionContext($this->request, $this->settings);
        $cancelUrl = $this->uriBuilder->reset()->uriFor(
            'list',
        );
        $this->userSessionService->saveRefererToSession($this->request);
        $this->view->assignMultiple([
            'data' => $this->getCurrentContentObjectRenderer()?->data,
            'record' => $this->getCurrentContentRecord($this->getCurrentContentObjectRenderer()),
            'profile' => $profile,
            'profileFormData' => $this->profileFormDataFactory->createFromProfile($pluginControllerActionContext, $profile),
            'cancelUrl' => $cancelUrl,
        ]);
        return $this->htmlResponse();
    }

    // =================================================================================================================
    // Handle entity changes like displaying edit form and edit persistence.
    // =================================================================================================================

    public function editAction(Profile $profile): ResponseInterface
    {
        $pluginControllerActionContext = new PluginControllerActionContext($this->request, $this->settings);
        $this->view->assignMultiple([
            'data' => $this->getCurrentContentObjectRenderer()?->data,
            'record' => $this->getCurrentContentRecord($this->getCurrentContentObjectRenderer()),
            'profile' => $profile,
            'profileFormData' => $this->profileFormDataFactory->createFromProfile($pluginControllerActionContext, $profile),
            'genderOptions' => $this->getAvailableGenderSelectItems(),
            'cancelUrl' => $this->userSessionService->loadRefererFromSession($this->request),
            'validations' => $this->academicPersonsSettings->getProfileValidationSet()->validations,
        ]);
        return $this->htmlResponse();
    }

    public function initializeUpdateAction(): void
    {
        $this->addArgumentValidator('profileFormData', ProfileFormDataValidator::class);
    }

    public function updateAction(Profile $profile, ProfileFormData $profileFormData): ResponseInterface
    {
        $this->profileRepository->update(
            $this->profileFactory->updateFromFormData(
                $this->academicPersonsSettings->getProfileValidationSet(),
                $profile,
                $profileFormData,
            ),
        );
        $this->persistAndDispatchProfileUpdate($profile);

        $this->addTranslatedSuccessMessage('profile.update.success');

        if ($this->request->hasArgument('submit')
            && $this->request->getArgument('submit') === 'save-and-close'
        ) {
            return new RedirectResponse($this->userSessionService->loadRefererFromSession($this->request), 303);
        }
        return $this->createFormPersistencePrgRedirect('edit', ['profile' => $profile]);
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
        $uploadedImageFile = $profile->getImage()?->getOriginalResource()->getOriginalFile();
        if ($uploadedImageFile === null) {
            throw new \UnexpectedValueException('The uploaded profile image is unavailable.');
        }
        $persistedProfileUid = $this->resolvePersistedProfileUid($profile);
        try {
            $replacedFileUids = $this->profileImageRelationWriter->replace(
                $persistedProfileUid,
                $uploadedImageFile,
            );
            if (!$profile->getIsTranslation()) {
                $this->eventDispatcher->dispatch(new AfterProfileUpdateEvent($profile));
            }
            $this->profileImageMetadataService->updateForProfileUid($persistedProfileUid);
            $this->deleteUnreferencedFiles($replacedFileUids, $uploadedImageFile->getUid());
        } catch (\Throwable $exception) {
            if ($this->countFileReferences($uploadedImageFile) === 0) {
                $uploadedImageFile->getStorage()->deleteFile($uploadedImageFile);
            }
            throw $exception;
        }

        return new RedirectResponse($this->userSessionService->loadRefererFromSession($this->request), 303);
    }

    public function removeImageAction(Profile $profile): ResponseInterface
    {
        $removedFileUids = $this->profileImageRelationWriter->remove(
            $this->resolvePersistedProfileUid($profile),
        );
        $this->deleteUnreferencedFiles($removedFileUids);
        return new RedirectResponse($this->userSessionService->loadRefererFromSession($this->request), 303);
    }

    public function toggleSkipSyncAction(Profile $profile): ResponseInterface
    {
        $profile->setSkipSync(!$profile->getSkipSync());
        $this->profileRepository->update($profile);
        // `skip_sync` gates the fe_users to profile data synchronisation, not the
        // translation synchronisation - toggling it is a profile change like any other.
        $this->persistAndDispatchProfileUpdate($profile);
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
            // what this form does - `addImageAction()` assigns the uploaded file through
            // DataHandler and cleans the replaced file up afterwards.
            // Registering a file deletion instead would delete the replaced file unconditionally,
            // even when another record still references it.
            ->setMaxFiles(2)
            ->setUploadFolder(
                (string)($this->settings['editForm']['profileImage']['targetFolder'] ?? '1:/user_upload/')
            );

        $fileSizeValidator = GeneralUtility::makeInstance(FileSizeValidator::class);
        $fileSizeValidator->setOptions([
            'maximum' => (string)($this->settings['editForm']['profileImage']['validation']['maxFileSize'] ?? PHP_INT_MAX . 'B'),
        ]);
        $fileUploadConfiguration->addValidator($fileSizeValidator);

        // An empty list means "no mime type restriction". `MimeTypeValidator` throws
        // for an empty `allowedMimeTypes` option, so it is only added when configured.
        $allowedMimeTypes = GeneralUtility::trimExplode(
            ',',
            (string)($this->settings['editForm']['profileImage']['validation']['allowedMimeTypes'] ?? ''),
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

    private function resolvePersistedProfileUid(Profile $profile): int
    {
        $siteLanguage = $this->request->getAttribute('language');
        $languageId = $siteLanguage instanceof SiteLanguage ? $siteLanguage->getLanguageId() : 0;
        return $this->localizedProfileUidResolver->resolve((int)($profile->getUid() ?? 0), $languageId);
    }

    /**
     * @param list<int> $fileUids
     */
    private function deleteUnreferencedFiles(array $fileUids, int $retainedFileUid = 0): void
    {
        foreach (array_unique($fileUids) as $fileUid) {
            if ($fileUid <= 0 || $fileUid === $retainedFileUid) {
                continue;
            }
            try {
                $file = $this->resourceFactory->getFileObject($fileUid);
                if ($this->countFileReferences($file) === 0) {
                    $file->getStorage()->deleteFile($file);
                }
            } catch (FileDoesNotExistException) {
                // A stale relation may already have pointed to a missing file.
            }
        }
    }

    private function countFileReferences(File $file): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file_reference');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        return (int)$queryBuilder
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
            $itemValue = (string)($item['value'] ?? '');
            if ($itemValue === '') {
                // Skip empty string values, handled with `<f:form.select prependOptionLabel="---" />`
                // in the fluid template.
                continue;
            }
            $labelIdentifier = (string)($item['label'] ?? '');
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
