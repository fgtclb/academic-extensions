.. _breaking-1791043409:

============================================
Breaking: The B-ITE jobs controller is final
============================================

Description
===========

:php:`\FGTCLB\AcademicBiteJobs\Controller\BiteJobsController` is
:php:`final`. It serves the job list plugin `List`. Its job service is a
private constructor argument now.

Every plugin controller of the academic extensions is final in 3.0. A plugin is
extended through its events, not through a subclass of its controller.

Impact
======

A class that extends :php:`BiteJobsController` stops loading with a fatal
error, :php:`Class ... cannot extend final class
FGTCLB\AcademicBiteJobs\Controller\BiteJobsController`. That happens as soon
as anything loads the subclass, a plugin registered with it renders, or the
container is built with it.

An XCLASS of the controller fails the same way. The upgrade check
:bash:`academic:upgrade:check` of :guilabel:`EXT:academic_base` reports it as
an error. A :php:`configurePlugin()` call that points the plugin at a subclass
is not reported.

The plugin, its templates and its settings are unchanged. The behaviour is the
same on TYPO3 v13 and v14.

Affected Installations
======================

Installations with a class that extends :php:`BiteJobsController`, registered
for the plugin through :php:`ExtensionUtility::configurePlugin()`, as an
XCLASS, or in the service container.

Migration
=========

Remove the subclass, and the :php:`configurePlugin()` call or the XCLASS
registration that points at it, so the shipped controller serves the plugin
again. Move each override to its replacement, see :ref:`feature-1790774400`:

*   Changing the request to B-ITE, its filter, channel, locale, sorting or
    paging: a listener of
    :php:`\FGTCLB\AcademicBiteJobs\Event\ModifyBiteJobPostingsRequestEvent`.
*   Removing, enriching or grouping the postings: a listener of
    :php:`\FGTCLB\AcademicBiteJobs\Event\ModifyBiteJobPostingsEvent`.
*   Additional view variables: a listener of the plugin view event of
    :guilabel:`academic_base`, see :ref:`feature-plugin-view-event`.

A subclass that drops postings:

..  code-block:: php
    :caption: Before: EXT:my_extension/Classes/Controller/BiteJobsController.php

    final class BiteJobsController extends \FGTCLB\AcademicBiteJobs\Controller\BiteJobsController
    {
        public function listAction(): ResponseInterface
        {
            $jobs = $this->biteJobsService->fetchBiteJobs($this->request);
            $this->view->assign('jobs', array_filter($jobs, $this->isPublished(...)));
            return $this->htmlResponse();
        }
    }

does the same as a listener, with one difference: the listener runs before the
limit of the content element is applied, so the list still shows as many
postings as the limit allows:

..  code-block:: php
    :caption: After: EXT:my_extension/Classes/EventListener/SkipUnpublishedPostings.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\EventListener;

    use FGTCLB\AcademicBiteJobs\Event\ModifyBiteJobPostingsEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    final class SkipUnpublishedPostings
    {
        #[AsEventListener(identifier: 'my-extension/skip-unpublished-postings')]
        public function __invoke(ModifyBiteJobPostingsEvent $event): void
        {
            $event->setJobPostings(array_values(array_filter(
                $event->getJobPostings(),
                static fn(array $posting): bool => ($posting['published'] ?? true) !== false,
            )));
        }
    }

..  index:: Frontend, PHP-API, NotScanned, ext:academic_bite_jobs
