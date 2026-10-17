..  _developers-event-modify-plugin-view-event:

=====================
ModifyPluginViewEvent
=====================

:php:`\FGTCLB\AcademicBase\Event\ModifyPluginViewEvent` belongs to
:guilabel:`academic_base`, and the plugins of seven academic extensions
dispatch it. It hands a listener the view of a plugin right before it
renders, so the listener can assign further variables that a template override
renders. The event, its methods and the context it carries are described in
the :ref:`documentation of academic_base
<fgtclb/academic-base:developers-event-modify-plugin-view-event>`.
This page states what the plugins of :guilabel:`academic_projects` pass in.

When it is dispatched
=====================

Both content elements dispatch the event once per rendering, from
:php:`ProjectController::listAction()`, after
:ref:`developers-event-modify-project-list-event` and after the action assigned
its own variables:

..  list-table::
    :header-rows: 1
    :widths: 35 35 30

    *   -   Content element
        -   :php:`getPluginName()`
        -   :php:`getActionName()`
    *   -   :guilabel:`Projects`
        -   ``ProjectList``
        -   ``list``
    *   -   :guilabel:`Selected Projects`
        -   ``ProjectListSingle``
        -   ``list``

:php:`getControllerExtensionName()` returns ``AcademicProjects`` for both. A
submission of the filter form is answered with a redirect and renders nothing,
so it dispatches no event. The project page is no plugin and dispatches no
event.

What this extension passes in
=============================

The view holds these variables when the event fires: :html:`projects`,
:html:`demand`, :html:`categories`, :html:`filterTypes`,
:html:`visitorSelection`, :html:`data` (the content element record as an
array), :html:`record` and the :html:`settings`. A listener that assigns one of
them again replaces it in the templates.

:php:`getSettings()` of the context returns the settings of the content
element merged over :typoscript:`plugin.tx_academicprojects.settings`, the
values of the site settings included.

Example
=======

A site places a :guilabel:`Selected Projects` element on each project page to
show related projects, and wants the card of the project the visitor is on to
look different, or to be left out. The listener hands the uid of the page
being rendered to the view:

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/AddCurrentPageToSelectedProjects.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\EventListener;

    use FGTCLB\AcademicBase\Event\ModifyPluginViewEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Frontend\Page\PageInformation;

    final class AddCurrentPageToSelectedProjects
    {
        #[AsEventListener(identifier: 'my-extension/add-current-page-to-selected-projects')]
        public function __invoke(ModifyPluginViewEvent $event): void
        {
            $context = $event->getPluginControllerActionContext();
            if ($context->getControllerExtensionName() !== 'AcademicProjects'
                || $context->getPluginName() !== 'ProjectListSingle'
            ) {
                return;
            }
            $pageInformation = $context->getRequest()->getAttribute('frontend.page.information');
            if ($pageInformation instanceof PageInformation) {
                $event->getView()->assign('currentPageUid', $pageInformation->getId());
            }
        }
    }

An override of :file:`Project/Item.html` compares :html:`{project.uid}` with
:html:`{currentPageUid}`. Both are uids of the default language, on a
translated page as well.

Rules and pitfalls
==================

*   The event fires for the plugins of seven academic extensions. Check the
    extension name and the plugin name before acting, as the example does.
*   A variable the action assigned is replaced only in the view. The query has
    run already: projects a listener assigns as :html:`projects` are rendered,
    but the filter keeps offering the categories of the queried projects. A
    change of which projects are shown belongs in
    :ref:`developers-event-modify-project-demand-event` or
    :ref:`developers-event-modify-project-list-event`.

Introduced in
=============

*   :ref:`feature-plugin-view-event` made both content elements dispatch the
    event in 3.0.
*   :ref:`breaking-1791043407` made the controller final, which leaves the
    events as the way to add view variables.
*   The event itself was introduced by
    :ref:`academic_base 3.0 <fgtclb/academic-base:feature-modify-plugin-view-event>`.
