<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\EventListener;

use FGTCLB\AcademicProjects\Enumeration\PageTypes;
use FGTCLB\CategoryTypes\Backend\PageCategorySummaryRenderer;
use TYPO3\CMS\Backend\Controller\Event\ModifyPageLayoutContentEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;

/**
 * Shows the categories of a project page in the page module, above the content grid.
 *
 * The summary itself is `EXT:category_types`, which renders the same table for the page types
 * of this extension, of `EXT:academic_programs` and of `EXT:academic_partners`. All this
 * listener contributes are the two constants that make it a project summary: the page type and
 * the category group.
 *
 * It needs no condition of its own. `renderForPageOfType()` answers with an empty string for
 * every page that is not of the given type, and `addHeaderContent()` appends an empty string
 * without a trace - so the page module of a standard page is byte for byte what it was.
 *
 * `addHeaderContent()` and not `setHeaderContent()`: the header of the page module is shared
 * with the listeners of every other extension, and setting replaces what they contributed.
 *
 * Until ACE-689 this extension shipped the summary as
 * `Resources/Private/Backend/Partials/PageLayout/Doktype30.html`, registered in
 * `Configuration/page.tsconfig` as `templates.typo3/cms-backend.academic-projects`. No template of
 * `EXT:backend` renders a `PageLayout/Doktype*` partial, so nothing rendered it.
 *
 * It never rendered. `784742607` (2023-09-19) added it as a partial from the start,
 * together with a `module.tx_backend.view.partialRootPaths` registration - the shape
 * `EXT:academic_programs` had been left in by a rename six months earlier, where the same
 * markup had been a working page module template override. TYPO3 v12.0 then dropped
 * `module.tx_backend.view` entirely (Breaking: #96812), and the
 * `templates.typo3/cms-backend.*` line is its replacement - pointing at the same
 * unrendered partial. All three are gone now, the `ext_typoscript_setup.typoscript`
 * this extension was born with included.
 */
#[AsEventListener(identifier: 'academic-projects/page-module-category-summary')]
final readonly class AddPageModuleCategorySummary
{
    private const CATEGORY_GROUP = 'projects';

    public function __construct(
        private PageCategorySummaryRenderer $pageCategorySummaryRenderer,
    ) {}

    public function __invoke(ModifyPageLayoutContentEvent $event): void
    {
        $event->addHeaderContent($this->pageCategorySummaryRenderer->renderForPageOfType(
            $event->getRequest(),
            PageTypes::TYPE_ACEDEMIC_PROJECT,
            self::CATEGORY_GROUP,
        ));
    }
}
