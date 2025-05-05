<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons_edit" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fgtclb\AcademicPersonsEdit\Controller;

use Fgtclb\AcademicPersonsEdit\Domain\Model\Profile;
use Fgtclb\AcademicPersonsEdit\Domain\Repository\ProfileRepository;
use Fgtclb\AcademicPersonsEdit\Domain\Validator\UpdateProfileValidator;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Extbase\Annotation\Validate;
use TYPO3\CMS\Extbase\Http\ForwardResponse;

final class ProfileController extends AbstractActionController
{
    private ProfileRepository $profileRepository;

    public function injectProfileRepository(ProfileRepository $profileRepository)
    {
        $this->profileRepository = $profileRepository;
    }

    public function listAction(): ResponseInterface
    {
        $profiles = $this->profileRepository->findByFrontendUser(
            $this->context->getPropertyFromAspect('frontend.user', 'id')
        );

        $this->userSessionService->saveRefererToSession($this->request);

        $this->view->assignMultiple([
            'profiles' => $profiles,
        ]);
        return $this->htmlResponse();
    }

    public function showAction(Profile $profile): ResponseInterface
    {
        $this->userSessionService->saveRefererToSession($this->request);

        $this->view->assignMultiple([
            'profile' => $profile,
        ]);
        return $this->htmlResponse();
    }

    /**
     * ------------------------------------------------------------------------
     * Edit profile actions
     * ------------------------------------------------------------------------
     */

    public function editAction(Profile $profile): ResponseInterface
    {
        $this->view->assignMultiple([
            'profile' => $profile,
            'cancelUrl' => $this->userSessionService->loadRefererFromSession($this->request),
        ]);
        return $this->htmlResponse();
    }

    #[Validate([
        'param' => 'profile',
        'validator' => UpdateProfileValidator::class,
    ])]
    public function updateAction(Profile $profile): ResponseInterface
    {
        $this->profileRepository->update($profile);

        if ($this->request->hasArgument('submit')
            && $this->request->getArgument('submit') === 'save-and-close'
        ) {
            return (new RedirectResponse($this->userSessionService->loadRefererFromSession($this->request)));
        }

        return (new ForwardResponse('edit'))->withArguments(['profile' => $profile]);
    }
}
