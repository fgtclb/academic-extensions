..  _feature-modify-plugin-view-event:

========================================================
Feature: One event to add variables to every plugin view
========================================================

Description
===========

:php:`\FGTCLB\AcademicBase\Event\ModifyPluginViewEvent` is a PSR-14 event that
every plugin of :guilabel:`academic_bite_jobs`,
:guilabel:`academic_contacts4pages`, :guilabel:`academic_jobs`,
:guilabel:`academic_partners`, :guilabel:`academic_persons`,
:guilabel:`academic_programs` and :guilabel:`academic_projects` dispatches,
once each time an action renders its view. It is dispatched after the action
assigned its own variables, including the renderings of an empty state, like
the selected profiles without a selection.

The event hands a listener the view and a plugin action context typed against
the :guilabel:`academic_base` interface: the request, the site and its language,
the content element, its settings, and the extension, plugin and action name.
One listener serves every plugin and checks the names for the one it means:

..  code-block:: php
    :caption: EXT:my_sitepackage/Classes/EventListener/AddContactPageToPartnerMap.php

    namespace MyVendor\MySitepackage\EventListener;

    use FGTCLB\AcademicBase\Event\ModifyPluginViewEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    final class AddContactPageToPartnerMap
    {
        #[AsEventListener]
        public function __invoke(ModifyPluginViewEvent $event): void
        {
            $context = $event->getPluginControllerActionContext();
            if ($context->getControllerExtensionName() !== 'AcademicPartners'
                || $context->getPluginName() !== 'Map'
            ) {
                return;
            }
            $event->getView()->assign('contactPageId', 42);
        }
    }

A listener that assigns a variable the action assigned already replaces it in
the view, and only there: what the action computed from the query, such as the
pagination, keeps using the queried result. Changing which records a plugin
shows belongs in the demand and query events. One variable is assigned after
the event on purpose and cannot be replaced at all: the validations of the job
form.

The event replaces the view events of single actions that
:guilabel:`academic_jobs` and :guilabel:`academic_persons` dispatched up to
2.4; their changelogs describe the migration.

The `extension points page
<https://docs.typo3.org/p/fgtclb/academic-base/main/en-us/Developers/ExtensionPoints/Index.html>`__
lists the event as public API.

Impact
======

A project adds a variable to the templates of any academic plugin with an event
listener. A copy, a subclass or an XCLASS of a plugin controller is no longer
needed for it.

The behaviour is the same on TYPO3 v13 and v14.

..  index:: Frontend, PHP-API, NotScanned, ext:academic_base
