<?php

declare(strict_types=1);

namespace TESTS\FrontendIcons\EventListener;

use FGTCLB\AcademicBase\Event\ModifyFrontendIconAllowListEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;

/**
 * What a project does to serve the icons of its own extension: add the prefix of its
 * identifiers to the allow-list.
 */
#[AsEventListener(identifier: 'test-frontend-icons/allow-project-icons')]
final readonly class AllowProjectIcons
{
    public function __invoke(ModifyFrontendIconAllowListEvent $event): void
    {
        $event->addPrefix('tx-testproject-');
    }
}
