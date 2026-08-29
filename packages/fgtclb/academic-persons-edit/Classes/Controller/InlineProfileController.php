<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons_edit" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersonsEdit\Controller;

use DateTime;
use FGTCLB\AcademicPersons\Domain\Model\Contract;
use FGTCLB\AcademicPersons\Domain\Model\FunctionType;
use FGTCLB\AcademicPersons\Domain\Model\Location;
use FGTCLB\AcademicPersons\Domain\Model\OrganisationalUnit;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use UnexpectedValueException;
use FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContext;
use FGTCLB\AcademicPersons\Domain\Model\Profile;
use FGTCLB\AcademicPersons\Domain\Model\ProfileInformation;
use FGTCLB\AcademicPersons\Domain\Repository\ContractRepository;
use FGTCLB\AcademicPersons\Domain\Repository\FunctionTypeRepository;
use FGTCLB\AcademicPersons\Domain\Repository\LocationRepository;
use FGTCLB\AcademicPersons\Domain\Repository\OrganisationalUnitRepository;
use FGTCLB\AcademicPersons\Domain\Repository\ProfileRepository;
use FGTCLB\AcademicPersons\Domain\Repository\ProfileInformationRepository;
use FGTCLB\AcademicPersons\Settings\DocumentSection;
use FGTCLB\AcademicPersons\Settings\Validation;
use FGTCLB\AcademicPersonsEdit\Attributes\ListSortingMode;
use FGTCLB\AcademicPersonsEdit\Domain\Factory\ContractFactory;
use FGTCLB\AcademicPersonsEdit\Domain\Factory\ProfileFactory;
use FGTCLB\AcademicPersonsEdit\Domain\Factory\ProfileInformationFactory;
use FGTCLB\AcademicPersonsEdit\Domain\Model\Dto\AbstractFormData;
use FGTCLB\AcademicPersonsEdit\Domain\Model\Dto\ContractFormData;
use FGTCLB\AcademicPersonsEdit\Domain\Model\Dto\ProfileInformationFormData;
use FGTCLB\AcademicPersonsEdit\Service\ProfileDocumentSectionProvider;
use FGTCLB\AcademicPersonsEdit\Service\ProfileRichTextSanitizerInterface;
use FGTCLB\AcademicPersonsEdit\Service\ProfileUpdateRequestService;
use FGTCLB\AcademicPersonsEdit\Service\ProfileUpdateValidationService;
use FGTCLB\AcademicPersonsEdit\Service\ProfileFieldOptionsService;
use FGTCLB\AcademicPersonsEdit\Service\ProfileSectionProvider;
use FGTCLB\AcademicPersonsEdit\Service\RichTextCharacterCounter;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Index\MetaDataRepository;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Extbase\Mvc\Controller\FileUploadConfiguration;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface;
use TYPO3\CMS\Extbase\Validation\Validator\FileSizeValidator;
use TYPO3\CMS\Extbase\Validation\Validator\MimeTypeValidator;

/**
 * Controller for inline profile editing via JSON-based frontend requests.
 *
 * This controller handles profile actions such as validation,
 * updates and data retrieval without relying on the shared HTML authentication
 * flow used by regular Extbase pages.
 *
 * @internal This controller is intentionally internal to `EXT:academic_person_edit`
 *           and is not part of the public API.
 */
final class InlineProfileController extends AbstractActionController
{
    public function __construct(
        private readonly ProfileFactory $profileFactory,
        private readonly ProfileRepository $profileRepository,
        private readonly ResourceFactory $resourceFactory,
        private readonly ProfileUpdateRequestService $profileUpdateRequestService,
        private readonly ProfileUpdateValidationService $profileUpdateValidationService,
        private readonly ProfileFieldOptionsService $profileFieldOptionsService,
        private readonly ProfileSectionProvider $profileSectionProvider,
        private readonly ProfileDocumentSectionProvider $profileDocumentSectionProvider,
        private readonly ContractFactory $contractFactory,
        private readonly ContractRepository $contractRepository,
        private readonly ProfileInformationFactory $profileInformationFactory,
        private readonly ProfileInformationRepository $profileInformationRepository,
        private readonly FunctionTypeRepository $functionTypeRepository,
        private readonly OrganisationalUnitRepository $organisationalUnitRepository,
        private readonly LocationRepository $locationRepository,
        private readonly ProfileRichTextSanitizerInterface $profileRichTextSanitizer,
    ) {}

    /**
     * Initializes the controller without the shared HTML auth flow for JSON endpoints.
     *
     * These actions validate and answer their own requests as machine-readable
     * payloads, so they must bypass the extbase parent initialization to return
     * their own 401/422/403 responses instead of the generic HTML error page.
     */
    public function initializeAction(): void
    {
        if (in_array(
            $this->request->getControllerActionName(),
            [
                'update',
                'updateSkipSync',
                'deleteImage',
                'documentForm',
                'createDocument',
                'updateDocument',
                'deleteDocument',
                'sortDocument',
            ],
            true,
        )) {
            return;
        }
        parent::initializeAction();
    }

    /**
     * Converts validation failures from the image upload flow into a JSON error response.
     *
     * This keeps upload-related form validation machine-readable instead of falling
     * back to the generic HTML error handling from the parent controller.
     *
     * @return ResponseInterface The rendered response for the current error state.
     */
    protected function errorAction(): ResponseInterface
    {
        if ($this->request->getControllerActionName() === 'uploadImage') {
            throw new PropagateResponseException(
                $this->jsonError(
                    'validation_failed',
                    422,
                    $this->getFlattenedValidationErrorMessage(),
                ),
                1776760202,
            );
        }
        return parent::errorAction();
    }

    // =================================================================================================================
    // Assigned profile list and initial display of the selected profile
    // =================================================================================================================

    /**
     * Renders the list of profiles assigned to the currently authenticated frontend user.
     *
     * The method loads the user-related profiles, resolves the current site for
     * language labels and exposes the prepared data to the view template.
     *
     * @return ResponseInterface The rendered HTML response for the profile list.
     */
    public function listAction(): ResponseInterface
    {
        $profiles = $this->profileRepository->findByFrontendUser(
            (int)$this->context->getPropertyFromAspect('frontend.user', 'id', 0),
        );
        $site = $this->request->getAttribute('site');
        $this->view->assignMultiple([
            'data' => $this->getCurrentContentObjectRenderer()?->data,
            'record' => $this->getCurrentContentRecord($this->getCurrentContentObjectRenderer()),
            'profiles' => $profiles,
            'profileListItems' => $this->createProfileListItems(
                $profiles,
                $site instanceof Site ? $site : null,
            ),
        ]);
        return $this->htmlResponse();
    }

    /**
     * Displays the editable profile view for a given user profile ID.
     *
     * This action fetches the editable profile data, checks for existence,
     * and assigns necessary data structures to the view for rendering.
     *
     * @param int $profileUid The unique ID of the profile to edit.
     * @return ResponseInterface The HTML response containing the editable profile view.
     */
    public function indexAction(int $profileUid): ResponseInterface
    {
        $profile = $this->profileUpdateRequestService->findEditableProfile($profileUid);
        if ($profile === null) {
            $this->view->assign('profile', null);
            return $this->htmlResponse()->withStatus(403);
        }
        $this->view->assignMultiple([
            'data' => $this->getCurrentContentObjectRenderer()?->data,
            'record' => $this->getCurrentContentRecord($this->getCurrentContentObjectRenderer()),
            'profile' => $profile,
            'profileSections' => $this->profileSectionProvider->getSections(),
            'specialFields' => $this->profileSectionProvider->getSpecialFields(),
            'profileFieldOptions' => $this->profileFieldOptionsService->getOptionsByField(),
            'documentSections' => $this->profileDocumentSectionProvider->getSections($profile),
            'imageAllowedMimeTypes' => $this->resolveImageAllowedMimeTypes(),
        ]);
        return $this->htmlResponse();
    }

    /**
     * Resolves the allowed MIME types for profile image uploads.
     *
     * Falls back to the default browser-safe image formats when the settings are
     * missing, empty or not configured as a string.
     *
     * @return string A comma-separated list of allowed MIME types.
     */
    private function resolveImageAllowedMimeTypes(): string
    {
        $mimeTypes = $this->settings['editForm']['profileImage']['validation']['allowedMimeTypes'] ?? null;
        if (!is_string($mimeTypes) || trim($mimeTypes) === '') {
            return 'image/jpeg,image/png,image/webp';
        }
        return $mimeTypes;
    }

    /**
     * Builds the display list for the profile overview.
     *
     * Each entry contains the profile entity and the localized language label that
     * should be shown alongside it in the frontend list. Profiles without a
     * valid language mapping fall back to the generic "all languages" or
     * translated "unknown" label.
     *
     * @param iterable<Profile> $profiles The profiles assigned to the current frontend user.
     * @param Site|null $site The current site used to resolve language labels.
     * @return list<array{profile: Profile, language: string}> The normalized list items for the template.
     */
    private function createProfileListItems(iterable $profiles, ?Site $site): array
    {
        $languageLabels = [];
        foreach ($site?->getAllLanguages() ?? [] as $siteLanguage) {
            $languageLabels[$siteLanguage->getLanguageId()] = $siteLanguage->getTitle();
        }
        $items = [];
        foreach ($profiles as $profile) {
            if (!$profile instanceof Profile) {
                continue;
            }
            $languageUid = $profile->getLanguageUid();
            if ($languageUid === -1) {
                $language = LocalizationUtility::translate(
                    'list.language.all',
                    'academic_persons_edit',
                ) ?? 'All languages';
            } else {
                $language = $languageLabels[$languageUid]
                    ?? LocalizationUtility::translate(
                        'list.language.unknown',
                        'academic_persons_edit',
                        [$languageUid],
                    )
                    ?? sprintf('Language %d', $languageUid);
            }
            $items[] = [
                'profile' => $profile,
                'language' => $language,
            ];
        }
        return $items;
    }

    // =================================================================================================================
    // Handle entity profile operations
    // =================================================================================================================

    /**
     * Updates one or more profile fields through the inline JSON API.
     *
     * The request payload contains the profile identifier and a partial field map.
     * Missing properties keep the current value, empty strings clear the value,
     * and concrete values replace it. The method validates the payload and form
     * data before persisting the updated entity and returning the normalized data.
     *
     * @return ResponseInterface A JSON response with the updated profile UID and normalized values.
     */
    public function updateAction(): ResponseInterface
    {
        $requestResult = $this->profileUpdateRequestService->validate(
            $this->request
        );
        if (!$requestResult->isValid()) {
            $this->throwJsonError(
                $requestResult->getError() ?? 'invalid_request',
                $requestResult->getStatusCode(),
            );
        }
        $payload = $requestResult->getPayload();
        $profile = $requestResult->getProfile();
        if ($payload === null || $profile === null) {
            $this->throwJsonError('internal_server_error', 500);
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
                $this->throwJsonError(
                    'validation_failed',
                    422,
                    'The submitted profile data is invalid.',
                    $errors,
                );
            }
            $updatedProfile = $this->profileFactory->updateFromFormData(
                $this->academicPersonsSettings->getProfileUpdateValidationSet(),
                $profile,
                $profileFormData,
            );
            $this->profileRepository->update($updatedProfile);
            $this->persistenceManager->persistAll();
            return new JsonResponse([
                'success' => true,
                'profile' => $updatedProfile->getUid(),
                'data' => $this->profileUpdateValidationService->getNormalizedData(
                    $profileFormData,
                    $payload,
                ),
            ]);
        } catch (PropagateResponseException $exception) {
            throw $exception;
        } catch (UnexpectedValueException $exception) {
            $this->throwJsonError(
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
            $this->throwJsonError(
                'internal_server_error',
                500,
                'The profile could not be updated.',
            );
        }
    }

    /**
     * Updates the profile synchronization flag through its dedicated JSON endpoint.
     *
     * The endpoint accepts exactly one boolean field, skipSync, so the checkbox
     * can be persisted independently from other profile data without submitting
     * unrelated values. It validates the payload before storing the updated flag
     * and returning the persisted result.
     *
     * @return ResponseInterface A JSON response containing the updated profile UID and synchronization state.
     */
    public function updateSkipSyncAction(): ResponseInterface
    {
        $requestResult = $this->profileUpdateRequestService->validate(
            $this->request,
        );
        if (!$requestResult->isValid()) {
            $this->throwJsonError(
                $requestResult->getError() ?? 'invalid_request',
                $requestResult->getStatusCode(),
            );
        }
        $payload = $requestResult->getPayload();
        $profile = $requestResult->getProfile();
        if ($payload === null || $profile === null) {
            $this->throwJsonError('internal_server_error', 500);
        }
        $data = $payload->getData();
        if (
            array_keys($data) !== ['skipSync']
            || !is_bool($data['skipSync'])
        ) {
            $this->throwJsonError(
                'invalid_payload',
                400,
                'The payload must contain exactly one boolean skipSync value.',
            );
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
                $this->throwJsonError(
                    'validation_failed',
                    422,
                    'The submitted synchronization setting is invalid.',
                    $errors,
                );
            }
            $updatedProfile = $this->profileFactory->updateFromFormData(
                $this->academicPersonsSettings->getProfileUpdateValidationSet(),
                $profile,
                $profileFormData,
            );
            $this->profileRepository->update($updatedProfile);
            $this->persistenceManager->persistAll();
            return new JsonResponse([
                'success' => true,
                'profile' => $updatedProfile->getUid(),
                'skipSync' => $updatedProfile->getSkipSync(),
            ]);
        } catch (PropagateResponseException $exception) {
            throw $exception;
        } catch (UnexpectedValueException $exception) {
            $this->throwJsonError(
                'invalid_profile_data',
                422,
                $exception->getMessage(),
            );
        } catch (Throwable $exception) {
            GeneralUtility::makeInstance(LogManager::class)
                ->getLogger(self::class)
                ->error('Updating the inline profile synchronization flag failed.', [
                    'exception' => $exception,
                ]);
            $this->throwJsonError(
                'internal_server_error',
                500,
                'The synchronization setting could not be updated.',
            );
        }
    }

    // =================================================================================================================
    // Handle structured document sections through the InlineProfile JSON API
    // =================================================================================================================

    /**
     * Returns the field schema and current values for a structured document section.
     *
     * The request must contain a valid section identifier and a matching mode
     * ("add", "view", "edit" or "delete"). In add mode, no record UID is allowed;
     * in all other modes, a positive record UID must reference an existing
     * contract/profile information entry for the current profile.
     *
     * The JSON response contains the profile ID, section identifier, kind and the
     * full field definitions used by the frontend modal for creating or editing the
     * selected document record.
     *
     * @return ResponseInterface JSON response with the current form definition and record data.
     */
    public function documentFormAction(): ResponseInterface
    {
        try {
            [$profile, $section, $data] = $this->getDocumentRequest();
            $this->assertDocumentPayload($data, ['section', 'record', 'mode'], ['section', 'mode']);
            $mode = $this->getRequiredDocumentMode($data);
            $recordUid = $this->getOptionalPositiveInteger($data, 'record');
            if (($mode === 'add') !== ($recordUid === null)) {
                $this->throwJsonError('invalid_payload', 400, 'The document record does not match the requested mode.');
            }
            $this->assertDocumentActionAllowed($section, $mode);
            $record = $recordUid === null
                ? null
                : $this->findDocumentRecord($profile, $section, $recordUid);
            if ($recordUid !== null && $record === null) {
                $this->throwJsonError('document_not_found', 404);
            }
            return new JsonResponse([
                'success' => true,
                'profile' => $profile->getUid(),
                'section' => $section->identifier,
                'kind' => $section->isContractSection() ? 'contract' : 'profileInformation',
                'record' => $record?->getUid(),
                'fields' => $this->getDocumentFieldDefinitions($section, $record),
            ]);
        } catch (PropagateResponseException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            $this->handleDocumentFailure('Loading a structured document form failed.', $exception);
        }
    }

    /**
     * Creates a new structured document record for the given profile section.
     *
     * Validates the submitted payload, normalizes and validates the document fields,
     * creates the matching record type (contract or profile information), assigns
     * sorting and PID values, persists it, and returns the serialized result as JSON.
     *
     * @return ResponseInterface JSON response containing the created document item.
     * @throws PropagateResponseException If the response should be propagated without wrapping.
     * @throws \Throwable If the creation fails, the error is handled as a document failure.
     */
    public function createDocumentAction(): ResponseInterface
    {
        try {
            [$profile, $section, $data] = $this->getDocumentRequest();
            $this->assertDocumentPayload($data, ['section', 'fields'], ['section', 'fields']);
            $this->assertDocumentActionAllowed($section, 'add');
            $fields = $this->getSubmittedDocumentFields($data);
            $normalizedFields = $this->normalizeAndValidateDocumentFields(
                $section,
                $fields,
                true,
            );
            if ($section->isContractSection()) {
                $record = $this->contractFactory->createFromFormData(
                    $section->validationSet,
                    $profile,
                    $this->createContractFormData($normalizedFields),
                );
                $record->setSorting($this->getNextSortingValue($this->getDocumentRecords($profile, $section)));
                $record->setPid((int)$profile->getPid());
                $this->contractRepository->add($record);
            } else {
                $record = $this->profileInformationFactory->createFromFormData(
                    $section->validationSet,
                    $profile,
                    $this->createProfileInformationFormData($section, $normalizedFields),
                );
                $record->setSorting($this->getNextSortingValue($this->getDocumentRecords($profile, $section)));
                $record->setPid((int)$profile->getPid());
                $this->profileInformationRepository->add($record);
            }
            $this->persistenceManager->persistAll();
            return new JsonResponse([
                'success' => true,
                'profile' => $profile->getUid(),
                'section' => $section->identifier,
                'item' => $this->serializeDocumentItem($section, $record),
            ]);
        } catch (PropagateResponseException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            $this->handleDocumentFailure('Creating a structured document failed.', $exception);
        }
    }

    /**
     * Updates an existing structured document item for a profile section.
     *
     * Validates the submitted payload, resolves the target record, normalizes and
     * validates the changed fields, and persists the updated values for either a
     * contract or a profile information record.
     *
     * @return ResponseInterface JSON response containing the updated document item.
     * @throws PropagateResponseException If a response is intentionally propagated.
     * @throws Throwable If validation or persistence fails while updating the record.
     */
    public function updateDocumentAction(): ResponseInterface
    {
        try {
            [$profile, $section, $data] = $this->getDocumentRequest();
            $this->assertDocumentPayload(
                $data,
                ['section', 'record', 'fields'],
                ['section', 'record', 'fields'],
            );
            $this->assertDocumentActionAllowed($section, 'edit');
            $recordUid = $this->getRequiredPositiveInteger($data, 'record');
            $record = $this->findDocumentRecord($profile, $section, $recordUid);
            if ($record === null) {
                $this->throwJsonError('document_not_found', 404);
            }
            $normalizedFields = $this->normalizeAndValidateDocumentFields(
                $section,
                $this->getSubmittedDocumentFields($data),
                false,
            );
            if ($record instanceof Contract) {
                $this->contractRepository->update(
                    $this->contractFactory->updateFromFormData(
                        $section->validationSet,
                        $record,
                        $this->createContractFormData($normalizedFields),
                    ),
                );
            } else {
                $this->profileInformationRepository->update(
                    $this->profileInformationFactory->updateFromFormData(
                        $section->validationSet,
                        $record,
                        $this->createProfileInformationFormData($section, $normalizedFields),
                    ),
                );
            }
            $this->persistenceManager->persistAll();
            return new JsonResponse([
                'success' => true,
                'profile' => $profile->getUid(),
                'section' => $section->identifier,
                'item' => $this->serializeDocumentItem($section, $record),
            ]);
        } catch (PropagateResponseException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            $this->handleDocumentFailure('Updating a structured document failed.', $exception);
        }
    }

    /**
     * Deletes a single record from a structured document section.
     *
     * Validates the provided payload, ensures the section allows the delete
     * action, loads the requested record, and removes it from the matching
     * repository.
     *
     * @return ResponseInterface JSON response with the delete result.
     *
     * @throws PropagateResponseException If an error response should be
     *      propagated without being wrapped.
     * @throws Throwable If the delete operation fails unexpectedly.
     */
    public function deleteDocumentAction(): ResponseInterface
    {
        try {
            [$profile, $section, $data] = $this->getDocumentRequest();
            $this->assertDocumentPayload($data, ['section', 'record'], ['section', 'record']);
            $this->assertDocumentActionAllowed($section, 'delete');
            $recordUid = $this->getRequiredPositiveInteger($data, 'record');
            $record = $this->findDocumentRecord($profile, $section, $recordUid);
            if ($record === null) {
                $this->throwJsonError('document_not_found', 404);
            }
            if ($record instanceof Contract) {
                $this->contractRepository->remove($record);
            } else {
                $this->profileInformationRepository->remove($record);
            }
            $this->persistenceManager->persistAll();
            return new JsonResponse([
                'success' => true,
                'profile' => $profile->getUid(),
                'section' => $section->identifier,
                'deleted' => $recordUid,
            ]);
        } catch (PropagateResponseException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            $this->handleDocumentFailure('Deleting a structured document failed.', $exception);
        }
    }

    /**
     * Sorts the records of a structured document section.
     *
     * Supports reordering by a submitted complete order list as well as
     * moving a single record one step up or down within the section.
     *
     * @return ResponseInterface JSON response with the updated order and change state.
     * @throws PropagateResponseException When the request should be handled by the caller.
     * @throws \Throwable When the sorting operation fails unexpectedly.
     */
    public function sortDocumentAction(): ResponseInterface
    {
        try {
            [$profile, $section, $data] = $this->getDocumentRequest();
            if (array_key_exists('order', $data)) {
                $this->assertDocumentPayload($data, ['section', 'order'], ['section', 'order']);
                $this->assertDocumentActionAllowed($section, 'reorder');
                $process = $this->reorderDocumentRecords(
                    $this->getDocumentRecords($profile, $section),
                    $this->getSubmittedDocumentOrder($data),
                );
                return new JsonResponse([
                    'success' => true,
                    'profile' => $profile->getUid(),
                    'section' => $section->identifier,
                    'changed' => $process['changed'],
                    'order' => array_values(array_map(
                        static fn(Contract|ProfileInformation $record): int => (int)$record->getUid(),
                        $process['records'],
                    )),
                ]);
            }
            $this->assertDocumentPayload($data, ['section', 'record', 'direction'], ['section', 'record', 'direction']);
            $recordUid = $this->getRequiredPositiveInteger($data, 'record');
            if ($this->findDocumentRecord($profile, $section, $recordUid) === null) {
                $this->throwJsonError('document_not_found', 404);
            }
            $direction = $data['direction'];
            if (!is_string($direction) || !in_array($direction, ['up', 'down'], true)) {
                $this->throwJsonError('invalid_payload', 400, 'The direction must be up or down.');
            }
            $this->assertDocumentActionAllowed($section, $direction);
            $records = $this->getDocumentRecords($profile, $section);
            $process = $this->sortItems(
                $records,
                $recordUid,
                $direction === 'up' ? ListSortingMode::UP : ListSortingMode::DOWN,
            );
            return new JsonResponse([
                'success' => true,
                'profile' => $profile->getUid(),
                'section' => $section->identifier,
                'changed' => $process->changed,
                'order' => array_values(array_map(
                    static fn(Contract|ProfileInformation $record): int => (int)$record->getUid(),
                    $process->items,
                )),
            ]);
        } catch (PropagateResponseException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            $this->handleDocumentFailure('Sorting a structured document failed.', $exception);
        }
    }

    /**
     * Validates the current request payload and resolves the profile and document section.
     *
     * @return array{0: Profile, 1: DocumentSection, 2: array<string, mixed>}
     *   The validated profile, the resolved document section, and the request payload data.
     *
     * @throws \TYPO3\CMS\Core\Http\PropagateResponseException If the request validation pipeline propagates an exception.
     */
    private function getDocumentRequest(): array
    {
        $requestResult = $this->profileUpdateRequestService->validate($this->request);
        if (!$requestResult->isValid()) {
            $this->throwJsonError(
                $requestResult->getError() ?? 'invalid_request',
                $requestResult->getStatusCode(),
            );
        }
        $payload = $requestResult->getPayload();
        $profile = $requestResult->getProfile();
        if ($payload === null || $profile === null) {
            $this->throwJsonError('internal_server_error', 500);
        }
        $data = $payload->getData();
        $sectionIdentifier = $data['section'] ?? null;
        if (!is_string($sectionIdentifier) || $sectionIdentifier === '') {
            $this->throwJsonError('invalid_payload', 400, 'A document section is required.');
        }
        $section = $this->academicPersonsSettings->getDocumentSection($sectionIdentifier);
        if ($section === null) {
            $this->throwJsonError('unknown_document_section', 404);
        }
        return [$profile, $section, $data];
    }

    /**
     * Validates the document payload and ensures that only allowed keys are present
     * and all required keys are available.
     *
     * @param array<string, mixed> $data The payload to validate.
     * @param list<string> $allowedKeys A list of allowed property names.
     * @param list<string> $requiredKeys A list of property names that must be present.
     * @return void
     */
    private function assertDocumentPayload(array $data, array $allowedKeys, array $requiredKeys): void
    {
        foreach (array_keys($data) as $key) {
            if (!is_string($key) || !in_array($key, $allowedKeys, true)) {
                $this->throwJsonError('invalid_payload', 400, 'The document payload contains an unknown property.');
            }
        }
        foreach ($requiredKeys as $requiredKey) {
            if (!array_key_exists($requiredKey, $data)) {
                $this->throwJsonError('invalid_payload', 400, 'The document payload is incomplete.');
            }
        }
    }

    /**
     * Validates that a required property exists and is a positive integer.
     *
     * @param array<string, mixed> $data The payload to validate.
     * @param string $property The property name to read.
     * @return int The validated positive integer value.
     */
    private function getRequiredPositiveInteger(array $data, string $property): int
    {
        $value = $data[$property] ?? null;
        if (!is_int($value) || $value <= 0) {
            $this->throwJsonError('invalid_payload', 400, sprintf('%s must be a positive integer.', $property));
        }
        return $value;
    }

    /**
     * Gets an optional positive integer property from the payload.
     *
     * Returns null when the property is missing, null, or zero.
     *
     * @param array<string, mixed> $data The payload to validate.
     * @param string $property The property name to read.
     * @return int|null The validated positive integer value or null when not set.
     */
    private function getOptionalPositiveInteger(array $data, string $property): ?int
    {
        if (!array_key_exists($property, $data) || $data[$property] === null || $data[$property] === 0) {
            return null;
        }
        return $this->getRequiredPositiveInteger($data, $property);
    }

    /**
     * Gets the required document mode from the payload.
     *
     * Valid modes are: "add", "view", "edit", and "delete".
     *
     * @param array<string, mixed> $data The payload to validate.
     * @return 'add'|'view'|'edit'|'delete' The validated document mode.
     */
    private function getRequiredDocumentMode(array $data): string
    {
        $mode = $data['mode'] ?? null;
        if (!is_string($mode) || !in_array($mode, ['add', 'view', 'edit', 'delete'], true)) {
            $this->throwJsonError('invalid_payload', 400, 'The document mode is invalid.');
        }
        return $mode;
    }

    /**
     * Verifies that the requested action is allowed for the given document section.
     *
     * Some actions are mapped to specific section capabilities, such as creating
     * a new item or drag-and-drop reordering.
     *
     * @param DocumentSection $section The document section to validate.
     * @param string $action The action name to check, for example "add", "reorder",
     *                       or any action supported by the section.
     * @throws \RuntimeException Thrown when the action is not permitted for the section,
     *                           which results in a JSON error response.
     */
    private function assertDocumentActionAllowed(DocumentSection $section, string $action): void
    {
        $allowed = match ($action) {
            'add' => $section->allowsCreate(),
            'reorder' => $section->allowsDragSorting(),
            default => $section->allowsAction($action),
        };
        if (!$allowed) {
            $this->throwJsonError('document_action_not_allowed', 403, 'This action is not allowed for the document section.');
        }
    }

    /**
     * Validates and returns the submitted document field payload.
     *
     * @param array<string, mixed> $data The raw request payload.
     * @return array<string, mixed> The validated document fields as an associative array.
     * @throws \RuntimeException Thrown when the payload is invalid or field names are not strings,
     *                           which results in a JSON error response.
     */
    private function getSubmittedDocumentFields(array $data): array
    {
        $fields = $data['fields'] ?? null;
        if (!is_array($fields)) {
            $this->throwJsonError('invalid_payload', 400, 'Document fields must be a JSON object.');
        }
        foreach (array_keys($fields) as $fieldName) {
            if (!is_string($fieldName)) {
                $this->throwJsonError('invalid_payload', 400, 'Document field names must be strings.');
            }
        }
        return $fields;
    }

    /**
     * Validates and returns the submitted document order payload.
     *
     * @param array<string, mixed> $data The raw request payload.
     * @return list<int> The validated order as a list of positive integer record UIDs.
     * @throws \RuntimeException Thrown when the payload is invalid, which results in a JSON error response.
     */
    private function getSubmittedDocumentOrder(array $data): array
    {
        $order = $data['order'] ?? null;
        if (!is_array($order) || !array_is_list($order)) {
            $this->throwJsonError('invalid_payload', 400, 'The document order must be a JSON list.');
        }
        foreach ($order as $recordUid) {
            if (!is_int($recordUid) || $recordUid <= 0) {
                $this->throwJsonError('invalid_payload', 400, 'Every document order value must be a positive integer.');
            }
        }
        return $order;
    }

    /**
     * Finds a document record for the given profile and section by its UID.
     *
     * @param Profile $profile The profile whose document records should be searched.
     * @param DocumentSection $section The document section to inspect.
     * @param int $recordUid The UID of the record to find.
     * @return Contract|ProfileInformation|null The matching record, or null if no record with the given UID exists.
     */
    private function findDocumentRecord(
        Profile $profile,
        DocumentSection $section,
        int $recordUid,
    ): Contract|ProfileInformation|null {
        foreach ($this->getDocumentRecords($profile, $section) as $record) {
            if ((int)$record->getUid() === $recordUid) {
                return $record;
            }
        }
        return null;
    }

    /**
     * Returns all document records belonging to the provided profile and section.
     *
     * For contract sections, the method returns the profile's contract records.
     * For all other sections, it returns the corresponding profile information records.
     *
     * @param Profile $profile The profile whose records should be retrieved.
     * @param DocumentSection $section The section whose records should be inspected.
     * @return list<Contract|ProfileInformation> The matching records, filtered to valid record instances.
     */
    private function getDocumentRecords(Profile $profile, DocumentSection $section): array
    {
        if ($section->isContractSection()) {
            return array_values(array_filter(
                $profile->getContracts()->toArray(),
                static fn(mixed $record): bool => $record instanceof Contract,
            ));
        }
        return array_values(array_filter(
            $this->profileInformationRepository->findByProfileAndType($profile, $section->type)->toArray(),
            static fn(mixed $record): bool => $record instanceof ProfileInformation,
        ));
    }

    /**
     * Determines the next sorting value for a document record by taking the highest
     * existing sort value and adding a new increment.
     *
     * @param list<Contract|ProfileInformation> $records The records for which the next sorting position should be calculated.
     * @return int The next available sorting value, offset by 10 from the current maximum.
     */
    private function getNextSortingValue(array $records): int
    {
        $maximum = 0;
        foreach ($records as $record) {
            $maximum = max($maximum, $record->getSorting());
        }
        return $maximum + 10;
    }

    /**
     * Reorders document records to the submitted sequence and updates their sorting values.
     *
     * The submitted order is validated to contain each record exactly once. Records are
     * then assigned their new sorting position in increments of 10 and persisted when a
     * change is required.
     *
     * @param list<Contract|ProfileInformation> $records The records that should be reordered.
     * @param list<int> $order The desired order as a list of record UIDs.
     * @return array{changed: bool, records: list<Contract|ProfileInformation>} The reorder result including whether
     *     the sorting changed and the ordered records in their new sequence.
     */
    private function reorderDocumentRecords(array $records, array $order): array
    {
        $recordsByUid = [];
        foreach ($records as $record) {
            $recordsByUid[(int)$record->getUid()] = $record;
        }
        $currentOrder = array_keys($recordsByUid);
        $normalizedCurrentOrder = $currentOrder;
        $normalizedSubmittedOrder = $order;
        sort($normalizedCurrentOrder);
        sort($normalizedSubmittedOrder);
        if ($normalizedSubmittedOrder !== $normalizedCurrentOrder || count(array_unique($order)) !== count($order)) {
            $this->throwJsonError('invalid_payload', 400, 'The document order must contain every section record exactly once.');
        }
        $orderedRecords = [];
        $changed = false;
        foreach ($order as $position => $recordUid) {
            $record = $recordsByUid[$recordUid];
            $expectedSorting = ($position + 1) * 10;
            if ($record->getSorting() !== $expectedSorting) {
                $record->setSorting($expectedSorting);
                $this->persistenceManager->update($record);
                $changed = true;
            }
            $orderedRecords[] = $record;
        }
        if ($changed) {
            $this->persistenceManager->persistAll();
        }
        return ['changed' => $changed, 'records' => $orderedRecords];
    }

    /**
     * Builds the field definitions for a document section and applies validation,
     * help text and type overrides for the current record.
     *
     * @param DocumentSection $section The document section whose fields should be resolved.
     * @param Contract|ProfileInformation|null $record The current record used to populate field values.
     * @return list<array{
     *     name: string,
     *     label: string,
     *     type: string,
     *     required: bool,
     *     readOnly: bool,
     *     disabled: bool,
     *     richText: bool,
     *     characterLimit: int,
     *     helptext: string,
     *     columnClass: string,
     *     compactCheckbox: bool,
     *     value: mixed,
     *     displayValue: string,
     *     helptext: string,
     *     options: list<array{value: int|string, label: string}>
     * }>
     */
    private function getDocumentFieldDefinitions(
        DocumentSection $section,
        Contract|ProfileInformation|null $record,
    ): array {
        $definitions = $section->isContractSection()
            ? $this->getContractFieldDefinitions($record instanceof Contract ? $record : null)
            : $this->getProfileInformationFieldDefinitions(
                $record instanceof ProfileInformation ? $record : null,
            );
        foreach ($definitions as &$definition) {
            $validation = $section->validationSet->get($definition['name']);
            $definition['required'] = $validation?->required ?? false;
            $definition['readOnly'] = $validation?->readOnly ?? false;
            $definition['disabled'] = $validation?->disabled ?? false;
            $definition['richText'] = $definition['richText']
                || ($validation?->isRichText() ?? false);
            $definition['characterLimit'] = $validation?->characterLimit ?? 0;
            if ($validation?->inputType === 'date') {
                $definition['type'] = 'date';
            }
            $helptext = $this->profileDocumentSectionProvider->getFieldHelptext(
                $section,
                $definition['name'],
            );
            $definition['helptext'] = $helptext === ''
                ? ''
                : ($this->localizationUtility->translate($helptext) ?? $helptext);
        }
        unset($definition);
        return $definitions;
    }

    /**
     * Returns the field definitions for a contract record.
     *
     * @param Contract|null $record The contract record for which the field definitions should be created.
     * @return list<array<string, mixed>> The list of field definitions used to render contract form fields.
     */
    private function getContractFieldDefinitions(?Contract $record): array
    {
        $organisationalUnits = $this->getEntityOptions(
            $this->organisationalUnitRepository->findAll(),
            static fn(OrganisationalUnit $item): string => $item->getUnitName(),
        );
        $functionTypes = $this->getEntityOptions(
            $this->functionTypeRepository->findAll(),
            static fn(FunctionType $item): string => $item->getFunctionName(),
        );
        $locations = $this->getEntityOptions(
            $this->locationRepository->findAll(),
            static fn(Location $item): string => $item->getTitle(),
        );
        return [
            $this->createDocumentField('contract', 'position', 'text', $record?->getPosition() ?? ''),
            $this->createDocumentField(
                'contract',
                'organisationalUnit',
                'select',
                $record?->getOrganisationalUnit()?->getUid(),
                $organisationalUnits,
            ),
            $this->createDocumentField(
                'contract',
                'functionType',
                'select',
                $record?->getFunctionType()?->getUid(),
                $functionTypes,
            ),
            $this->createDocumentField(
                'contract',
                'validFrom',
                'date',
                $record?->getValidFrom()?->format('Y-m-d'),
            ),
            $this->createDocumentField(
                'contract',
                'validTo',
                'date',
                $record?->getValidTo()?->format('Y-m-d'),
            ),
            $this->createDocumentField(
                'contract',
                'location',
                'select',
                $record?->getLocation()?->getUid(),
                $locations,
            ),
            $this->createDocumentField('contract', 'room', 'text', $record?->getRoom() ?? ''),
            $this->createDocumentField(
                'contract',
                'officeHours',
                'textarea',
                $record?->getOfficeHours() ?? '',
                richText: true,
            ),
            $this->createDocumentField('contract', 'publish', 'checkbox', $record?->isPublish() ?? false),
        ];
    }

    /**
     * Returns the form field definitions for a profile information record.
     *
     * Uses the record state to determine whether the year fields are rendered in
     * a single-year mode and builds the corresponding input definitions for the
     * inline profile editor.
     *
     * @param ProfileInformation|null $record The profile information record or null for a new entry.
     * @return list<array<string, mixed>> Field configuration for the document form.
     */
    private function getProfileInformationFieldDefinitions(?ProfileInformation $record): array
    {
        $yearOnly = $record?->isYearOnly() ?? false;
        return [
            $this->createDocumentField('profileInformation', 'title', 'text', $record?->getTitle() ?? ''),
            $this->createDocumentField('profileInformation', 'link', 'url', $record?->getLink() ?? ''),
            $this->createDocumentField(
                'profileInformation',
                'year',
                'date',
                $record?->getYear()?->format('Y-m-d'),
                yearOnly: $yearOnly,
                columnClass: 'col-12 col-md-3',
            ),
            $this->createDocumentField(
                'profileInformation',
                'yearStart',
                'date',
                $record?->getYearStart()?->format('Y-m-d'),
                yearOnly: $yearOnly,
                columnClass: 'col-12 col-md-3',
            ),
            $this->createDocumentField(
                'profileInformation',
                'yearEnd',
                'date',
                $record?->getYearEnd()?->format('Y-m-d'),
                yearOnly: $yearOnly,
                columnClass: 'col-12 col-md-3',
            ),
            $this->createDocumentField(
                'profileInformation',
                'yearOnly',
                'checkbox',
                $yearOnly,
                columnClass: 'col-12 col-md-3',
                compactCheckbox: true,
            ),
            $this->createDocumentField(
                'profileInformation',
                'bodytext',
                'textarea',
                $record?->getBodytext() ?? '',
            ),
        ];
    }

    /**
     * Creates a configuration array for a document form field.
     *
     * @param string $translationPrefix Translation key prefix used to resolve the field label.
     * @param string $name Field name identifier.
     * @param string $type Field type such as text, date, checkbox or textarea.
     * @param mixed $value Current field value.
     * @param list<array{value: int|string, label: string}> $options Optional select-like options for the field.
     * @param bool $richText Whether the field uses rich text editing.
     * @param bool $yearOnly Whether the date field should be treated as a year-only input.
     * @param string $columnClass CSS column class for layout handling.
     * @param bool $compactCheckbox Whether the checkbox should be rendered in compact mode.
     * @return array<string, mixed> Field configuration array for the frontend form renderer.
     */
    private function createDocumentField(
        string $translationPrefix,
        string $name,
        string $type,
        mixed $value,
        array $options = [],
        bool $richText = false,
        bool $yearOnly = false,
        string $columnClass = '',
        bool $compactCheckbox = false,
    ): array {
        return [
            'name' => $name,
            'label' => $this->localizationUtility->translate(
                $translationPrefix . '.' . $name . '.label',
                'academic_persons_edit',
            ) ?? $name,
            'type' => $type,
            'required' => false,
            'readOnly' => false,
            'disabled' => false,
            'richText' => $richText,
            'characterLimit' => 0,
            'helptext' => '',
            'columnClass' => $columnClass,
            'compactCheckbox' => $compactCheckbox,
            'value' => $value,
            'displayValue' => $this->getDocumentDisplayValue(
                $name,
                $type,
                $value,
                $options,
                $yearOnly,
            ),
            'options' => $options,
        ];
    }

    /**
     * Builds a list of selectable entity options from the provided items.
     *
     * Each item is converted into an option entry containing its UID as the value
     * and a label generated by the provided callback. Items without a valid UID are
     * ignored.
     *
     * @param iterable<object> $items
     *   Iterable collection of entities from which the options should be built.
     * @param callable(object): string $labelCallback
     *   Callback that returns the label text for a single entity.
     * @return list<array{value: int, label: string}>
     *   List of normalized option arrays with numeric values and labels.
     */
    private function getEntityOptions(iterable $items, callable $labelCallback): array
    {
        $options = [];
        foreach ($items as $item) {
            $uid = method_exists($item, 'getUid') ? (int)$item->getUid() : 0;
            if ($uid > 0) {
                $options[] = ['value' => $uid, 'label' => $labelCallback($item)];
            }
        }
        return $options;
    }

    /**
     * Converts a raw field value into the display value shown in the UI.
     *
     * Handles date, select, checkbox and fallback string values. For select fields,
     * the matching label is resolved from the provided option list. Checkbox fields
     * use translated labels depending on the field name and boolean value.
     *
     * @param string $name
     *   The field name used to determine checkbox-specific display behavior.
     * @param string $type
     *   The field type (for example: date, select, checkbox or fallback).
     * @param mixed $value
     *   The raw field value to be rendered.
     * @param list<array{value: int|string, label: string}> $options
     *   Available select options used to resolve option labels.
     * @param bool $yearOnly
     *   Whether a date should be displayed as year-only.
     *
     * @return string
     *   The formatted display value for the frontend or an empty string for null/empty values.
     */
    private function getDocumentDisplayValue(
        string $name,
        string $type,
        mixed $value,
        array $options,
        bool $yearOnly,
    ): string {
        if ($value === null || $value === '') {
            return '';
        }
        if ($type === 'date' && is_string($value)) {
            $date = DateTime::createFromFormat('!Y-m-d', $value);
            return $date instanceof DateTime ? $date->format($yearOnly ? 'Y' : 'd.m.Y') : $value;
        }
        if ($type === 'select') {
            foreach ($options as $option) {
                if ((string)$option['value'] === (string)$value) {
                    return $option['label'];
                }
            }
            return '';
        }
        if ($type === 'checkbox') {
            if ($name === 'yearOnly') {
                return $this->localizationUtility->translate(
                    $value ? 'profileInformation.yearOnly.enabled' : 'profileInformation.yearOnly.disabled',
                    'academic_persons_edit',
                ) ?? ($value ? 'Yes' : 'No');
            }
            return $this->localizationUtility->translate(
                $value ? 'inlineProfile.visibility.public' : 'inlineProfile.visibility.private',
                'academic_persons_edit',
            ) ?? ($value ? 'Yes' : 'No');
        }
        return (string)$value;
    }

    /**
     * Normalize and validate submitted document field values for a section.
     *
     * The method checks whether each field exists in the section definition,
     * whether it is editable, normalizes the incoming values to the expected PHP
     * types, and applies the section's validation rules. Required fields are also
     * enforced when creating a new document.
     *
     * @param DocumentSection $section The document section whose field definitions and validation rules apply.
     * @param array<string, mixed> $fields Raw field data submitted by the client, keyed by field name.
     * @param bool $creating Whether the document is currently being created.
     * @return array<string, mixed> Normalized and validated field values keyed by field name.
     */
    private function normalizeAndValidateDocumentFields(
        DocumentSection $section,
        array $fields,
        bool $creating,
    ): array {
        $definitions = $this->getDocumentFieldDefinitions($section, null);
        $definitionsByName = [];
        foreach ($definitions as $definition) {
            $definitionsByName[$definition['name']] = $definition;
        }
        $errors = [];
        $normalized = [];
        foreach ($fields as $name => $value) {
            $definition = $definitionsByName[$name] ?? null;
            if ($definition === null || $definition['readOnly'] || $definition['disabled']) {
                $errors[$name][] = 'This field cannot be changed.';
                continue;
            }
            try {
                $normalized[$name] = $this->normalizeDocumentFieldValue(
                    $name,
                    $definition['type'],
                    $value,
                    $definition['richText'],
                );
            } catch (UnexpectedValueException $exception) {
                $errors[$name][] = $exception->getMessage();
            }
        }
        foreach ($definitionsByName as $name => $definition) {
            if ($creating && $definition['required'] && !array_key_exists($name, $normalized)) {
                $errors[$name][] = 'This field is required.';
            }
            if (!array_key_exists($name, $normalized)) {
                continue;
            }
            $validation = $section->validationSet->get($name);
            if ($validation !== null) {
                $this->validateDocumentField($name, $normalized[$name], $validation, $errors);
            }
        }
        if ($errors !== []) {
            $this->throwJsonError(
                'validation_failed',
                422,
                'The submitted document data is invalid.',
                $errors,
            );
        }
        return $normalized;
    }

    /**
     * Normalizes a submitted document field value according to its declared field type.
     *
     * @param string $name The field name used to resolve select-based entity lookups.
     * @param string $type The document field type (for example: checkbox, number, date, select, or text).
     * @param mixed $value The submitted raw value to normalize.
     * @param bool $richText Whether the value should be sanitized as rich text.
     * @return mixed The normalized value for storage or validation.
     * @throws UnexpectedValueException If the value does not match the expected format for the given type.
     */
    private function normalizeDocumentFieldValue(
        string $name,
        string $type,
        mixed $value,
        bool $richText,
    ): mixed {
        if ($type === 'checkbox') {
            if (!is_bool($value)) {
                throw new UnexpectedValueException('The value must be boolean.');
            }
            return $value;
        }
        if ($type === 'number') {
            if ($value === null || $value === '') {
                return null;
            }
            if (is_int($value)) {
                return $value;
            }
            if (is_string($value) && preg_match('/^-?\d+$/', $value) === 1) {
                return (int)$value;
            }
            throw new UnexpectedValueException('The value must be an integer.');
        }
        if ($type === 'date') {
            if ($value === null || $value === '') {
                return null;
            }
            if (!is_string($value)) {
                throw new UnexpectedValueException('The value must be a date.');
            }
            $date = DateTime::createFromFormat('!Y-m-d', $value);
            $dateErrors = DateTime::getLastErrors();
            if (
                !$date instanceof DateTime
                || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
                || $date->format('Y-m-d') !== $value
            ) {
                throw new UnexpectedValueException('The value must be a valid date.');
            }
            return $date;
        }
        if ($type === 'select') {
            if ($value === null || $value === '' || $value === 0) {
                return null;
            }
            $uid = is_int($value)
                ? $value
                : (is_string($value) && preg_match('/^\d+$/', $value) === 1 ? (int)$value : 0);
            if ($uid <= 0) {
                throw new UnexpectedValueException('The selected value is invalid.');
            }
            $entity = $this->findDocumentSelectEntity($name, $uid);
            if ($entity === null) {
                throw new UnexpectedValueException('The selected value is not available.');
            }
            return $entity;
        }
        if (!is_string($value)) {
            throw new UnexpectedValueException('The value must be a string.');
        }
        return ($richText || in_array($name, ['bodytext', 'officeHours'], true))
            ? $this->profileRichTextSanitizer->sanitize($value)
            : trim($value);
    }

    /**
     * Finds the matching document select entity for the given field name and UID.
     *
     * @param string $name The field name identifying the repository to search in.
     * @param int $uid The UID of the entity to look up.
     * @return FunctionType|OrganisationalUnit|Location|null The matching entity or null if no entity with the given UID exists.
     */
    private function findDocumentSelectEntity(
        string $name,
        int $uid,
    ): FunctionType|OrganisationalUnit|Location|null {
        $items = match ($name) {
            'functionType' => $this->functionTypeRepository->findAll(),
            'organisationalUnit' => $this->organisationalUnitRepository->findAll(),
            'location' => $this->locationRepository->findAll(),
            default => [],
        };
        foreach ($items as $item) {
            if (
                ($item instanceof FunctionType || $item instanceof OrganisationalUnit || $item instanceof Location)
                && (int)$item->getUid() === $uid
            ) {
                return $item;
            }
        }
        return null;
    }

    /**
     * Validates a single document field against the configured character limit and custom validators.
     *
     * @param string $name The field name used as the validation error key.
     * @param mixed $value The submitted value to validate.
     * @param Validation $validation The validation configuration for the field.
     * @param array<string, list<string>> $errors Reference to the collected validation errors by field name.
     */
    private function validateDocumentField(
        string $name,
        mixed $value,
        Validation $validation,
        array &$errors,
    ): void {
        if (
            $validation->characterLimit > 0
            && is_string($value)
            && RichTextCharacterCounter::count($value) > $validation->characterLimit
        ) {
            $errors[$name][] = sprintf(
                'The text must not exceed %d characters.',
                $validation->characterLimit,
            );
        }
        foreach ($validation->validatorClassNames as $validatorClassName) {
            $validator = GeneralUtility::makeInstance($validatorClassName);
            if (!$validator instanceof ValidatorInterface) {
                continue;
            }
            $validationResult = $validator->validate($value);
            foreach ($validationResult->getErrors() as $error) {
                $errors[$name][] = $error->getMessage();
            }
        }
    }

    /**
     * Creates a contract form data object and applies the provided field overrides.
     *
     * @param array<string, mixed> $fields The raw form fields to override on the contract form.
     * @return ContractFormData The initialized contract form data instance.
     */
    private function createContractFormData(array $fields): ContractFormData
    {
        $formData = new ContractFormData();
        $this->applyDocumentFormOverrides($formData, $fields);
        return $formData;
    }

    /**
     * Creates a profile information form data object and applies the provided field overrides.
     *
     * @param DocumentSection $section The document section used to determine the profile information type.
     * @param array<string, mixed> $fields The raw form fields to override on the profile information form.
     * @return ProfileInformationFormData The initialized profile information form data instance.
     */
    private function createProfileInformationFormData(
        DocumentSection $section,
        array $fields,
    ): ProfileInformationFormData {
        $formData = ProfileInformationFormData::createEmptyForType($section->type);
        $formData->setPropertyOverride('type', $section->type);
        $this->applyDocumentFormOverrides($formData, $fields);
        return $formData;
    }

    /**
     * Applies the provided field overrides to the form data instance.
     *
     * @param AbstractFormData $formData The target form data object receiving the overrides.
     * @param array<string, mixed> $fields The raw form fields keyed by property name with their override values.
     * @return void
     */
    private function applyDocumentFormOverrides(AbstractFormData $formData, array $fields): void
    {
        foreach ($fields as $name => $value) {
            $formData->setPropertyOverride($name, $value);
        }
    }

    /**
     * Serializes a document item into a frontend-safe payload.
     *
     * This compiles the field definitions for the given document section and record
     * into a normalized array that contains the stored values as well as the
     * corresponding display labels for the editor UI.
     *
     * @param DocumentSection $section The document section that defines the fields to serialize.
     * @param Contract|ProfileInformation $record The record associated with the document item.
     * @return array{
     *     uid: int,
     *     sorting: int,
     *     values: array<string, mixed>,
     *     display: array<string, string>
     * }
     */
    private function serializeDocumentItem(
        DocumentSection $section,
        Contract|ProfileInformation $record,
    ): array {
        $values = [];
        $display = [];
        foreach ($this->getDocumentFieldDefinitions($section, $record) as $field) {
            $values[$field['name']] = $field['value'];
            $display[$field['name']] = $field['displayValue'];
        }
        return [
            'uid' => (int)$record->getUid(),
            'sorting' => $record->getSorting(),
            'values' => $values,
            'display' => $display,
        ];
    }

    /**
     * Logs a failed document operation and terminates the request with a JSON error response.
     *
     * @param string $logMessage Message to log for diagnosing the failure
     * @param Throwable $exception The exception that caused the document operation to fail
     */
    private function handleDocumentFailure(string $logMessage, Throwable $exception): never
    {
        GeneralUtility::makeInstance(LogManager::class)
            ->getLogger(self::class)
            ->error($logMessage, ['exception' => $exception]);
        $this->throwJsonError(
            'internal_server_error',
            500,
            'The document operation could not be completed.',
        );
    }

    /**
     * Creates a JSON error response payload for failed requests.
     *
     * @param string $error Machine-readable error identifier.
     * @param int $statusCode HTTP status code to return in the response.
     * @param string|null $message Optional human-readable message describing the error.
     * @param array<string, list<string>> $errors Optional field-specific validation errors keyed by field name.
     * @return JsonResponse The generated JSON error response.
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

    /**
     * Throws a JSON error response as a propagated exception.
     *
     * This method creates a JSON error payload and wraps it in a
     * PropagateResponseException so the framework can handle the response
     * without continuing the normal controller flow.
     *
     * @param string $error The machine-readable error code.
     * @param int $statusCode The HTTP status code to send with the response.
     * @param string|null $message An optional human-readable error message.
     * @param array<string, list<string>> $errors Optional field-specific validation errors keyed by field name.
     * @throws PropagateResponseException Thrown when the JSON error response should be propagated.
     * @return never This method does not return normally because it always throws.
     */
    private function throwJsonError(
        string $error,
        int $statusCode,
        ?string $message = null,
        array $errors = [],
    ): never {
        throw new PropagateResponseException(
            $this->jsonError($error, $statusCode, $message, $errors),
            1776760205,
        );
    }

    // =================================================================================================================
    //  Handle entity image operations
    // =================================================================================================================
    /**
     * Initializes the upload process for a profile image.
     *
     * Validates that the image field is writable and that the referenced profile
     * can be edited by the current user before configuring the file upload.
     * This check is performed before Extbase maps the uploaded file to avoid
     * creating unreferenced files in the file abstraction layer for unauthorized
     * requests.
     *
     * @return void
     * @throws PropagateResponseException If the image field is not writable or the
     *         profile is not editable for the current request.
     */
    public function initializeUploadImageAction(): void
    {
        if (!$this->isSpecialFieldWritable('image')) {
            throw new PropagateResponseException(
                $this->jsonError('image_not_editable', 403),
                1776760206,
            );
        }
        $profileArgument = $this->request->hasArgument('profile')
            ? $this->request->getArgument('profile')
            : null;
        $profileUid = is_array($profileArgument)
            ? (int)($profileArgument['__identity'] ?? 0)
            : (int)$profileArgument;
        // This check happens before Extbase maps the upload. An unauthorized
        // request therefore cannot create an unreferenced file in FAL.
        if ($this->profileUpdateRequestService->findEditableProfile($profileUid) === null) {
            throw new PropagateResponseException(
                $this->jsonError('profile_not_editable', 403),
                1776760201,
            );
        }
        $this->configureImageFileUpload();
    }

    /**
     * Uploads a new profile image for the given profile.
     *
     * The method validates that a new file was submitted, stores the uploaded
     * image in the configured FAL storage, and returns the updated image
     * metadata as JSON.
     *
     * @param Profile $profile The profile whose image should be updated.
     * @return ResponseInterface JSON response containing the upload result and
     *                          image metadata.
     * @throws PropagateResponseException If the upload is missing, invalid or not
     *                                   permitted.
     */
    public function uploadImageAction(Profile $profile): ResponseInterface
    {
        try {
            $replacedImageFile = $this->getPersistedProfileImageFile($profile);
            if (!$this->hasNewProfileImage($profile, $replacedImageFile)) {
                throw new PropagateResponseException(
                    $this->jsonError(
                        'image_upload_missing',
                        422,
                        'No new profile image was received.',
                    ),
                    1776760203,
                );
            }
            $imageMetadata = $this->persistUploadedProfileImage($profile, $replacedImageFile);
            return new JsonResponse([
                'success' => true,
                'profile' => $profile->getUid(),
                'hasImage' => true,
                'imageAlternative' => $imageMetadata['alternative'],
                'imageTitle' => $imageMetadata['title'],
            ]);
        } catch (PropagateResponseException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            GeneralUtility::makeInstance(LogManager::class)
                ->getLogger(self::class)
                ->error('Uploading the inline profile image failed.', [
                    'exception' => $exception,
                ]);
            throw new PropagateResponseException(
                $this->jsonError(
                    'internal_server_error',
                    500,
                    'The profile image could not be uploaded.',
                ),
                1776760204,
            );
        }
    }

    /**
     * Deletes the current profile image for the validated inline profile.
     *
     * Validates the incoming request payload, ensures the image field is writable,
     * and removes the existing profile image if no profile fields were submitted.
     *
     * @return ResponseInterface JSON response indicating whether the image was deleted
     * @throws PropagateResponseException When the request is invalid and a JSON error
     *         response should be propagated
     */
    public function deleteImageAction(): ResponseInterface
    {
        $requestResult = $this->profileUpdateRequestService->validate(
            $this->request,
        );
        if (!$requestResult->isValid()) {
            $this->throwJsonError(
                $requestResult->getError() ?? 'invalid_request',
                $requestResult->getStatusCode(),
            );
        }
        $payload = $requestResult->getPayload();
        $profile = $requestResult->getProfile();
        if ($payload === null || $profile === null) {
            $this->throwJsonError('internal_server_error', 500);
        }
        if (!$this->isSpecialFieldWritable('image')) {
            $this->throwJsonError('image_not_editable', 403);
        }
        if ($payload->getData() !== []) {
            $this->throwJsonError(
                'invalid_payload',
                400,
                'The image deletion payload must not contain profile fields.',
            );
        }
        try {
            $deleted = $this->deleteProfileImage($profile);
            return new JsonResponse([
                'success' => true,
                'profile' => $profile->getUid(),
                'deleted' => $deleted,
                'hasImage' => false,
            ]);
        } catch (Throwable $exception) {
            GeneralUtility::makeInstance(LogManager::class)
                ->getLogger(self::class)
                ->error('Deleting the inline profile image failed.', [
                    'exception' => $exception,
                ]);
            $this->throwJsonError(
                'internal_server_error',
                500,
                'The profile image could not be deleted.',
            );
        }
    }

    /**
     * Checks whether the submitted profile image differs from the persisted image.
     *
     * @param Profile $profile The profile currently being processed.
     * @param File|null $persistedImageFile The previously persisted image file, if available.
     * @return bool True if a new image was submitted or the persisted image is different, otherwise false.
     */
    private function hasNewProfileImage(Profile $profile, ?File $persistedImageFile): bool
    {
        $submittedImageFile = $profile->getImage()?->getOriginalResource()->getOriginalFile();
        if ($submittedImageFile === null) {
            return false;
        }
        return $persistedImageFile === null
            || $submittedImageFile->getUid() !== $persistedImageFile->getUid();
    }

    /**
     * Checks whether the given special field is editable.
     *
     * @param string $identifier The identifier of the special field to check.
     * @return bool True if the field exists and is not marked as read-only or disabled, otherwise false.
     */
    private function isSpecialFieldWritable(string $identifier): bool
    {
        $field = $this->academicPersonsSettings->getSpecialField($identifier);
        return $field !== null
            && !$field->validation->readOnly
            && !$field->validation->disabled;
    }

    /**
     * Persists the uploaded profile image and updates the associated metadata.
     *
     * The profile is saved, pending database changes are flushed, the image metadata is updated,
     * and the previously replaced image file is deleted afterwards.
     *
     * @param File|null $replacedImageFile The image file that was replaced by the newly uploaded one, if any.
     * @return array{alternative: string, title: string} The updated metadata for the uploaded profile image.
     */
    private function persistUploadedProfileImage(Profile $profile, ?File $replacedImageFile): array
    {
        $this->profileRepository->update($profile);
        $this->persistenceManager->persistAll();
        $imageMetadata = $this->updateUploadedProfileImageMetadata($profile);
        $this->deleteReplacedProfileImageFile($replacedImageFile, $profile);
        return $imageMetadata;
    }

    /**
     * Updates the metadata for the uploaded profile image and its file reference.
     *
     * The metadata text is generated from the profile data and written to the file
     * metadata as well as the related sys_file_reference record, if one exists.
     *
     * @param Profile $profile The profile whose uploaded image metadata should be updated.
     * @return array{alternative: string, title: string} The updated image metadata.
     * @throws UnexpectedValueException If the uploaded image reference or file is missing.
     */
    private function updateUploadedProfileImageMetadata(Profile $profile): array
    {
        $imageReference = $profile->getImage()?->getOriginalResource();
        $imageFile = $imageReference?->getOriginalFile();
        if ($imageReference === null || $imageFile === null) {
            throw new UnexpectedValueException('The uploaded profile image is unavailable.');
        }
        $metadataText = $this->buildProfileImageMetadataText($profile);
        $metadata = ['alternative' => $metadataText, 'title' => $metadataText];
        GeneralUtility::makeInstance(MetaDataRepository::class)
            ->update($imageFile->getUid(), $metadata);
        $imageReferenceUid = $this->getPersistedProfileImageReferenceUid($profile, $imageFile);
        if ($imageReferenceUid > 0) {
            GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable('sys_file_reference')
                ->update('sys_file_reference', $metadata, ['uid' => $imageReferenceUid]);
        }
        return $metadata;
    }

    /**
     * Retrieves the persisted UID of the file reference for the profile image.
     *
     * The method first checks the current in-memory image relation and falls back to a
     * direct lookup in the sys_file_reference table when the relation has not yet been
     * persisted to the database.
     *
     * @param Profile $profile The profile whose image reference should be resolved.
     * @param File $imageFile The image file associated with the profile.
     * @return int The UID of the persisted file reference, or 0 if no persisted reference exists.
     */
    private function getPersistedProfileImageReferenceUid(Profile $profile, File $imageFile): int
    {
        $imageReferenceUid = $profile->getImage()?->getOriginalResource()->getUid() ?? 0;
        if ($imageReferenceUid > 0) {
            return $imageReferenceUid;
        }
        $profileUid = $profile->getUid();
        if ($profileUid === null) {
            return 0;
        }
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file_reference');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        return (int)$queryBuilder
            ->select('uid')
            ->from('sys_file_reference')
            ->where(
                $queryBuilder->expr()->eq(
                    'tablenames',
                    $queryBuilder->createNamedParameter('tx_academicpersons_domain_model_profile'),
                ),
                $queryBuilder->expr()->eq(
                    'fieldname',
                    $queryBuilder->createNamedParameter('image'),
                ),
                $queryBuilder->expr()->eq(
                    'uid_foreign',
                    $queryBuilder->createNamedParameter($profileUid, Connection::PARAM_INT),
                ),
                $queryBuilder->expr()->eq(
                    'uid_local',
                    $queryBuilder->createNamedParameter($imageFile->getUid(), Connection::PARAM_INT),
                ),
            )
            ->orderBy('uid', 'DESC')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchOne();
    }

    /**
     * Builds the image metadata text from the profile's name parts.
     *
     * The method normalizes whitespace and concatenates the available name
     * components (title, first name, middle name, and last name) into a single
     * string that can be used as metadata for the profile image.
     *
     * @param Profile $profile The profile whose name metadata should be assembled.
     * @return string A normalized name string without empty parts.
     */
    private function buildProfileImageMetadataText(Profile $profile): string
    {
        $parts = array_map(
            static fn(string $part): string => trim(
                (string)(preg_replace('/\s+/u', ' ', $part) ?? $part),
            ),
            [$profile->getTitle(), $profile->getFirstName(), $profile->getMiddleName(), $profile->getLastName()],
        );
        return implode(' ', array_filter($parts, static fn(string $part): bool => $part !== ''));
    }

    /**
     * Deletes the currently assigned image of a profile and removes the file from storage
     * when it is no longer referenced by any other record.
     *
     * The image relation is cleared before the file is removed so that stale references
     * do not remain on the profile record and other usages can be checked correctly.
     *
     * @param Profile $profile The profile whose image should be deleted.
     * @return bool True if an image was deleted, otherwise false if no image was assigned.
     */
    private function deleteProfileImage(Profile $profile): bool
    {
        $image = $profile->getImage();
        if ($image === null) {
            return false;
        }
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
        return true;
    }


    /**
     * Configures the upload handling for the profile image field.
     *
     * Sets the upload folder, maximum number of files, validates file size and
     * MIME types according to the configured settings, and registers the upload
     * configuration on the profile argument while skipping the image property in
     * the property mapping process.
     *
     * @return void
     */
    private function configureImageFileUpload(): void
    {
        $profileArgument = $this->arguments->getArgument('profile');
        $fileUploadConfiguration = (new FileUploadConfiguration('image'))
            ->setMaxFiles(2)
            ->setUploadFolder(
                (string) ($this->settings['editForm']['profileImage']['targetFolder'] ?? '1:/user_upload/')
            );
        $fileSizeValidator = GeneralUtility::makeInstance(FileSizeValidator::class);
        $fileSizeValidator->setOptions([
            'maximum' => (string) ($this->settings['editForm']['profileImage']['validation']['maxFileSize'] ?? PHP_INT_MAX . 'B'),
        ]);
        $fileUploadConfiguration->addValidator($fileSizeValidator);
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
        $profileArgument->getPropertyMappingConfiguration()->skipProperties('image');
    }

    /**
     * Returns the file currently referenced as the profile image in the persisted database state.
     *
     * Reading the persisted state instead of the mapped object is intentional: the in-memory
     * profile already carries a newly uploaded file during upload processing and therefore does
     * not necessarily reflect the original stored file reference.
     *
     * @param Profile $profile The profile whose persisted image reference should be resolved.
     * @return File|null The referenced file object or null if no persisted profile image exists.
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
     * Deletes a previously replaced profile image file if it is no longer used.
     *
     * The method checks whether the uploaded image actually replaced an existing
     * file and ensures that the previous file is removed only when it is not
     * referenced anymore by the profile or any other record.
     *
     * @param File|null $replacedImageFile The old image file that was replaced
     * @param Profile $profile The profile whose image was updated
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

    /**
     * Counts how many file references point to the given file.
     *
     * This is used to decide whether a replaced image can be removed safely after
     * an update without deleting still-referenced files from other records.
     *
     * @param File $file The file whose references should be counted
     * @return int The number of active references in sys_file_reference for this file
     */
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
     * Returns the configured gender select items from the profile TCA.
     *
     * Only directly configured TCA items are considered, since evaluating all
     * itemProcFunc and FormEngine logic in the frontend is not feasible.
     * Empty values are skipped because the Fluid select field adds the empty
     * placeholder separately.
     *
     * @return array<int, array{
     *     label: string,
     *     labelTranslationIdentifier: string,
     *     value: string,
     * }>
     *
     *
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
