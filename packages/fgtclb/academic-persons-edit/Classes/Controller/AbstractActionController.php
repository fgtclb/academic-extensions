<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons_edit" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fgtclb\AcademicPersonsEdit\Controller;

use Fgtclb\AcademicPersonsEdit\Configuration;
use Fgtclb\AcademicPersonsEdit\Service\UserSessionService;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Property\TypeConverter\DateTimeConverter;
use TYPO3\CMS\Frontend\Controller\ErrorController;

/**
 * AbstractActionController
 */
abstract class AbstractActionController extends ActionController
{
    public const FLASH_MESSAGE_QUEUE_IDENTIFIER = 'academic_profile';

    public function __construct(
        public readonly Context $context,
        public readonly PersistenceManager $persistenceManager,
        public readonly UserSessionService $userSessionService,
    ) {
    }

    public function initializeAction(): void
    {
        if ($this->context->getPropertyFromAspect('frontend.user', 'isLoggedIn', false) === false) {
            throw new PropagateResponseException(
                GeneralUtility::makeInstance(ErrorController::class)->accessDeniedAction(
                    $this->request,
                    'Authentication needed'
                ),
                1744109477
            );
        }

        // Map date and time arguments
        foreach (Configuration::DATETIME_ARGUMENTS as $argument => $datetimeProperties) {
            if ($this->arguments->hasArgument($argument)) {
                foreach ($datetimeProperties as $property => $format) {
                    $this->arguments->getArgument($argument)
                        ->getPropertyMappingConfiguration()
                        ->forProperty($property)
                        ->setTypeConverterOption(
                            DateTimeConverter::class,
                            DateTimeConverter::CONFIGURATION_DATE_FORMAT,
                            $format
                        );
                }
            }
        }
    }

    /**
     * Add translated success message to the flash message queue
     *
     * @param string $key
     */
    public function addTranslatedSuccessMessage(string $key): void
    {
        $this->addFlashMessage(
            $key,
            '',
            ContextualFeedbackSeverity::OK,
            true
        );
    }

    /**
     * Add translated error message to the flash message queue
     *
     * @param string $key
     */
    public function addTranslatedErrorMessage(string $key): void
    {
        // TODO: Add translation handling
        $this->addFlashMessage(
            $key,
            '',
            ContextualFeedbackSeverity::ERROR,
            true
        );
    }
}
