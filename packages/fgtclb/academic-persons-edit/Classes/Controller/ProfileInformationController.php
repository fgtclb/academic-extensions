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
use FGTCLB\AcademicPersonsEdit\Domain\Factory\ProfileInformationFactory;
use FGTCLB\AcademicPersonsEdit\Domain\Model\Dto\ProfileInformationFormData;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Extbase\Http\ForwardResponse;

final class ProfileInformationController extends AbstractActionController
{
    private ProfileInformationFactory $profileInformationFactory;
    public function injectProfileInformationFactory(ProfileInformationFactory $profileInformationFactory): void
    {
        $this->profileInformationFactory = $profileInformationFactory;
    }

    private ProfileInformationRepository $profileInformationRepository;
    public function injectProfileRepository(ProfileInformationRepository $profileInformationRepository): void
    {
        $this->profileInformationRepository = $profileInformationRepository;
    }

    /**
     * -------------------------------------------------------------------------
     * Default actions (list)
     * -------------------------------------------------------------------------
     */

    /**
     * @param non-empty-string $type
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
        $this->view->assignMultiple([
            'profile' => $profile,
            'type' => $type,
            'profileInformationFormData' => $profileInformationFormData ?? ProfileInformationFormData::createEmpty(),
            'cancelUrl' => $this->userSessionService->loadRefererFromSession($this->request),
            'validations' => $this->settingsRegistry->getValidationsForFrontend('profileInformation'),
        ]);

        return $this->htmlResponse();
    }

    public function createAction(Profile $profile, string $type, ProfileInformationFormData $profileInformationFormData): ResponseInterface
    {
        $profileInformation = $this->profileInformationFactory->createFromFormData(
            $profile,
            $this->settingsRegistry->getProfileInformationTypeMapping($type),
            $profileInformationFormData
        );
        $this->profileInformationRepository->add($profileInformation);
        $this->persistenceManager->persistAll();

        $this->addTranslatedSuccessMessage('profileInformation.success.create.done');

        if ($this->request->hasArgument('submit')
            && $this->request->getArgument('submit') === 'save-and-close'
        ) {
            return new RedirectResponse($this->userSessionService->loadRefererFromSession($this->request));
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
            'profileInformationFormData' => ProfileInformationFormData::createFromProfileInformation($profileInformation),
            'cancelUrl' => $this->userSessionService->loadRefererFromSession($this->request),
            'validations' => $this->settingsRegistry->getValidationsForFrontend('profileInformation'),
        ]);

        return $this->htmlResponse();
    }

    public function updateAction(
        ProfileInformation $profileInformation,
    ): ResponseInterface {
        $this->persistenceManager->update($profileInformation);

        $this->addTranslatedSuccessMessage('profileInformation.success.update.done');

        if ($this->request->hasArgument('submit')
            && $this->request->getArgument('submit') === 'save-and-close'
        ) {
            return new RedirectResponse($this->userSessionService->loadRefererFromSession($this->request));
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

        return new RedirectResponse($this->userSessionService->loadRefererFromSession($this->request));
    }

    /**
     * ------------------------------------------------------------------------
     * Sort actions
     * @todo Implement type aware sorting and activate sorting options in templare.
     * ------------------------------------------------------------------------
     */
    public function sortAction(ProfileInformation $profileInformation, string $sortDirection): ResponseInterface
    {
        $sortingItemFromForm = $profileInformation;
        $profile = $sortingItemFromForm->getProfile();
        $sortingItems = $this->profileInformationRepository->findByProfileAndType(
            $profile,
            $sortingItemFromForm->getType()
        );

        if (!in_array($sortDirection, ['up', 'down'])
            || $sortingItems->count() <= 1
        ) {
            $this->addTranslatedErrorMessage('profileInformations.sort.error.notPossible');
            return new RedirectResponse($this->userSessionService->loadRefererFromSession($this->request));
        }

        // Convert profile informations to array
        $sortingItemsArray = [];
        foreach ($sortingItems as $item) {
            $sortingItemsArray[] = $item;
        }

        // Revert array, if sort direction is down
        if ($sortDirection === 'down') {
            $sortingItemsArray = array_reverse($sortingItemsArray);
        }

        // Switch sorting values
        $prevItem = null;
        foreach ($sortingItemsArray as $currentItem) {
            if ($sortingItemFromForm != $currentItem) {
                $prevItem = $currentItem;
            } else {
                /**
                 * Only switch sorting if the selected contract is not the first one in the array
                 * (normally the sorting options for this case should be hidden in the Fluid template)
                 */
                if ($prevItem !== null) {
                    $prevSorting = $prevItem->getSorting();
                    $prevItem->setSorting($currentItem->getSorting());
                    $currentItem->setSorting($prevSorting);

                    $this->profileInformationRepository->update($prevItem);
                    $this->profileInformationRepository->update($currentItem);

                    $this->persistenceManager->persistAll();
                    $this->addTranslatedSuccessMessage('contracts.sort.success.done');
                }
                break;
            }
        }

        return new RedirectResponse($this->userSessionService->loadRefererFromSession($this->request));
    }
}
