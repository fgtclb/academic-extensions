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
use FGTCLB\AcademicPersons\Domain\Model\Profile;
use FGTCLB\AcademicPersons\Domain\Repository\ContractRepository;
use FGTCLB\AcademicPersons\Domain\Repository\FunctionTypeRepository;
use FGTCLB\AcademicPersons\Domain\Repository\LocationRepository;
use FGTCLB\AcademicPersons\Domain\Repository\OrganisationalUnitRepository;
use FGTCLB\AcademicPersonsEdit\Domain\Factory\ContractFactory;
use FGTCLB\AcademicPersonsEdit\Domain\Model\Dto\ContractFormData;
use FGTCLB\AcademicPersonsEdit\Domain\Validator\ContractFormDataValidator;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Extbase\Annotation\Validate;

/**
 * @internal to be used only in `EXT:academic_person_edit` and not part of public API.
 */
final class ContractController extends AbstractActionController
{
    public function __construct(
        private readonly ContractFactory $contractFactory,
        private readonly ContractRepository $contractRepository,
        private readonly FunctionTypeRepository $functionTypeRepository,
        private readonly OrganisationalUnitRepository $organisationalUnitRepository,
        private readonly LocationRepository $locationRepository,
    ) {}

    // =================================================================================================================
    // Handle readonly display like list forms and detail view
    // =================================================================================================================

    public function listAction(Profile $profile): ResponseInterface
    {
        $this->userSessionService->saveRefererToSession($this->request);

        $this->view->assignMultiple([
            'data' => $this->getCurrentContentObjectRenderer()?->data,
            'profile' => $profile,
        ]);
        return $this->htmlResponse();
    }

    public function showAction(Contract $contract): ResponseInterface
    {
        $cancelUrl = $this->uriBuilder->reset()->uriFor(
            'show',
            ['profile' => $contract->getProfile()],
            'Profile'
        );

        $this->userSessionService->saveRefererToSession($this->request);

        $this->view->assignMultiple([
            'data' => $this->getCurrentContentObjectRenderer()?->data,
            'profile' => $contract->getProfile(),
            'contract' => $contract,
            'cancelUrl' => $cancelUrl,
        ]);
        return $this->htmlResponse();
    }

    // =================================================================================================================
    //  Handle creation of new entity
    // =================================================================================================================

    public function newAction(Profile $profile): ResponseInterface
    {
        $this->view->assignMultiple([
            'data' => $this->getCurrentContentObjectRenderer()?->data,
            'profile' => $profile,
            'contractFormData' => new ContractFormData(),
            'functionTypes' => $this->functionTypeRepository->findAll(),
            'organisationalUnits' => $this->organisationalUnitRepository->findAll(),
            'locations' => $this->locationRepository->findAll(),
            'cancelUrl' => $this->userSessionService->loadRefererFromSession($this->request),
            'validations' => $this->academicPersonsSettings->getValidationSetWithFallback('contract')->validations,
        ]);
        return $this->htmlResponse();
    }

    #[Validate([
        'param' => 'contractFormData',
        'validator' => ContractFormDataValidator::class,
    ])]
    public function createAction(Profile $profile, ContractFormData $contractFormData): ResponseInterface
    {
        $contract = $this->contractFactory->createFromFormData(
            $this->academicPersonsSettings->getValidationSetWithFallback('contract'),
            $profile,
            $contractFormData,
        );
        $maxSortingValue = 0;
        foreach ($profile->getContracts() as $existingContract) {
            $maxSortingValue = max($maxSortingValue, $existingContract->getSorting());
        }
        // Use next available sorting value
        $maxSortingValue += 1;
        $contract->setSorting($maxSortingValue);
        $this->contractRepository->add($contract);
        $this->persistenceManager->persistAll();

        $this->addTranslatedSuccessMessage('contract.create.success');

        if ($this->request->hasArgument('submit')
            && $this->request->getArgument('submit') === 'save-and-close'
        ) {
            return new RedirectResponse($this->userSessionService->loadRefererFromSession($this->request), 303);
        }
        return $this->createFormPersistencePrgRedirect('edit', ['contract' => $contract]);
    }

    // =================================================================================================================
    // Handle entity changes like displaying edit form and edit persistence.
    // =================================================================================================================

    public function editAction(Contract $contract): ResponseInterface
    {
        $this->view->assignMultiple([
            'data' => $this->getCurrentContentObjectRenderer()?->data,
            'profile' => $contract->getProfile(),
            'contract' => $contract,
            'contractFormData' => ContractFormData::createFromContract($contract),
            'functionTypes' => $this->functionTypeRepository->findAll(),
            'organisationalUnits' => $this->organisationalUnitRepository->findAll(),
            'locations' => $this->locationRepository->findAll(),
            'cancelUrl' => $this->userSessionService->loadRefererFromSession($this->request),
            'validations' => $this->academicPersonsSettings->getValidationSetWithFallback('contract')->validations,
        ]);
        return $this->htmlResponse();
    }

    #[Validate([
        'param' => 'contractFormData',
        'validator' => ContractFormDataValidator::class,
    ])]
    public function updateAction(Contract $contract, ContractFormData $contractFormData): ResponseInterface
    {
        $this->contractRepository->update(
            $this->contractFactory->updateFromFormData(
                $this->academicPersonsSettings->getValidationSetWithFallback('contract'),
                $contract,
                $contractFormData,
            ),
        );

        $this->addTranslatedSuccessMessage('contract.update.success');

        if ($this->request->hasArgument('submit')
            && $this->request->getArgument('submit') === 'save-and-close'
        ) {
            return new RedirectResponse($this->userSessionService->loadRefererFromSession($this->request));
        }
        return $this->createFormPersistencePrgRedirect('edit', ['contract' => $contract]);
    }

    public function sortAction(Contract $contract, string $sortDirection): ResponseInterface
    {
        $profile = $contract->getProfile();
        if ($profile === null) {
            // @todo Needs to be handled properly.
            throw new \RuntimeException(
                'Contract does not have a profile.',
                1752936133,
            );
        }

        if (!in_array($sortDirection, ['up', 'down', 'top', 'bottom'])
            || $profile->getContracts()->count() <= 1
        ) {
            $this->addTranslatedErrorMessage('contracts.sort.error.notPossible');
            return new RedirectResponse($this->userSessionService->loadRefererFromSession($this->request), 303);
        }

        // Convert contracts to array
        $sortingItemsArray = [];
        $targetItemIndex = null;

        $index = 0;
        foreach ($profile->getContracts() as $profileContract) {
            $sortingItemsArray[] = $profileContract;
            if ($contract->getUid() === $profileContract->getUid()) {
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
                $expectedSorting = ($idx + 1) * 10;

                if ($item->getSorting() !== $expectedSorting) {
                    $item->setSorting($expectedSorting);
                    $this->contractRepository->update($item);
                    $changed = true;
                }
            }

            if ($changed) {
                $this->persistenceManager->persistAll();
                $this->addTranslatedSuccessMessage('contract.sort.success');
            }
        }

        return new RedirectResponse($this->userSessionService->loadRefererFromSession($this->request), 303);
    }

    // =================================================================================================================
    // Handle destructive actions like deleting records
    // =================================================================================================================

    public function confirmDeleteAction(Contract $contract): ResponseInterface
    {
        $this->view->assignMultiple([
            'data' => $this->getCurrentContentObjectRenderer()?->data,
            'contract' => $contract,
        ]);
        return $this->htmlResponse();
    }

    public function deleteAction(Contract $contract): ResponseInterface
    {
        $this->contractRepository->remove($contract);
        $this->addTranslatedSuccessMessage('contract.delete.success');
        return new RedirectResponse($this->userSessionService->loadRefererFromSession($this->request), 303);
    }
}
