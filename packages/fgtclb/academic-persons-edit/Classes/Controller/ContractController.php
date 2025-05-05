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
use FGTCLB\AcademicPersons\Domain\Repository\OrganisationalUnitRepository;
use FGTCLB\AcademicPersonsEdit\Domain\Factory\ContractFactory;
use FGTCLB\AcademicPersonsEdit\Domain\Model\Dto\ContractFormData;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

final class ContractController extends AbstractActionController
{
    private ContractFactory $contractFactory;
    public function injectContractFactory(ContractFactory $contractFactory): void
    {
        $this->contractFactory = $contractFactory;
    }

    private ContractRepository $contractRepository;
    public function injectContractRepository(ContractRepository $contractRepository): void
    {
        $this->contractRepository = $contractRepository;
    }

    private FunctionTypeRepository $functionTypeRepository;
    public function injectFunctionTypeRepository(FunctionTypeRepository $functionTypeRepository): void
    {
        $this->functionTypeRepository = $functionTypeRepository;
    }

    private OrganisationalUnitRepository $organisationalUnitRepository;
    public function injectOrganisationalUnitRepository(OrganisationalUnitRepository $organisationalUnitRepository): void
    {
        $this->organisationalUnitRepository = $organisationalUnitRepository;
    }

    /**
     * -------------------------------------------------------------------------
     * Default actions (list and show)
     * -------------------------------------------------------------------------
     */
    public function listAction(Profile $profile): ResponseInterface
    {
        $this->userSessionService->saveRefererToSession($this->request);

        $this->view->assignMultiple([
            'profile' => $profile,
        ]);
        return $this->htmlResponse();
    }

    public function showAction(Contract $contract): ResponseInterface
    {
        $this->userSessionService->saveRefererToSession($this->request);

        $this->view->assignMultiple([
            'profile' => $contract->getProfile(),
            'contract' => $contract,
        ]);
        return $this->htmlResponse();
    }

    /**
     * -------------------------------------------------------------------------
     * New actions
     * -------------------------------------------------------------------------
     */
    public function newAction(Profile $profile, ?ContractFormData $contractFormData = null): ResponseInterface
    {
        $this->view->assignMultiple([
            'profile' => $profile,
            'contractFormData' => $contractFormData ?? ContractFormData::createEmpty(),
            'functionTypes' => $this->functionTypeRepository->findAll(),
            'organisationalUnits' => $this->organisationalUnitRepository->findAll(),
            'cancelUrl' => $this->userSessionService->loadRefererFromSession($this->request),
            'validations' => $this->settingsRegistry->getValidationsForFrontend('contract'),
        ]);
        return $this->htmlResponse();
    }

    public function createAction(Profile $profile, ContractFormData $contractFormData): ResponseInterface
    {
        $contract = $this->contractFactory->createFromFormData(
            $profile,
            $contractFormData
        );
        $this->contractRepository->add($contract);
        $this->persistenceManager->persistAll();

        $this->addTranslatedSuccessMessage('contracts.success.create.done');

        if ($this->request->hasArgument('submit')
            && $this->request->getArgument('submit') === 'save-and-close'
        ) {
            return new RedirectResponse($this->userSessionService->loadRefererFromSession($this->request));
        }

        return (new ForwardResponse('edit'))->withArguments(['contract' => $contract]);
    }

    /**
     * -------------------------------------------------------------------------
     * Edit actions
     * -------------------------------------------------------------------------
     */
    public function editAction(Contract $contract): ResponseInterface
    {
        $this->view->assignMultiple([
            'profile' => $contract->getProfile(),
            'contract' => $contract,
            'contractFormData' => ContractFormData::createFromContract($contract),
            'functionTypes' => $this->functionTypeRepository->findAll(),
            'organisationalUnits' => $this->organisationalUnitRepository->findAll(),
            'cancelUrl' => $this->userSessionService->loadRefererFromSession($this->request),
            'validations' => $this->settingsRegistry->getValidationsForFrontend('contract'),
        ]);
        return $this->htmlResponse();
    }

    public function updateAction(Contract $contract, ContractFormData $contractFormData): ResponseInterface
    {
        $this->contractRepository->update(
            $this->contractFactory->updateFromFormData($contract, $contractFormData)
        );

        $this->addTranslatedSuccessMessage('contracts.success.update.done');

        if ($this->request->hasArgument('submit')
            && $this->request->getArgument('submit') === 'save-and-close'
        ) {
            return new RedirectResponse($this->userSessionService->loadRefererFromSession($this->request));
        }

        return (new ForwardResponse('edit'))->withArguments(['contract' => $contract]);
    }

    /**
     * -------------------------------------------------------------------------
     * Delete actions
     * -------------------------------------------------------------------------
     */
    public function confirmDeleteAction(Contract $contract): ResponseInterface
    {
        $this->view->assignMultiple([
            'contract' => $contract,
        ]);
        return $this->htmlResponse();
    }

    public function deleteAction(Contract $contract): ResponseInterface
    {
        $this->contractRepository->remove($contract);
        $this->addTranslatedSuccessMessage('contracts.success.delete.done');
        return new RedirectResponse($this->userSessionService->loadRefererFromSession($this->request));
    }

    /**
     * ------------------------------------------------------------------------
     * Sort actions
     * ------------------------------------------------------------------------
     */
    public function sortAction(Contract $contractFromForm, string $sortDirection): ResponseInterface
    {
        $profile = $contractFromForm->getProfile();

        if (!in_array($sortDirection, ['up', 'down'])
            || $profile->getContracts()->count() <= 1
        ) {
            $this->addTranslatedErrorMessage('contracts.sort.error.notPossible');
            return new RedirectResponse($this->userSessionService->loadRefererFromSession($this->request));
        }

        // Convert contracts to array
        $contractsArray = [];
        foreach ($profile->getContracts() as $contract) {
            $contractsArray[$contract->getUid()] = $contract;
        }

        // Revert array, if sort direction is down
        if ($sortDirection === 'down') {
            $contractsArray = array_reverse($contractsArray, true);
        }

        // Switch sorting values
        $prevContract = null;
        $persistenceManager = GeneralUtility::makeInstance(PersistenceManager::class);
        foreach ($contractsArray as $currentContract) {
            if ($contractFromForm != $currentContract) {
                $prevContract = $currentContract;
            } else {
                /**
                 * Only switch sorting if the selected contract is not the first one in the array
                 * (normally the sorting options for this case should be hidden in the Fluid template)
                 */
                if ($prevContract !== null) {
                    $prevSorting = $prevContract->getSorting();
                    $prevContract->setSorting($currentContract->getSorting());
                    $currentContract->setSorting($prevSorting);

                    $this->contractRepository->update($prevContract);
                    $this->contractRepository->update($currentContract);

                    $persistenceManager->persistAll();
                    $this->addTranslatedSuccessMessage('contracts.sort.success.done');
                }
                break;
            }
        }

        return new RedirectResponse($this->userSessionService->loadRefererFromSession($this->request));
    }
}
