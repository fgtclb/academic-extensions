<?php

declare(strict_types=1);

namespace TESTS\TestProjectListEvents\EventListener;

use FGTCLB\AcademicProjects\Event\ModifyProjectListEvent;
use FGTCLB\CategoryTypes\Domain\Repository\CategoryRepository;
use TYPO3\CMS\Core\Attribute\AsEventListener;

/**
 * Changes the queried projects and the view, driven by plugin settings - see
 * {@see ProjectDemandListener} for why.
 */
final class ProjectListListener
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
    ) {}

    #[AsEventListener(identifier: 'test-project-list-events/list')]
    public function __invoke(ModifyProjectListEvent $event): void
    {
        $context = $event->getPluginControllerActionContext();
        $settings = $context->getSettings();

        // An additional view variable, rendered by the template this fixture extension
        // ships. The plugin name tells the two project list plugins apart.
        $marker = (string)($settings['testProjectListMarker'] ?? '');
        if ($marker !== '') {
            $event->getView()->assign('testProjectListMarker', sprintf(
                '%s|%s|%d',
                $marker,
                $context->getPluginName() ?? '',
                count($event->getProjects()->toArray()),
            ));
        }

        // Replaces the result with a subset. The setter is typed to a query result, so the
        // subset is narrowed on the query the result came from - and narrowed beside the
        // condition the repository built, not instead of it.
        $keptTitle = (string)($settings['testProjectListKeepTitle'] ?? '');
        if ($keptTitle !== '') {
            $query = $event->getProjects()->getQuery();
            $constraint = $query->getConstraint();
            $subset = $query->equals('title', $keptTitle);
            $query->matching($constraint === null ? $subset : $query->logicalAnd($constraint, $subset));
            $event->setProjects($query->execute());
        }

        // Replaces the applicable categories, which is what a listener that narrowed the
        // result has to do for the filter of the plugin to match it - the controller does
        // not recompute them.
        $category = (int)($settings['testProjectListCategory'] ?? 0);
        if ($category > 0) {
            $event->setCategories($this->categoryRepository->findByGroupAndUidList('projects', [$category]));
        }
    }
}
