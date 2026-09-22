<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Tests\Unit\Event;

use FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface;
use FGTCLB\AcademicProjects\Domain\Model\Dto\ProjectDemand;
use FGTCLB\AcademicProjects\Domain\Model\Project;
use FGTCLB\AcademicProjects\Event\ModifyProjectListEvent;
use FGTCLB\CategoryTypes\Collection\CategoryCollection;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\View\ViewInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ModifyProjectListEventTest extends UnitTestCase
{
    /**
     * @param QueryResultInterface<int, Project>|null $projects
     */
    private function event(
        ?QueryResultInterface $projects = null,
        ?CategoryCollection $categories = null,
        ?ProjectDemand $demand = null,
        ?ViewInterface $view = null,
        ?PluginControllerActionContextInterface $context = null,
    ): ModifyProjectListEvent {
        return new ModifyProjectListEvent(
            $projects ?? $this->createStub(QueryResultInterface::class),
            $categories ?? new CategoryCollection(),
            $demand ?? new ProjectDemand(),
            $view ?? $this->createStub(ViewInterface::class),
            $context ?? $this->createStub(PluginControllerActionContextInterface::class),
        );
    }

    #[Test]
    public function getProjectsReturnsInstanceSetInConstructor(): void
    {
        $projects = $this->createStub(QueryResultInterface::class);
        $this->assertSame($projects, $this->event(projects: $projects)->getProjects());
    }

    #[Test]
    public function getProjectsReturnsTheResultAListenerReplacedItWith(): void
    {
        $replacement = $this->createStub(QueryResultInterface::class);
        $event = $this->event();
        $event->setProjects($replacement);
        $this->assertSame($replacement, $event->getProjects());
    }

    #[Test]
    public function getCategoriesReturnsInstanceSetInConstructor(): void
    {
        $categories = new CategoryCollection();
        $this->assertSame($categories, $this->event(categories: $categories)->getCategories());
    }

    #[Test]
    public function getCategoriesReturnsTheCollectionAListenerReplacedItWith(): void
    {
        $replacement = new CategoryCollection();
        $event = $this->event();
        $event->setCategories($replacement);
        $this->assertSame($replacement, $event->getCategories());
    }

    #[Test]
    public function getDemandReturnsInstanceSetInConstructor(): void
    {
        $demand = new ProjectDemand();
        $this->assertSame($demand, $this->event(demand: $demand)->getDemand());
    }

    #[Test]
    public function getViewReturnsInstanceSetInConstructor(): void
    {
        $view = $this->createStub(ViewInterface::class);
        $this->assertSame($view, $this->event(view: $view)->getView());
    }

    #[Test]
    public function getPluginControllerActionContextReturnsInstanceSetInConstructor(): void
    {
        $context = $this->createStub(PluginControllerActionContextInterface::class);
        $this->assertSame($context, $this->event(context: $context)->getPluginControllerActionContext());
    }
}
