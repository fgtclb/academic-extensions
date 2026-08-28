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

    public function initializeAction(): void
    {
        // The JSON update endpoint performs its own authentication check so it
        // can return a machine-readable 401 response instead of the HTML error
        // response propagated by the shared controller initialization.
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
            'imageAllowedMimeTypes' => (string)(
                $this->settings['editForm']['profileImage']['validation']['allowedMimeTypes']
                ?? 'image/jpeg,image/png,image/webp'
            ),
        ]);
        return $this->htmlResponse();
    }

    /**
     * @param iterable<Profile> $profiles
     * @return list<array{profile: Profile, language: string}>
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
     * Updates the synchronization flag through its own JSON endpoint.
     *
     * Keeping this separate from the generic field endpoint allows the
     * checkbox to persist immediately without submitting unrelated fields.
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
     * Returns the field schema and current values used by the add, view and edit modal.
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
     * @return array{0: Profile, 1: DocumentSection, 2: array<string, mixed>}
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
     * @param array<string, mixed> $data
     * @param list<string> $allowedKeys
     * @param list<string> $requiredKeys
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
     * @param array<string, mixed> $data
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
     * @param array<string, mixed> $data
     */
    private function getOptionalPositiveInteger(array $data, string $property): ?int
    {
        if (!array_key_exists($property, $data) || $data[$property] === null || $data[$property] === 0) {
            return null;
        }
        return $this->getRequiredPositiveInteger($data, $property);
    }

    /**
     * @param array<string, mixed> $data
     * @return 'add'|'view'|'edit'|'delete'
     */
    private function getRequiredDocumentMode(array $data): string
    {
        $mode = $data['mode'] ?? null;
        if (!is_string($mode) || !in_array($mode, ['add', 'view', 'edit', 'delete'], true)) {
            $this->throwJsonError('invalid_payload', 400, 'The document mode is invalid.');
        }
        return $mode;
    }

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
     * @param array<string, mixed> $data
     * @return array<string, mixed>
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
     * @param array<string, mixed> $data
     * @return list<int>
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
     * @return list<Contract|ProfileInformation>
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
     * @param list<Contract|ProfileInformation> $records
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
     * @param list<Contract|ProfileInformation> $records
     * @param list<int> $order
     * @return array{changed: bool, records: list<Contract|ProfileInformation>}
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
     * @param Contract|ProfileInformation|null $record
     * @return list<array{
     *     name: string,
     *     label: string,
     *     type: string,
     *     required: bool,
     *     readOnly: bool,
     *     disabled: bool,
     *     richText: bool,
     *     characterLimit: int,
     *     columnClass: string,
     *     compactCheckbox: bool,
     *     value: mixed,
     *     displayValue: string,
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
        }
        unset($definition);
        return $definitions;
    }

    /**
     * @return list<array<string, mixed>>
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
     * @return list<array<string, mixed>>
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
     * @param list<array{value: int|string, label: string}> $options
     * @return array<string, mixed>
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
     * @param iterable<object> $items
     * @param callable(object): string $labelCallback
     * @return list<array{value: int, label: string}>
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
     * @param list<array{value: int|string, label: string}> $options
     */
    private function getDocumentDisplayValue(
        string $name,
        string $type,
        mixed $value,
        array $options,
        bool $yearOnly,
    ): string
    {
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
     * @param array<string, mixed> $fields
     * @return array<string, mixed>
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

    private function normalizeDocumentFieldValue(
        string $name,
        string $type,
        mixed $value,
        bool $richText,
    ): mixed
    {
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
     * @param array<string, list<string>> $errors
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
     * @param array<string, mixed> $fields
     */
    private function createContractFormData(array $fields): ContractFormData
    {
        $formData = new ContractFormData();
        $this->applyDocumentFormOverrides($formData, $fields);
        return $formData;
    }

    /**
     * @param array<string, mixed> $fields
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
     * @param array<string, mixed> $fields
     */
    private function applyDocumentFormOverrides(AbstractFormData $formData, array $fields): void
    {
        foreach ($fields as $name => $value) {
            $formData->setPropertyOverride($name, $value);
        }
    }

    /**
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

    /**
     * @param array<string, list<string>> $errors
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

    private function hasNewProfileImage(Profile $profile, ?File $persistedImageFile): bool
    {
        $submittedImageFile = $profile->getImage()?->getOriginalResource()->getOriginalFile();
        if ($submittedImageFile === null) {
            return false;
        }
        return $persistedImageFile === null
            || $submittedImageFile->getUid() !== $persistedImageFile->getUid();
    }

    private function isSpecialFieldWritable(string $identifier): bool
    {
        $field = $this->academicPersonsSettings->getSpecialField($identifier);
        return $field !== null
            && !$field->validation->readOnly
            && !$field->validation->disabled;
    }

    /**
     * @return array{alternative: string, title: string}
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
     * @return array{alternative: string, title: string}
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
            // the uploaded file, and `uploadImageAction()` cleans the replaced file up afterwards.
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
