..  _developers-event-modify-project-list-event:

======================
ModifyProjectListEvent
======================

:php:`\FGTCLB\AcademicProjects\Event\ModifyProjectListEvent` hands a listener
the projects a project list found, the categories its filter offers, and its
view. A listener replaces the projects, replaces the categories, or assigns
further variables to the view.

When it is dispatched
=====================

:php:`ProjectController::listAction()` dispatches the event once per rendering
of the :guilabel:`Projects` and the :guilabel:`Selected Projects` content
elements. It follows :ref:`developers-event-modify-project-demand-event`: the
projects are queried with the demand that event handed back, the categories
are computed from them, and then this event fires, before the action assigns
its view variables. :ref:`developers-event-modify-plugin-view-event` follows
once they are assigned.

What a listener reads and changes
=================================

..  list-table::
    :header-rows: 1
    :widths: 35 65

    *   -   Method
        -   What it does
    *   -   :php:`getProjects()`, :php:`setProjects()`
        -   The projects, an Extbase query result of
            :php:`\FGTCLB\AcademicProjects\Domain\Model\Project`. The setter
            takes a query result, not an array.
    *   -   :php:`getCategories()`, :php:`setCategories()`
        -   The categories of the group `projects` the filter offers, a
            :php:`\FGTCLB\CategoryTypes\Collection\CategoryCollection` in which
            the categories no listed project carries are marked disabled.
    *   -   :php:`getDemand()`
        -   The demand the projects were queried with, for reading.
    *   -   :php:`getView()`
        -   The view of the action. A variable assigned to it reaches the
            templates.
    *   -   :php:`getPluginControllerActionContext()`
        -   The context of the rendering, as in the demand event. The plugin
            name is :php:`ProjectList` or :php:`ProjectListSingle`.

The seven variables the action assigns after the event win over a variable of
the same name a listener assigned here: :html:`projects`, :html:`demand`,
:html:`categories`, :html:`filterTypes`, :html:`visitorSelection`,
:html:`data` and :html:`record`. The plugin view event, which runs last, can
replace them.

Example
=======

A site lists the projects of a funding programme and shows the sum of their
budgets above the cards, for whatever the visitor filtered. The listener adds
up the budgets of the projects the list shows and hands the sum to the view:

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/AddTotalProjectBudget.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\EventListener;

    use FGTCLB\AcademicProjects\Event\ModifyProjectListEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    final class AddTotalProjectBudget
    {
        #[AsEventListener(identifier: 'my-extension/add-total-project-budget')]
        public function __invoke(ModifyProjectListEvent $event): void
        {
            $total = 0.0;
            foreach ($event->getProjects() as $project) {
                $total += $project->getBudget();
            }
            $event->getView()->assign('projectsBudgetTotal', $total);
        }
    }

An override of :file:`Project/List.html` renders
:html:`{projectsBudgetTotal -> f:format.currency()}` above the list. The list
has no pagination, so the sum covers every project the visitor sees.

Rules and pitfalls
==================

**A replaced result is rendered as it is.** :php:`setProjects()` takes whatever
query result a listener hands back, and the ordering of the repository is not
reapplied to it. A listener that builds its own result gives it its own
ordering, or the list is in whatever order the database happens to return,
which is not the same list twice on PostgreSQL. The query of the handed
result, :php:`$event->getProjects()->getQuery()`, keeps the ordering when a
listener adds a constraint to it and executes it again.

**The categories are not recomputed.** They are computed from the queried
projects, once, before the list event. A listener that replaces the projects
and wants the filter of the plugin to match them sets the categories too:

..  code-block:: php

    $event->setProjects($narrowedResult);
    $event->setCategories(
        $this->categoryRepository->findAllApplicable('projects', ...array_values($narrowedResult->toArray())),
    );

:php:`findAllApplicable()` of
:php:`\FGTCLB\CategoryTypes\Domain\Repository\CategoryRepository` is what the
controller itself calls: it keeps every category of the group and marks the
ones no record carries as disabled options. :php:`findByGroupAndUidList()`
returns a bare list instead, so a listener that reaches for that one drops the
disabled options the filter otherwise shows.

**Hidden projects on TYPO3 v13.** With :guilabel:`Show hidden records`
switched on, the extension fetches the projects so that a translated page shows
the translation of a hidden project page. A result a listener executes itself
does not get that treatment on TYPO3 v13: a hidden project page whose
translation is hidden as well renders in the default language there. TYPO3 v14
handles it in the core, and nothing differs.

Introduced in
=============

*   :ref:`feature-list-plugin-events` added the event in 3.0.
*   :ref:`breaking-1791043407` made the controller final and names the event
    as the replacement of a subclass that changed the result or the view
    variables.
