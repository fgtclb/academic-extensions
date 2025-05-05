<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons_edit" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersonsEdit\Controller;

use FGTCLB\AcademicPersons\Domain\Model\Profile;
use FGTCLB\AcademicPersons\Domain\Model\ProfileInformation;
use FGTCLB\AcademicPersons\Domain\Repository\ProfileInformationRepository;
use FGTCLB\AcademicPersons\Registry\AcademicPersonsSettingsRegistry;
use FGTCLB\AcademicPersonsEdit\Domain\Factory\ProfileInformationFactory;
use FGTCLB\AcademicPersonsEdit\Domain\Model\Dto\ProfileInformationFormData;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Annotation\Validate;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

final class ProfileInformationController extends AbstractActionController
{
    private ProfileInformationRepository $profileInformationRepository;
    public function injectProfileRepository(ProfileInformationRepository $profileInformationRepository)
    {
        $this->profileInformationRepository = $profileInformationRepository;
    }

    private AcademicPersonsSettingsRegistry $academicPersonsSettingsRegistry;
    public function injectAcademicPersonsSettingsRegistry(AcademicPersonsSettingsRegistry $academicPersonsSettingsRegistry)
    {
        $this->academicPersonsSettingsRegistry = $academicPersonsSettingsRegistry;
    }

    /**
     * -------------------------------------------------------------------------
     * Default actions (list)
     * -------------------------------------------------------------------------
     */

    public function listAction(Profile $profile, string $type): ResponseInterface
    {
        $this->userSessionService->saveRefererToSession($this->request);

        $this->view->assignMultiple([
            'profile' => $profile,
            'type' => $type,
            'profileInformations' => $profile->_getProperty($type),
        ]);

        return $this->htmlResponse();
    }

    /**
     * -------------------------------------------------------------------------
     * New actions
     * -------------------------------------------------------------------------
     */

    public function newAction(Profile $profile, string $type, ?ProfileInformationFormData $profileInformationFormData = null): ResponseInterface
    {
        if ($profileInformationFormData === null) {
            $profileInformationFormData = new ProfileInformationFormData;
        }

        $this->view->assignMultiple([
            'profile' => $profile,
            'type' => $type,
            'profileInformationFormData' => $profileInformationFormData,
            'cancelUrl' => $this->userSessionService->loadRefererFromSession($this->request),
            'validations' => $this->settingsRegistry->getValidationsForFrontend('profileInformation'),
        ]);

        return $this->htmlResponse();
    }

    public function createAction(Profile $profile, string $type, ProfileInformationFormData $profileInformationFormData): ResponseInterface
    {
        $profileInformationFactory = GeneralUtility::makeInstance(ProfileInformationFactory::class);
        $profileInformation = $profileInformationFactory->get($profileInformationFormData);
        $profileInformation->setProfile($profile);
        $profileInformation->setType($this->academicPersonsSettingsRegistry->getProfileInformationTypeMapping($type));
        $this->profileInformationRepository->add($profileInformation);
        $this->persistenceManager->persistAll();

        $this->addTranslatedSuccessMessage('profileInformation.success.create.done');

        if ($this->request->hasArgument('submit')
            && $this->request->getArgument('submit') === 'save-and-close'
        ) {
            return (new RedirectResponse($this->userSessionService->loadRefererFromSession($this->request)));
        }

        return (new ForwardResponse('edit'))->withArguments(['profileInformation' => $profileInformation]);
    }

    /**
     * -------------------------------------------------------------------------
     * Edit actions
     * -------------------------------------------------------------------------
     */

    public function editAction(ProfileInformation $profileInformation): ResponseInterface
    {        
        $this->view->assignMultiple([
            'profile' => $profileInformation->getProfile(),
            'profileInformation' => $profileInformation,
            'cancelUrl' => $this->userSessionService->loadRefererFromSession($this->request),
            'validations' => $this->settingsRegistry->getValidationsForFrontend('profileInformation'),
        ]);

        return $this->htmlResponse();
    }

    public function updateAction(ProfileInformation $profileInformation): ResponseInterface
    {
        $this->persistenceManager->update($profileInformation);

        $this->addTranslatedSuccessMessage('profileInformation.success.update.done');

        if ($this->request->hasArgument('submit')
            && $this->request->getArgument('submit') === 'save-and-close'
        ) {
            return (new RedirectResponse($this->userSessionService->loadRefererFromSession($this->request)));
        }

        return (new ForwardResponse('edit'))->withArguments(['profileInformation' => $profileInformation]);
    }

    /**
     * -------------------------------------------------------------------------
    * Delete actions
    * -------------------------------------------------------------------------
    */

    public function confirmDeleteAction(ProfileInformation $profileInformation): ResponseInterface
    {
        $this->view->assignMultiple([
            'profile' => $profileInformation->getProfile(),
            'profileInformation' => $profileInformation,
            'cancelUrl' => $this->userSessionService->loadRefererFromSession($this->request),
        ]);

        return $this->htmlResponse();
    }

    public function deleteAction(ProfileInformation $profileInformation): ResponseInterface
    {
        $this->profileInformationRepository->remove($profileInformation);

        $this->addTranslatedSuccessMessage('profileInformation.success.delete.done');

        return (new RedirectResponse($this->userSessionService->loadRefererFromSession($this->request)));
    }

    /**
     * ------------------------------------------------------------------------
    * Sort actions
    * ------------------------------------------------------------------------
    */

    public function sortAction(ProfileInformation $profileInformation, string $sortDirection): ResponseInterface
    {
        $profile = $profileInformation->getProfile();

        if (!in_array($sortDirection, ['up', 'down'])
            || $profile->getEmailAddresses()->count() <= 1
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
                 * Only switch sorting if the selected item is not the first one in the array
                 * (normally the sorting options for this case should be hidden/disabled in the Fluid template)
                 */
                if ($prevEmailAddress !== null) {
                    $prevSorting = $prevEmailAddress->getSorting();
                    $prevEmailAddress->setSorting($currentEmailAddress->getSorting());
                    $currentEmailAddress->setSorting($prevSorting);

                    $this->profileInformationRepository->update($prevEmailAddress);
                    $this->profileInformationRepository->update($currentEmailAddress);

                    $persistenceManager->persistAll();
                    $this->addTranslatedSuccessMessage('emailAddress.sort.success.done');
                }
                break;
            }
        }

        return (new RedirectResponse($this->userSessionService->loadRefererFromSession($this->request)));
    }
}
