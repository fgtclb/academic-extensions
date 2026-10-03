<?php

declare(strict_types=1);

namespace TESTS\FrontendIcons\EventListener;

use FGTCLB\AcademicBase\Event\CollectFrontendIconsEvent;
use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;
use TYPO3\CMS\Core\Attribute\AsEventListener;

#[AsEventListener(identifier: 'test-frontend-icons/contribute-frontend-icons')]
final readonly class ContributeFrontendIcons
{
    public function __invoke(CollectFrontendIconsEvent $event): void
    {
        $event->addIcon(
            'test-frontend-contributed',
            CurrentColorSvgIconProvider::class,
            ['source' => 'EXT:test_frontend_icons/Resources/Public/Icons/arrow.svg'],
        );
        $event->addIcon(
            'test-frontend-contributed-overridden',
            CurrentColorSvgIconProvider::class,
            ['source' => 'EXT:test_frontend_icons/Resources/Public/Icons/arrow.svg'],
        );
    }
}
