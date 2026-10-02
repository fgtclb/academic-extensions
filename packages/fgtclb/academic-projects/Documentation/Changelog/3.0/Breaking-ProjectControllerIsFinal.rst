.. _breaking-1791043407:

=========================================
Breaking: The project controller is final
=========================================

Description
===========

:php:`\FGTCLB\AcademicProjects\Controller\ProjectController` is :php:`final`.
It serves both project list plugins, `ProjectList` and `ProjectListSingle`.

Its collaborators are private constructor arguments now. The methods
:php:`injectFilterRedirectExtensionService()` and
:php:`injectFilterTypeResolver()` are removed, and
:php:`redirectFilterSubmission()` and :php:`pluginControllerActionContext()`
are private. All of them existed only so that a subclass could keep calling the
old constructor and the helpers of the shipped action.

Every plugin controller of the academic extensions is final in 3.0. A plugin is
extended through its events, not through a subclass of its controller.

Impact
======

A class that extends :php:`ProjectController` stops loading with a fatal
error, :php:`Class ... cannot extend final class
FGTCLB\AcademicProjects\Controller\ProjectController`. That happens as soon as
anything loads the subclass, a plugin registered with it renders, or the
container is built with it.

An XCLASS of the controller fails the same way. The upgrade check
:bash:`academic:upgrade:check` of :guilabel:`EXT:academic_base` reports it as
an error. A :php:`configurePlugin()` call that points one of the two plugins
at a subclass is not reported.

The plugins, their templates and their settings are unchanged. The behaviour
is the same on TYPO3 v13 and v14.

Affected Installations
======================

Installations with a class that extends :php:`ProjectController`, registered
for a plugin through :php:`ExtensionUtility::configurePlugin()`, as an XCLASS,
or in the service container.

Migration
=========

Remove the subclass, and the :php:`configurePlugin()` call or the XCLASS
registration that points at it, so the shipped controller serves the plugin
again. Move each override to its replacement:

*   Changing the filter, the status or the sorting before the query: a
    listener of :php:`\FGTCLB\AcademicProjects\Event\ModifyProjectDemandEvent`,
    which adjusts or replaces the demand. See :ref:`feature-list-plugin-events`.
*   A replaced or reduced result, other filter categories, or additional view
    variables: a listener of
    :php:`\FGTCLB\AcademicProjects\Event\ModifyProjectListEvent`.
*   Filter selections that can be bookmarked: the list answers a filter
    submission with a redirect to a GET URL, see :ref:`feature-1790226101`.

A subclass that adds a view variable:

..  code-block:: php
    :caption: Before: EXT:my_extension/Classes/Controller/ProjectController.php

    final class ProjectController extends \FGTCLB\AcademicProjects\Controller\ProjectController
    {
        public function listAction(?array $demand = null): ResponseInterface
        {
            $this->view->assign('fundingBodies', $this->fundingBodyRepository->findAll());
            return parent::listAction($demand);
        }
    }

does the same as a listener:

..  code-block:: php
    :caption: After: EXT:my_extension/Classes/EventListener/AddFundingBodiesToProjectList.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\EventListener;

    use FGTCLB\AcademicProjects\Event\ModifyProjectListEvent;
    use MyVendor\MyExtension\Domain\Repository\FundingBodyRepository;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    final readonly class AddFundingBodiesToProjectList
    {
        public function __construct(
            private FundingBodyRepository $fundingBodyRepository,
        ) {}

        #[AsEventListener(identifier: 'my-extension/add-funding-bodies-to-project-list')]
        public function __invoke(ModifyProjectListEvent $event): void
        {
            $event->getView()->assign('fundingBodies', $this->fundingBodyRepository->findAll());
        }
    }

The listener acts on both plugins. It reads
:php:`$event->getPluginControllerActionContext()->getPluginName()` where it
means only one of them.

..  index:: Frontend, PHP-API, NotScanned, ext:academic_projects
