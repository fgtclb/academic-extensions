<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons_edit" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersonsEdit\Controller;

use FGTCLB\AcademicPersons\Domain\Model\Contract;
use FGTCLB\AcademicPersons\Domain\Model\PhoneNumber;
use FGTCLB\AcademicPersons\Domain\Repository\PhoneNumberRepository;
use FGTCLB\AcademicPersonsEdit\Domain\Factory\PhoneNumberFactory;
use FGTCLB\AcademicPersonsEdit\Domain\Model\Dto\PhoneNumberFormData;
use FGTCLB\AcademicPersonsEdit\Domain\Validator\PhoneNumberFormDataValidator;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Annotation\Validate;

/**
 * @internal to be used only in `EXT:academic_person_edit` and not part of public API.
 */
final class PhoneNumberController extends AbstractActionController
{
    public function __construct(
        private readonly PhoneNumberFactory $phoneNumberFactory,
        private readonly PhoneNumberRepository $phoneNumberRepository,
    ) {}

    // =================================================================================================================
    // Handle readonly display like list forms and detail view
    // =================================================================================================================

    public function listAction(Contract $contract): ResponseInterface
    {
        $this->userSessionService->saveRefererToSession($this->request);

        $this->view->assignMultiple([
            'data' => $this->getCurrentContentObjectRenderer()?->data,
            'profile' => $contract->getProfile(),
            'contract' => $contract,
            'phoneNumbers' => $contract->getPhoneNumbers(),
        ]);
        return $this->htmlResponse();
    }

    public function showAction(PhoneNumber $phoneNumber): ResponseInterface
    {
        $this->userSessionService->saveRefererToSession($this->request);

        $this->view->assignMultiple([
            'data' => $this->getCurrentContentObjectRenderer()?->data,
            'profile' => $phoneNumber->getContract()?->getProfile(),
            'contract' => $phoneNumber->getContract(),
            'phoneNumber' => $phoneNumber,
        ]);
        return $this->htmlResponse();
    }

    // =================================================================================================================
    //  Handle creation of new entity
    // =================================================================================================================

    public function newAction(Contract $contract): ResponseInterface
    {
        $this->view->assignMultiple([
            'data' => $this->getCurrentContentObjectRenderer()?->data,
            'profile' => $contract->getProfile(),
            'availableTypes' => $this->getAvailableTypes(),
            'contract' => $contract,
            'phoneNumberFormData' => new PhoneNumberFormData(),
            'cancelUrl' => $this->userSessionService->loadRefererFromSession($this->request),
            'validations' => $this->academicPersonsSettings->getValidationSetWithFallback('phoneNumber')->validations,
        ]);
        return $this->htmlResponse();
    }

    #[Validate([
        'param' => 'phoneNumberFormData',
        'validator' => PhoneNumberFormDataValidator::class,
    ])]
    public function createAction(Contract $contract, PhoneNumberFormData $phoneNumberFormData): ResponseInterface
    {
        $phoneNumber = $this->phoneNumberFactory->createFromFormData(
            $this->academicPersonsSettings->getValidationSetWithFallback('phoneNumber'),
            $contract,
            $phoneNumberFormData,
        );
        $maxSortingValue = 0;
        foreach ($contract->getPhoneNumbers() as $existingPhoneNumber) {
            $maxSortingValue = max($maxSortingValue, $existingPhoneNumber->getSorting());
        }
        // Use next available sorting value
        $maxSortingValue += 1;
        $phoneNumber->setSorting($maxSortingValue);
        $this->phoneNumberRepository->add($phoneNumber);
        $this->persistenceManager->persistAll();

        $this->addTranslatedSuccessMessage('phoneNumber.create.success');

        if ($this->request->hasArgument('submit')
            && $this->request->getArgument('submit') === 'save-and-close'
        ) {
            return new RedirectResponse($this->userSessionService->loadRefererFromSession($this->request), 303);
        }
        return $this->createFormPersistencePrgRedirect('edit', ['phoneNumber' => $phoneNumber]);
    }

    // =================================================================================================================
    // Handle entity changes like displaying edit form and edit persistence.
    // =================================================================================================================

    public function editAction(PhoneNumber $phoneNumber): ResponseInterface
    {
        $this->view->assignMultiple([
            'data' => $this->getCurrentContentObjectRenderer()?->data,
            'profile' => $phoneNumber->getContract()?->getProfile(),
            'availableTypes' => $this->getAvailableTypes(),
            'contract' => $phoneNumber->getContract(),
            'phoneNumber' => $phoneNumber,
            'phoneNumberFormData' => PhoneNumberFormData::createFromPhoneNumber($phoneNumber),
            'cancelUrl' => $this->userSessionService->loadRefererFromSession($this->request),
            'validations' => $this->academicPersonsSettings->getValidationSetWithFallback('phoneNumber')->validations,
        ]);
        return $this->htmlResponse();
    }

    #[Validate([
        'param' => 'phoneNumberFormData',
        'validator' => PhoneNumberFormDataValidator::class,
    ])]
    public function updateAction(
        PhoneNumber $phoneNumber,
        PhoneNumberFormData $phoneNumberFormData
    ): ResponseInterface {
        $this->phoneNumberRepository->update(
            $this->phoneNumberFactory->updateFromFormData(
                $this->academicPersonsSettings->getValidationSetWithFallback('phoneNumber'),
                $phoneNumber,
                $phoneNumberFormData,
            ),
        );

        $this->addTranslatedSuccessMessage('phoneNumber.update.success');

        if ($this->request->hasArgument('submit')
            && $this->request->getArgument('submit') === 'save-and-close'
        ) {
            return new RedirectResponse($this->userSessionService->loadRefererFromSession($this->request), 303);
        }
        return $this->createFormPersistencePrgRedirect('edit', ['phoneNumber' => $phoneNumber]);
    }

    public function sortAction(PhoneNumber $phoneNumber, string $sortDirection): ResponseInterface
    {
        $contract = $phoneNumber->getContract();
        if ($contract === null) {
            // @todo Needs to be handled properly.
            throw new \RuntimeException(
                'Could not get contract.',
                1752939240,
            );
        }

        if (!in_array($sortDirection, ['up', 'down', 'top', 'bottom'])
            || $contract->getPhoneNumbers()->count() <= 1
        ) {
            $this->addTranslatedErrorMessage('phoneNumber.sort.error.notPossible');
            return new RedirectResponse($this->userSessionService->loadRefererFromSession($this->request), 303);
        }

        // Convert contracts to array
        $sortingItemsArray = [];
        $targetItemIndex = null;

        $index = 0;
        foreach ($contract->getPhoneNumbers() as $contractPhoneNumber) {
            $sortingItemsArray[] = $contractPhoneNumber;
            if ($phoneNumber->getUid() === $contractPhoneNumber->getUid()) {
                $targetItemIndex = $index;
            }
            $index++;
        }

        $changed = false;

        if ($targetItemIndex !== null) {
            $targetItem = $sortingItemsArray[$targetItemIndex];

            // if sort direction is top and target not the first item, remove item from array then prepend it to the array
            if ($sortDirection === 'top' && $targetItemIndex > 0) {
                array_splice($sortingItemsArray, $targetItemIndex, 1);
                array_unshift($sortingItemsArray, $targetItem);
                $changed = true;
            }
            // if sort direction is bottom and target not the last item, remove item from array then append it to the array
            elseif ($sortDirection === 'bottom' && $targetItemIndex < count($sortingItemsArray) - 1) {
                array_splice($sortingItemsArray, $targetItemIndex, 1);
                array_push($sortingItemsArray, $targetItem);
                $changed = true;
            }
            // if sort direction is up and target not the first item, swap the target with the previous item
            elseif ($sortDirection === 'up' && $targetItemIndex > 0) {
                $sortingItemsArray[$targetItemIndex] = $sortingItemsArray[$targetItemIndex - 1];
                $sortingItemsArray[$targetItemIndex - 1] = $targetItem;
                $changed = true;
            }
            // if sort direction is down and target not the last item, swap the target with the next item
            elseif ($sortDirection === 'down' && $targetItemIndex < count($sortingItemsArray) - 1) {
                $sortingItemsArray[$targetItemIndex] = $sortingItemsArray[$targetItemIndex + 1];
                $sortingItemsArray[$targetItemIndex + 1] = $targetItem;
                $changed = true;
            }

            foreach ($sortingItemsArray as $idx => $item) {
                // Use strictly increasing sorting values (standard TYPO3 behavior)
                $expectedSorting = ($idx + 1) * 10;

                if ($item->getSorting() !== $expectedSorting) {
                    $item->setSorting($expectedSorting);
                    $this->phoneNumberRepository->update($item);
                    $changed = true;
                }
            }

            if ($changed) {
                $this->persistenceManager->persistAll();
                $this->addTranslatedSuccessMessage('phoneNumber.sort.success');
            }
        }

        return new RedirectResponse($this->userSessionService->loadRefererFromSession($this->request), 303);
    }

    // =================================================================================================================
    // Handle destructive actions like deleting records
    // =================================================================================================================

    public function confirmDeleteAction(PhoneNumber $phoneNumber): ResponseInterface
    {
        $this->view->assignMultiple([
            'data' => $this->getCurrentContentObjectRenderer()?->data,
            'profile' => $phoneNumber->getContract()?->getProfile(),
            'contract' => $phoneNumber->getContract(),
            'phoneNumber' => $phoneNumber,
            'cancelUrl' => $this->userSessionService->loadRefererFromSession($this->request),
        ]);
        return $this->htmlResponse();
    }

    public function deleteAction(PhoneNumber $phoneNumber): ResponseInterface
    {
        $this->phoneNumberRepository->remove($phoneNumber);
        $this->addTranslatedSuccessMessage('phoneNumber.delete.success');
        return new RedirectResponse($this->userSessionService->loadRefererFromSession($this->request), 303);
    }

    /**
     * @return array<int<0, max>, array{
     *      label: string,
     *      value: string,
     *  }>
     * @todo Evaluating TCA in frontend for available options is a hard task to do correctly requiring to execute
     *       TCA item proc functions and so on. It also does not account for eventually FormEngine nodes processing
     *       additional stuff. Current implementation calls the itemProcFunc with a minimal set as context data, but
     *       cannot simulate all the stuff provided by FormEngine.
     * @todo Use TcaSchema for TYPO3 v13, either as dual version OR when dropping TYPO3 v12 support.
     */
    private function getAvailableTypes(): array
    {
        $tableName = 'tx_academicpersons_domain_model_phone_number';
        $fieldName = 'type';
        $items = $GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config']['items'] ?? [];
        $itemProcFunc = (string)($GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config']['itemsProcFunc'] ?? '');
        if ($itemProcFunc !== '') {
            $items = $GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config']['items'] ?? [];
            $processorParameters = [
                'items' => &$items,
                'config' => $GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config'],
                'table' => $tableName,
                'field' => $fieldName,
            ];
            GeneralUtility::callUserFunction($itemProcFunc, $processorParameters, $this);
            $items = $processorParameters['items'];
        }
        $returnItems = [];
        foreach ($items as $item) {
            $itemValue = (string)($item['value'] ?? '');
            if ($itemValue === '') {
                // Skip empty string values, handled with `<f:form.select prependOptionLabel="---" />`
                // in the fluid template.
                continue;
            }
            $labelIdentifier = (string)($item['label'] ?? '');
            $returnItems[] = [
                'label' => ($this->localizationUtility->translate(
                    $labelIdentifier,
                    'persons_edit',
                ) ?? $labelIdentifier) ?: $labelIdentifier,
                'value' => $itemValue,
            ];
        }
        return $returnItems;
    }
}
