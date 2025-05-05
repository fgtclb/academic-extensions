<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons_edit" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersonsEdit\Controller;

use FGTCLB\AcademicPersons\Domain\Model\Email;
use FGTCLB\AcademicPersons\Domain\Model\Contract;
use FGTCLB\AcademicPersons\Domain\Repository\EmailRepository;
use FGTCLB\AcademicPersonsEdit\Domain\Factory\EmailFactory;
use FGTCLB\AcademicPersonsEdit\Domain\Model\Dto\EmailFormData;
use FGTCLB\AcademicPersonsEdit\Domain\Validator\UpdateEmailAdressValidator;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Annotation\Validate;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

final class EmailAddressController extends AbstractActionController
{
    private EmailRepository $emailAddressRepository;
    public function injectEmailRepository(EmailRepository $emailAddressRepository)
    {
        $this->emailAddressRepository = $emailAddressRepository;
    }

    /**
     * -------------------------------------------------------------------------
     * Default actions (list and show)
     * -------------------------------------------------------------------------
     */

    public function listAction(Contract $contract): ResponseInterface
    {
        $this->userSessionService->saveRefererToSession($this->request);

        $this->view->assignMultiple([
            'profile' => $contract->getProfile(),
            'contract' => $contract,
            'emailAddresses' => $contract->getEmailAddresses(),
        ]);
        return $this->htmlResponse();
    }

    public function showAction(Email $emailAddress): ResponseInterface
    {
        $this->userSessionService->saveRefererToSession($this->request);

        $this->view->assignMultiple([
            'profile' => $emailAddress->getContract()->getProfile(),
            'contract' => $emailAddress->getContract(),
            'emailAddress' => $emailAddress,
        ]);
        return $this->htmlResponse();
    }

    /**
     * -------------------------------------------------------------------------
     * New actions
     * -------------------------------------------------------------------------
     */

     public function newAction(Contract $contract, ?EmailFormData $emailAddressFormData = null): ResponseInterface
     {
        if ($emailAddressFormData === null) {
            $emailAddressFormData = new EmailFormData;
        }

        $this->view->assignMultiple([
            'profile' => $contract->getProfile(),
            'contract' => $contract,
            'emailAddressFormData' => $emailAddressFormData,
            'cancelUrl' => $this->userSessionService->loadRefererFromSession($this->request),
            'validations' => $this->settingsRegistry->getValidationsForFrontend('emailAddress'),
        ]);
        return $this->htmlResponse();
    }

    public function createAction(Contract $contract, EmailFormData $emailAddressFormData): ResponseInterface
    {
        $emailAddressFactory = GeneralUtility::makeInstance(EmailFactory::class);
        $emailAddress = $emailAddressFactory->get($emailAddressFormData);
        $emailAddress->setContract($contract);
        $this->emailAddressRepository->add($emailAddress);
        $this->persistenceManager->persistAll();

        $this->addTranslatedSuccessMessage('emailAddress.success.create.done');

        if ($this->request->hasArgument('submit')
            && $this->request->getArgument('submit') === 'save-and-close'
        ) {
            return (new RedirectResponse($this->userSessionService->loadRefererFromSession($this->request)));
        }

        return (new ForwardResponse('edit'))->withArguments(['emailAddress' => $emailAddress]);
    }

    /**
     * -------------------------------------------------------------------------
     * Edit actions
     * -------------------------------------------------------------------------
     */

    public function editAction(Email $emailAddress): ResponseInterface
    {        
        $this->view->assignMultiple([
            'profile' => $emailAddress->getContract()->getProfile(),
            'contract' => $emailAddress->getContract(),
            'emailAddress' => $emailAddress,
            'cancelUrl' => $this->userSessionService->loadRefererFromSession($this->request),
            'validations' => $this->settingsRegistry->getValidationsForFrontend('emailAddress'),
        ]);
        return $this->htmlResponse();
    }

    #[Validate([
        'param' => 'emailAddress',
        'validator' => UpdateEmailAdressValidator::class,
    ])]
    public function updateAction(Email $emailAddress): ResponseInterface
    {
        $this->emailAddressRepository->update($emailAddress);

        $this->addTranslatedSuccessMessage('emailAddress.success.update.done');

        if ($this->request->hasArgument('submit')
            && $this->request->getArgument('submit') === 'save-and-close'
        ) {
            return (new RedirectResponse($this->userSessionService->loadRefererFromSession($this->request)));
        }

        return (new ForwardResponse('edit'))->withArguments(['emailAddress' => $emailAddress]);
    }

    /**
     * -------------------------------------------------------------------------
     * Delete actions
     * -------------------------------------------------------------------------
     */

    public function confirmDeleteAction(Email $emailAddress): ResponseInterface
    {
        $this->view->assignMultiple([
            'profile' => $emailAddress->getContract()->getProfile(),
            'contract' => $emailAddress->getContract(),
            'emailAddress' => $emailAddress,
            'cancelUrl' => $this->userSessionService->loadRefererFromSession($this->request),
        ]);
        return $this->htmlResponse();
    }

    public function deleteAction(Email $emailAddress): ResponseInterface
    {
        $this->emailAddressRepository->remove($emailAddress);
        $this->addTranslatedSuccessMessage('emailAddress.success.delete.done');
        return (new RedirectResponse($this->userSessionService->loadRefererFromSession($this->request)));
    }

    /**
     * ------------------------------------------------------------------------
     * Sort actions
     * ------------------------------------------------------------------------
     */

     public function sortAction(Email $emailAddress, string $sortDirection): ResponseInterface
     {
         $contract = $emailAddress->getContract();
 
         if (!in_array($sortDirection, ['up', 'down'])
             || $contract->getEmailAddresses()->count() <= 1
         ) {
             $this->addTranslatedErrorMessage('emailAddress.sort.error.notPossible');
             return (new RedirectResponse($this->userSessionService->loadRefererFromSession($this->request)));
         }
 
         // Convert contracts to array
         $emailAddressArray = [];
         foreach ($contract->getEmailAddresses() as $emailAddress) {
             $emailAddressArray[] = $emailAddress;
         }
 
         // Revert array, if sort direction is down
         if ($sortDirection === 'down') {
             $emailAddressArray = array_reverse($emailAddressArray);
         }
 
         // Switch sorting values
         $prevEmailAddress = null;
         $persistenceManager = GeneralUtility::makeInstance(PersistenceManager::class);
         foreach ($emailAddressArray as $currentEmailAddress) {
             if ($emailAddress != $currentEmailAddress) {
                 $prevEmailAddress = $currentEmailAddress;
             } else {
                 /**
                  * Only switch sorting if the selected contract is not the first one in the array
                  * (normally the sorting options for this case should be hidden in the Fluid template)
                  */
                 if ($prevEmailAddress !== null) {
                     $prevSorting = $prevEmailAddress->getSorting();
                     $prevEmailAddress->setSorting($currentEmailAddress->getSorting());
                     $currentEmailAddress->setSorting($prevSorting);
 
                     $this->emailAddressRepository->update($prevEmailAddress);
                     $this->emailAddressRepository->update($currentEmailAddress);
 
                     $persistenceManager->persistAll();
                     $this->addTranslatedSuccessMessage('emailAddress.sort.success.done');
                 }
                 break;
             }
         }
 
         return (new RedirectResponse($this->userSessionService->loadRefererFromSession($this->request)));
     }
}
