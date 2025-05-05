<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons_edit" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fgtclb\AcademicPersonsEdit\Controller;

use Fgtclb\AcademicPersons\Domain\Model\Contract;
use Fgtclb\AcademicPersons\Domain\Repository\ContractRepository;
use Fgtclb\AcademicPersons\Domain\Repository\FunctionTypeRepository;
use Fgtclb\AcademicPersons\Domain\Repository\OrganisationalUnitRepository;
use Fgtclb\AcademicPersonsEdit\Domain\Factory\ContractFactory;
use Fgtclb\AcademicPersonsEdit\Domain\Model\Profile;
use Fgtclb\AcademicPersonsEdit\Domain\Model\Dto\ContractFormData;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

final class ContractController extends AbstractActionController
{
    private ContractRepository $contractRepository;
    public function injectContractRepository(ContractRepository $contractRepository)
    {
        $this->contractRepository = $contractRepository;
    }

    private FunctionTypeRepository $functionTypeRepository;
    public function injectFunctionTypeRepository(FunctionTypeRepository $functionTypeRepository)
    {
        $this->functionTypeRepository = $functionTypeRepository;
    }

    private OrganisationalUnitRepository $organisationalUnitRepository;
    public function injectOrganisationalUnitRepository(OrganisationalUnitRepository $organisationalUnitRepository)
    {
        $this->organisationalUnitRepository = $organisationalUnitRepository;
    }

    /**
     * -------------------------------------------------------------------------
     * Default contract actions (list and show)
     * -------------------------------------------------------------------------
     */

    public function listAction(Profile $profile): ResponseInterface
    {
        $contracts = $this->contractRepository->findByProfile($profile);

        $this->userSessionService->saveRefererToSession($this->request);

        $this->view->assignMultiple([
            'profile' => $profile,
            'contracts' => $contracts,
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
     * Add contract actions
     * -------------------------------------------------------------------------
     */

     public function newAction(Profile $profile, ?ContractFormData $contractFormData = null): ResponseInterface
     {
        if ($contractFormData === null) {
            $contractFormData = new ContractFormData;
        }

        $this->view->assignMultiple([
            'profile' => $profile,
            'contractFormData' => $contractFormData,
            'cancelUrl' => $this->userSessionService->loadRefererFromSession($this->request),
        ]);
        return $this->htmlResponse();
    }

    public function createAction(Profile $profile, ContractFormData $contractFormData): ResponseInterface
    {
        $contractFactory = GeneralUtility::makeInstance(ContractFactory::class);
        $contract = $contractFactory->get($contractFormData);
        $contract->setProfile($profile);
        $this->contractRepository->add($contract);
        $this->persistenceManager->persistAll();

        $this->addTranslatedSuccessMessage('contracts.success.create.done');

        if ($this->request->hasArgument('submit')
            && $this->request->getArgument('submit') === 'save-and-close'
        ) {
            return (new RedirectResponse($this->userSessionService->loadRefererFromSession($this->request)));
        }

        return (new ForwardResponse('edit'))->withArguments(['contract' => $contract]);
    }

    /**
     * -------------------------------------------------------------------------
     * Edit contract actions
     * -------------------------------------------------------------------------
     */

    public function editAction(Contract $contract): ResponseInterface
    {
        $this->view->assignMultiple([
            'profile' => $contract->getProfile(),
            'contract' => $contract,
            'functionTypes' => $this->functionTypeRepository->findAll(),
            'organisationalUnits' => $this->organisationalUnitRepository->findAll(),
            'cancelUrl' => $this->userSessionService->loadRefererFromSession($this->request),
        ]);
        return $this->htmlResponse();
    }

    public function updateAction(Contract $contract): ResponseInterface
    {
        $this->contractRepository->update($contract);

        $this->addTranslatedSuccessMessage('contracts.success.update.done');

        if ($this->request->hasArgument('submit')
            && $this->request->getArgument('submit') === 'save-and-close'
        ) {
            return (new RedirectResponse($this->userSessionService->loadRefererFromSession($this->request)));
        }

        return (new ForwardResponse('edit'))->withArguments(['contract' => $contract]);
    }

    /**
     * -------------------------------------------------------------------------
     * Delete profile actions
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
        return (new RedirectResponse($this->userSessionService->loadRefererFromSession($this->request)));
    }

    /**
     * ------------------------------------------------------------------------
     * Sort contracts actions
     * ------------------------------------------------------------------------
     */

     public function sortContractsAction(Contract $contract, string $sortDirection): ResponseInterface
     {
         $profile = $contract->getProfile();
 
         if (!in_array($sortDirection, ['up', 'down'])
             || $profile->getContracts()->count() <= 1
         ) {
             $this->addTranslatedErrorMessage('contracts.error.sorting.notPossible');
             return (new RedirectResponse($this->userSessionService->loadRefererFromSession($this->request)));
         }
 
         // Convert contracts to array
         $contractsArray = [];
         foreach ($profile->getContracts() as $contract) {
             $contractsArray[] = $contract;
         }
 
         // Revert array, if sort direction is down
         if ($sortDirection === 'down') {
             $contractsArray = array_reverse($contractsArray);
         }
 
         // Switch sorting values
         $prevContract = null;
         $persistenceManager = GeneralUtility::makeInstance(PersistenceManager::class);
         foreach ($contractsArray as $currentContract) {
             if ($contract != $currentContract) {
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
                     $this->addTranslatedSuccessMessage('contracts.success.sorting.done');
                 }
                 break;
             }
         }
 
         return (new RedirectResponse($this->userSessionService->loadRefererFromSession($this->request)));
     }
}
