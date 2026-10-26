..  _developers-event-modify-plugin-view-event:

=====================
ModifyPluginViewEvent
=====================

:php:`\FGTCLB\AcademicBase\Event\ModifyPluginViewEvent` hands a listener the
view of an academic plugin right before it renders, so the listener can assign
further variables to it. A template override then renders them. It is the
supported way to bring one more value into the templates of a plugin, the uid
of a page a template links to or a value computed from the records the plugin
shows, without copying, subclassing or XCLASSing a plugin controller. The
controllers are :php:`final`, see
:ref:`developers-extension-points-not-api`.

This extension owns the event and dispatches it nowhere itself. The plugins of
seven academic extensions dispatch it. Each of them documents what it passes
in, with an example that fits its plugins.

..  contents::
    :local:
    :depth: 1

..  _developers-event-modify-plugin-view-event-dispatch:

When it is dispatched
=====================

Every action of a plugin that renders a view dispatches the event once, after
it assigned its own variables and right before it renders, including the
rendering of an empty state, a list without results for example. An action
that answers with a redirect, a list filter that is submitted or a job form
that is saved, renders nothing and dispatches nothing.

..  list-table::
    :header-rows: 1
    :widths: 25 25 50

    *   -   Extension
        -   :php:`getControllerExtensionName()`
        -   Plugins, by the name :php:`getPluginName()` returns
    *   -   `academic_bite_jobs
            <https://docs.typo3.org/p/fgtclb/academic-bite-jobs/3.0/en-us/Developers/Events/ModifyPluginViewEvent.html>`__
        -   ``AcademicBiteJobs``
        -   ``List``
    *   -   `academic_contacts4pages
            <https://docs.typo3.org/p/fgtclb/academic-contacts4pages/3.0/en-us/Developers/Events/ModifyPluginViewEvent.html>`__
        -   ``AcademicContacts4pages``
        -   ``List``
    *   -   `academic_jobs
            <https://docs.typo3.org/p/fgtclb/academic-jobs/3.0/en-us/Developers/Events/ModifyPluginViewEvent.html>`__
        -   ``AcademicJobs``
        -   ``List``, ``Detail``, ``NewJobForm``
    *   -   `academic_partners
            <https://docs.typo3.org/p/fgtclb/academic-partners/3.0/en-us/Developers/Events/ModifyPluginViewEvent.html>`__
        -   ``AcademicPartners``
        -   ``List``, ``Map``, ``PartnershipsList``, ``PartnershipsTeaser``
    *   -   `academic_persons
            <https://docs.typo3.org/p/fgtclb/academic-persons/3.0/en-us/Developers/Events/ModifyPluginViewEvent.html>`__
        -   ``AcademicPersons``
        -   ``List``, ``ListAndDetail``, ``Detail``, ``Card``,
            ``SelectedProfiles``, ``SelectedContracts``
    *   -   `academic_programs
            <https://docs.typo3.org/p/fgtclb/academic-programs/3.0/en-us/Developers/Events/ModifyPluginViewEvent.html>`__
        -   ``AcademicPrograms``
        -   ``ProgramList``, ``ProgramFinder``, ``ProgramDetails``
    *   -   `academic_projects
            <https://docs.typo3.org/p/fgtclb/academic-projects/3.0/en-us/Developers/Events/ModifyPluginViewEvent.html>`__
        -   ``AcademicProjects``
        -   ``ProjectList``, ``ProjectListSingle``

The profile editing of :guilabel:`academic_persons_edit` does not dispatch the
event. Its views are driven by a script that saves through endpoints of its
own, and every write of it is offered to an event of its own instead. The
content element of :guilabel:`academic_study_plan` is no Extbase plugin and
does not dispatch it either.

What a listener reads and changes
=================================

..  list-table::
    :header-rows: 1
    :widths: 35 65

    *   -   Method
        -   Returns
    *   -   :php:`getView()`
        -   The view the action renders. A variable assigned to it with
            :php:`assign()` or :php:`assignMultiple()` reaches the templates,
            partials and sections of the plugin. Nothing has to be handed back.
    *   -   :php:`getPluginControllerActionContext()`
        -   The context of the rendering, typed against
            :php:`\FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface`,
            for reading. See below.

The context tells a listener which plugin and which action it is called for,
and hands over what the action rendered with:

..  list-table::
    :header-rows: 1
    :widths: 35 65

    *   -   Method of the context
        -   Returns
    *   -   :php:`getControllerExtensionName()`
        -   The extension name of the plugin, ``AcademicPersons`` for example.
    *   -   :php:`getControllerExtensionKey()`
        -   The extension key of the plugin, ``academic_persons`` for example.
    *   -   :php:`getPluginName()`
        -   The name the plugin is registered with, see the table above.
    *   -   :php:`getControllerName()`, :php:`getControllerObjectName()`
        -   The short name and the class name of the controller.
    *   -   :php:`getActionName()`
        -   The action that renders, ``list`` or ``detail`` for example.
    *   -   :php:`getSettings()`
        -   The settings the action rendered with: the plugin settings of the
            content element merged over the TypoScript settings, after the
            action settled them.
    *   -   :php:`getRequest()`, :php:`getExtbaseRequestParameters()`
        -   The request of the rendering and its Extbase parameters.
    *   -   :php:`getSite()`, :php:`getLanguage()`
        -   The site and the site language of the request.
    *   -   :php:`getContentObjectRenderer()`
        -   The content object of the content element, which carries its
            record in :php:`$data`.
    *   -   :php:`getApplicationType()`
        -   Frontend or backend.

Every getter that can lack its value returns :php:`null` instead of failing.
:php:`getApplicationType()` is the exception: for a request without an
application type, which only a request built by hand can be, TYPO3 raises its
own exception.

..  _developers-event-modify-plugin-view-event-example:

Example
=======

A site links the detail view of every list of the academic extensions to a
contact form, on a page whose uid the templates should not hard code. One
listener assigns the uid to every detail view, and the template overrides of
the site render the link with it:

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/AddContactFormPage.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\EventListener;

    use FGTCLB\AcademicBase\Event\ModifyPluginViewEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    final readonly class AddContactFormPage
    {
        private const CONTACT_FORM_PAGE_UID = 42;

        #[AsEventListener(identifier: 'my-extension/add-contact-form-page')]
        public function __invoke(ModifyPluginViewEvent $event): void
        {
            $context = $event->getPluginControllerActionContext();
            $action = $context->getActionName();
            if ($action !== 'detail' && $action !== 'show') {
                return;
            }
            $event->getView()->assign('contactFormPageUid', self::CONTACT_FORM_PAGE_UID);
        }
    }

The detail actions of the persons plugins are named ``detail``, those of the
job and the program details plugins ``show``. A listener meant for one plugin
checks the extension name and the plugin name as well, because two
extensions register plugins of the same name, ``List`` for example. A further
example is part of the :ref:`extension points page
<developers-extension-points-plugin-view>`.

..  _developers-event-modify-plugin-view-event-rules:

Rules and pitfalls
==================

*   **The view only.** The event runs after the action assigned its own
    variables. A listener that assigns one of them again replaces it in the
    view and nowhere else: the pagination, the page title and the other
    events of the rendering keep what the action computed. Which records a
    plugin shows is changed in a demand or a query event of the extension
    that ships the plugin, not here.
*   **Some variables are protected.** An action may assign a variable after
    the event on purpose, so no listener can replace it. The validations of
    the job form of :guilabel:`academic_jobs` are the one case today.
*   **Check the plugin.** One listener is called for every plugin of every
    dispatching extension. A listener that does not check the context assigns
    its variables to all of them.
*   **One context per rendering.** The action builds its context once and
    hands the same object to every event it dispatches while it renders, the
    demand and list events of the same extension among them. A listener of
    several of them can keep what it collects keyed by that object. Its
    settings are the ones the action used, which may differ from the
    :fluid:`{settings}` in the view: Extbase assigns those before the action
    runs.
*   **No order is promised** between several listeners. A listener that has
    to run after another one says so with the :php:`after` argument of
    :php:`#[AsEventListener]`.

Introduced in
=============

*   :ref:`feature-modify-plugin-view-event`
*   :ref:`important-1790607225`
*   :ref:`important-plugin-controller-action-context-content-object` and
    :ref:`important-ace-748-academic-base` (2.4), which made the getters of
    the context return :php:`null` rather than fail
