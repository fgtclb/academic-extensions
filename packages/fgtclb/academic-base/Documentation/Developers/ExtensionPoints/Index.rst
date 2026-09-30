..  index:: ! Extension points, ! Public API
..  _developers-extension-points:

================
Extension points
================

This page is the contract between the academic extensions and the code of a
project. It covers every academic extension and :guilabel:`category_types`,
and it is a complete list: what it names is public API, and what it does not
name is not.

Every class, interface, trait and enum listed here carries the :php:`@api`
tag in its docblock, so the promise is visible in the code as well. A test of
the extensions' own repository makes sure the tags and this page name the
same classes.

..  contents::
    :local:
    :depth: 1

..  _developers-extension-points-promise:

What the promise means
======================

Public API does not change silently. A release that breaks or deprecates
anything listed on this page says so in the changelog of the extension:

*   a :guilabel:`Breaking` entry when existing code stops working, with the
    migration;
*   a :guilabel:`Deprecation` entry when the old way keeps working until the
    next major version, and says what replaces it.

Anything else may change in any release, a bugfix release included, and
without a changelog entry. Code that relies on it has to be checked on every
update.

..  _developers-extension-points-api:

What is public API
==================

*   The :ref:`events <developers-extension-points-events>`, and the
    :ref:`types they hand to a listener <developers-extension-points-types>`.
*   The :ref:`interfaces <developers-extension-points-interfaces>`.
*   The :ref:`services and classes <developers-extension-points-services>` a
    project names in its configuration or its code.
*   The :ref:`base class <developers-extension-points-base-classes>` of a
    profile factory.
*   The two :ref:`traits <developers-extension-points-traits>` of
    :guilabel:`academic_base` for Extbase controllers.
*   The :ref:`domain models <developers-extension-points-models>`: their
    public getters, which the templates read, and extending them to add fields.
*   Templates, partials and sections: their paths below
    :file:`Resources/Private/`, the names of their sections, the variables
    they receive, and the ViewHelpers they call, by tag name and arguments.
    The :guilabel:`Templates` chapter of each extension describes how to
    override them.
*   Settings: the site settings of the site sets, the TypoScript constants and
    settings, and the settings of the content elements.
*   The TypoScript and page TSconfig keys the extensions document.
*   The keys of the language files, which a site overrides labels by.
*   The identifier ``academic-persons/apply-settings-to-tca`` of the listener
    that applies the persons settings to the compiled TCA, so that a listener
    of :php:`TYPO3\CMS\Core\Configuration\Event\AfterTcaCompilationEvent`
    can be ordered after it. The listener class is not public API.
*   The format of :file:`Configuration/CategoryTypes.yaml`, see the
    `category types chapter of category_types
    <https://docs.typo3.org/p/fgtclb/category-types/main/en-us/Developers/CategoryTypes/Index.html>`__.
*   The routing aspect type ``CategoryFilterMapper`` of
    :guilabel:`category_types` and its settings, see the `routing chapter of
    category_types
    <https://docs.typo3.org/p/fgtclb/category-types/main/en-us/Developers/Routing/Index.html>`__.
    The class behind it is not public API.

..  _developers-extension-points-not-api:

What is not public API
======================

Everything else. In particular:

*   **Controllers.** The controllers of :guilabel:`academic_contacts4pages`,
    :guilabel:`academic_jobs`, :guilabel:`academic_persons` and
    :guilabel:`academic_persons_edit` are :php:`final`. Five controllers are not
    final yet: :php:`BiteJobsController` of :guilabel:`academic_bite_jobs`,
    :php:`PartnerController` of :guilabel:`academic_partners`,
    :php:`ProgramController` and :php:`DetailsController` of
    :guilabel:`academic_programs`, and :php:`ProjectController` of
    :guilabel:`academic_projects`. They are left open so that an existing
    subclass keeps working for now, not as an invitation: a subclass breaks
    whenever an action or a constructor changes, and they may become final in
    the next major version. Each of their actions dispatches the
    :ref:`plugin view event <developers-extension-points-plugin-view>`, and the
    partner, program and project lists and the program finder a demand and a
    list event as well, and the B-ITE job list a request and a result event,
    which replace such a subclass. A subclass that
    overrides an action without calling the parent action drops those events
    for its plugin.
*   **Repositories.** A condition a plugin should apply belongs in a demand or
    a query event, not in an XCLASS of the repository.
*   **Services, data processors, ViewHelper classes, backend item providers,
    hooks, event listeners, commands and upgrade wizards**, except the few
    :ref:`a project names <developers-extension-points-services>`. A service
    is replaced through the container where it is registered behind one of the
    interfaces below, never by subclassing it.
*   **Everything marked** :php:`@internal`. The marker says in the code what
    the absence of :php:`@api` says for the rest.

An XCLASS is unsupported for every class but a domain model, listed or not: a
listed class keeps what it promises to a caller, not what it offers to a
subclass. The :ref:`upgrade check <upgrade-check-configuration>` reports such
an XCLASS as a warning, and as an error when the class is final or gone. How
to extend a model is described under :ref:`developers-extension-points-models`.

..  _developers-extension-points-events:

Events
======

Every event is a :php:`final` class, dispatched through the PSR-14 event
dispatcher of TYPO3. A listener registers for it with the
:php:`#[AsEventListener]` attribute of TYPO3. The developer chapters of
`academic_bite_jobs <https://docs.typo3.org/p/fgtclb/academic-bite-jobs/main/en-us/Developers/Index.html>`__,
`academic_contacts4pages <https://docs.typo3.org/p/fgtclb/academic-contacts4pages/main/en-us/Developers/Index.html>`__,
`academic_persons <https://docs.typo3.org/p/fgtclb/academic-persons/main/en-us/Developers/Index.html>`__,
`academic_persons_edit <https://docs.typo3.org/p/fgtclb/academic-persons-edit/main/en-us/Developers/Index.html>`__,
`academic_partners <https://docs.typo3.org/p/fgtclb/academic-partners/main/en-us/Developers/Index.html>`__,
`academic_programs <https://docs.typo3.org/p/fgtclb/academic-programs/main/en-us/Developers/Index.html>`__
and
`academic_projects <https://docs.typo3.org/p/fgtclb/academic-projects/main/en-us/Developers/Index.html>`__
describe their events in detail, with examples.

..  list-table::
    :header-rows: 1
    :widths: 30 40 30

    *   -   Event
        -   Dispatched
        -   A listener may
    *   -   :php:`\FGTCLB\AcademicBase\Event\ModifyTcaSelectFieldItemsEvent`
        -   when the backend builds the items of a select field of
            :guilabel:`academic_jobs` (type, employment type),
            :guilabel:`academic_persons` (the contract and the shown fields
            of the plugins) or :guilabel:`academic_contacts4pages` (the
            contract and the addresses of a contact)
        -   replace the items and the other item provider parameters
    *   -   :php:`\FGTCLB\AcademicBase\Event\ModifyPluginViewEvent`
        -   in every plugin of :guilabel:`academic_bite_jobs`,
            :guilabel:`academic_contacts4pages`, :guilabel:`academic_jobs`,
            :guilabel:`academic_partners`, :guilabel:`academic_persons`,
            :guilabel:`academic_programs` and :guilabel:`academic_projects`,
            once each time an action renders its view, after the action
            assigned its own variables
        -   assign further view variables, see
            :ref:`developers-extension-points-plugin-view`
    *   -   :php:`\FGTCLB\AcademicBiteJobs\Event\ModifyBiteJobPostingsRequestEvent`
        -   in the job list of :guilabel:`academic_bite_jobs`, after the
            request to the B-ITE API is built from the plugin settings and
            before it is sent
        -   replace the payload of the request
    *   -   :php:`\FGTCLB\AcademicBiteJobs\Event\ModifyBiteJobPostingsEvent`
        -   in the job list of :guilabel:`academic_bite_jobs`, after the
            response of the B-ITE API is decoded, also after a failed request,
            and before the limit is applied
        -   replace the postings
    *   -   :php:`\FGTCLB\AcademicContacts4pages\Event\ModifyPageContactsEvent`
        -   in the contacts content element and in the data processor of
            the page contacts, after the contacts of the page are read and
            before they are grouped by role
        -   replace the contacts
    *   -   :php:`\FGTCLB\AcademicJobs\Event\AfterSaveJobEvent`
        -   in the job form plugin, after a submitted job is saved
        -   change the page redirected to, and how the confirmation message
            is shown
    *   -   :php:`\FGTCLB\AcademicPartners\Event\ModifyPartnerDemandEvent`
        -   in the partner list and the partner map, before the partners are
            queried
        -   replace the demand
    *   -   :php:`\FGTCLB\AcademicPartners\Event\ModifyPartnerListEvent`
        -   in the partner list and the partner map, after the query
        -   replace the partners and the categories, assign further view
            variables
    *   -   :php:`\FGTCLB\AcademicPrograms\Event\ModifyProgramDemandEvent`
        -   in the program list and the program finder, before the programs
            are queried
        -   replace the demand
    *   -   :php:`\FGTCLB\AcademicPrograms\Event\ModifyProgramListEvent`
        -   in the program list and the program finder, after the query
        -   replace the programs and the categories, assign further view
            variables
    *   -   :php:`\FGTCLB\AcademicPrograms\Event\ModifyProgramDataEvent`
        -   on a program page, after its data is built from the page record
            and before the facts are built from it
        -   replace the data the page template receives
    *   -   :php:`\FGTCLB\AcademicProjects\Event\ModifyProjectDemandEvent`
        -   in the project list, before the projects are queried
        -   replace the demand
    *   -   :php:`\FGTCLB\AcademicProjects\Event\ModifyProjectListEvent`
        -   in the project list, after the query
        -   replace the projects and the categories, assign further view
            variables
    *   -   :php:`\FGTCLB\AcademicPersons\Event\ModifyProfileDemandEvent`
        -   in the list, list-and-detail and card plugins, before the profile
            query and the query of the letter navigation are built from the
            demand
        -   replace the demand
    *   -   :php:`\FGTCLB\AcademicPersons\Event\ModifyProfileQueryEvent`
        -   right before the profile query of the list, list-and-detail and
            card plugins, of the letter navigation and of the
            selected-profiles plugin is executed
        -   add conditions
    *   -   :php:`\FGTCLB\AcademicPersons\Event\ModifyContractQueryEvent`
        -   right before the contract query of the selected-contracts plugin
            is executed
        -   add conditions
    *   -   :php:`\FGTCLB\AcademicPersons\Event\ModifyProfileTitlePlaceholderReplacementEvent`
        -   when the page title of a profile's detail view is built, once
            per placeholder of the title format
        -   replace the value of the placeholder
    *   -   :php:`\FGTCLB\AcademicPersons\Event\ModifyProfileImageMetadataEvent`
        -   right before the metadata of a profile image is written, for the
            file and for the file reference
        -   change or empty the fields that are written
    *   -   :php:`\FGTCLB\AcademicPersons\Event\AfterProfileUpdateEvent`
        -   after a profile was created or updated: by a backend save or an
            import through the DataHandler, by the commands
            :bash:`academic:createprofiles` and :bash:`academic:updateprofiles`,
            and by the profile editing of :guilabel:`academic_persons_edit`
        -   react to it; the profile, its site and the origin of the update
            are read only
    *   -   :php:`\FGTCLB\AcademicPersons\Event\ChooseProfileFactoryEvent`
        -   in the commands :bash:`academic:createprofiles` and
            :bash:`academic:updateprofiles`, once per frontend user
        -   choose the profile factory that creates or updates the profile
    *   -   :php:`\FGTCLB\AcademicPersons\Event\BeforeProfileMappedFromFrontendUserEvent`
        -   in the commands :bash:`academic:createprofiles` and
            :bash:`academic:updateprofiles`, before the data of a frontend
            user is mapped: once per frontend user on creation, once per
            synchronised profile on update (not for a profile whose
            :sql:`skip_sync` flag is set)
        -   add or change values of the data, skip the frontend user on
            creation or the profile on update
    *   -   :php:`\FGTCLB\AcademicPersons\Event\AfterProfileMappedFromFrontendUserEvent`
        -   in the same commands, after the data of a frontend user was
            mapped and before the profile is saved
        -   change the profile
    *   -   :php:`\FGTCLB\AcademicPersonsEdit\Event\BeforeProfileEditingWriteEvent`
        -   in the profile editing of :guilabel:`academic_persons_edit`, once
            for every write the editor accepted, before anything of it is
            stored: the profile, its switches and image, and its documents and
            contacts
        -   refuse the write with a reason the person is shown, or replace the
            values it stores, which are validated again

One event is not on this list. The event the commands
:bash:`academic:createprofiles` and :bash:`academic:updateprofiles` dispatch
before they set up the environment of a frontend user's site,
:php:`ModifyProfileCommandEnvironmentStateBuildContextForFrontendUserEvent`,
is marked :php:`@internal` as experimental, like the environment handling it
belongs to. The event the 2.4 changelog of :guilabel:`academic_persons_edit`
mentions for filling form data from other sources before it is written is
:php:`BeforeProfileEditingWriteEvent` from 3.0 on.

..  _developers-extension-points-plugin-view:

Adding a variable to the view of a plugin
-----------------------------------------

One listener of :php:`ModifyPluginViewEvent` serves every plugin. The context
it hands over names the plugin and the action, so a listener that means one of
them checks both. The plugin name is the one the plugin is registered with,
:php:`List`, :php:`Detail` or :php:`ProjectListSingle` for example, and the
extension name tells two plugins of the same name apart:

..  code-block:: php
    :caption: EXT:my_sitepackage/Classes/EventListener/AddOfficeHoursLink.php

    namespace MyVendor\MySitepackage\EventListener;

    use FGTCLB\AcademicBase\Event\ModifyPluginViewEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    final class AddOfficeHoursLink
    {
        #[AsEventListener]
        public function __invoke(ModifyPluginViewEvent $event): void
        {
            $context = $event->getPluginControllerActionContext();
            if ($context->getControllerExtensionName() !== 'AcademicPersons'
                || $context->getActionName() !== 'detail'
            ) {
                return;
            }
            $event->getView()->assign('officeHoursPageId', 42);
        }
    }

The variable is then available to the templates, partials and sections of
that plugin, and a template override renders it.

The event runs after the action assigned its own variables, so a listener
that assigns one of them again replaces it in the view, and only there: the
pagination and the page title keep the profiles the query returned, for
example. A change of which records are shown belongs in a demand or a query
event. One variable is assigned after the event on purpose and cannot be
replaced at all: the validations of the job form.

..  _developers-extension-points-types:

What an event hands over
========================

A listener types against whatever an event's methods declare, so these types
are public API together with the events. Classes of TYPO3 and of other
packages are not listed; their own documentation applies.

..  list-table::
    :header-rows: 1
    :widths: 50 50

    *   -   Type
        -   Handed over by
    *   -   :php:`\FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface`
        -   every event a plugin action dispatches: the request, the site and
            its language, the content object, the settings of the content
            element and the plugin name
    *   -   :php:`\FGTCLB\AcademicPersons\Domain\Model\Dto\PluginControllerActionContextInterface`
        -   the title placeholder event of :guilabel:`academic_persons`, which
            still declares this copy of the interface above. It adds nothing
            to it, is deprecated, and is removed in 4.0; type a listener
            against the :guilabel:`academic_base` interface.
    *   -   :php:`\FGTCLB\AcademicPersons\Domain\Model\Dto\DemandInterface`
        -   the profile demand and query events
    *   -   :php:`\FGTCLB\AcademicPersons\Domain\Model\Dto\ProfileDemand`
        -   the profile demand and query events as the demand of the list,
            list-and-detail and card plugins, which they declare as the
            interface above
    *   -   :php:`\FGTCLB\AcademicPartners\Domain\Model\Dto\PartnerDemand`
        -   the partner demand and list events
    *   -   :php:`\FGTCLB\AcademicPrograms\Domain\Model\Dto\ProgramDemand`
        -   the program demand and list events
    *   -   :php:`\FGTCLB\AcademicPrograms\Domain\Model\ProgramData`
        -   the program data event
    *   -   :php:`\FGTCLB\AcademicProjects\Domain\Model\Dto\ProjectDemand`
        -   the project demand and list events
    *   -   :php:`\FGTCLB\CategoryTypes\Collection\CategoryCollection`
        -   the partner, program and project list events
    *   -   :php:`\FGTCLB\CategoryTypes\Collection\FilterCollection`
        -   the partner, program and project demands, which carry the
            category filter of the request
    *   -   :php:`\FGTCLB\AcademicPersons\Profile\ProfileFactoryInterface`
        -   the profile factory event, which takes an implementation of it
    *   -   :php:`\FGTCLB\AcademicPersons\Event\ProfileUpdateOrigin`
        -   the profile update event
    *   -   :php:`\FGTCLB\AcademicPersons\Profile\ProfileActionType`
        -   the profile factory event and the two events of the frontend user
            synchronisation
    *   -   :php:`\FGTCLB\AcademicJobs\SaveForm\FlashMessageCreationMode`
        -   the job save event
    *   -   :php:`\FGTCLB\AcademicPersonsEdit\Event\ProfileEditingAction`
        -   the write event of the profile editing
    *   -   :php:`\FGTCLB\AcademicContacts4pages\Event\PageContactsOutput`
        -   the page contacts event

..  _developers-extension-points-interfaces:

Interfaces
==========

A project implements an interface, types against it, or aliases it to an
implementation of its own where the container hands one out.

..  list-table::
    :header-rows: 1
    :widths: 50 50

    *   -   Interface
        -   What it is for
    *   -   :php:`\FGTCLB\CategoryTypes\Collection\GetCategoryCollectionInterface`
        -   A model that carries categories of :guilabel:`category_types`
            implements it, as the partner, program and project models do.
    *   -   :php:`\FGTCLB\AcademicPersonsEdit\Service\ProfileRichTextSanitizerInterface`
        -   The sanitizer the profile editing runs rich text through. A
            project replaces it by aliasing this interface to its own
            implementation, in an extension that depends on
            :guilabel:`academic_persons_edit`.
    *   -   :php:`\FGTCLB\AcademicPersons\Types\TypesInterface`
        -   What the lists of address, email and phone number types are read
            through. The lists come from the extension configuration, and the
            extension asks for them by their own class names, so a project
            changes them there; an implementation of its own is not used.
    *   -   :php:`\FGTCLB\AcademicPersons\DemandValues\DemandValuesInterface`
        -   The same for the sorting and grouping values the profile list
            offers.
    *   -   :php:`\FGTCLB\AcademicPrograms\Domain\Model\ProgramFactsSourceInterface`
        -   What the facts of a program are built from: the program model of
            the content elements and the data of the program page implement
            it.

The abstract classes that implement some of them for the extensions
themselves are not public API.

..  _developers-extension-points-services:

Services and classes a project names
====================================

These are named in a project's configuration, or injected into its code. Use
them as they are; they are not meant to be subclassed or replaced.

..  list-table::
    :header-rows: 1
    :widths: 50 50

    *   -   Class
        -   Named
    *   -   :php:`\FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider`
        -   as the :php:`provider` of an icon in :file:`Configuration/Icons.php`,
            for an SVG drawn in ``currentColor``
    *   -   :php:`\FGTCLB\AcademicContacts4pages\DataProcessing\ContactsProcessor`
        -   as a data processor of a page template, for the contacts of the
            page, by its identifier `academic-page-contacts` or by class name
    *   -   :php:`\FGTCLB\CategoryTypes\Backend\FormEngine\CategoryTypeItemsProcFunc`
        -   as the :php:`itemsProcFunc` of a select field of a project's TCA or
            FlexForm that offers category types
    *   -   :php:`\FGTCLB\CategoryTypes\Registry\CategoryTypeRegistry`
        -   injected, to read the registered category types
    *   -   :php:`\FGTCLB\CategoryTypes\Backend\PageCategorySummaryRenderer`
        -   injected into a listener that shows the category summary of a page
            type of its own in the page module
    *   -   :php:`\FGTCLB\AcademicPersons\Profile\FrontendUserProfileMapper`
        -   injected into a profile factory of its own, to apply the
            configured map of frontend user fields
    *   -   :php:`\FGTCLB\AcademicPersons\DataHandling\ProfileWriteCorrelation`
        -   in import code, as :php:`ProfileWriteCorrelation::Import`, to mark
            a DataHandler run that writes profiles, so the profile update event
            announces it with the origin :php:`ProfileUpdateOrigin::Import`;
            and as :php:`ProfileWriteCorrelation::Internal` in a listener of
            the profile update event that writes profiles through the
            DataHandler, so that write is not announced again

..  _developers-extension-points-base-classes:

Base classes
============

..  list-table::
    :header-rows: 1
    :widths: 50 50

    *   -   Class
        -   What it is for
    *   -   :php:`\FGTCLB\AcademicPersons\Profile\AbstractProfileFactory`
        -   The base of a profile factory a project chooses with the profile
            factory event. It dispatches the two events of the frontend user
            synchronisation. A subclass implements the protected methods
            :php:`createProfileFromFrontendUser()`, which may return
            :php:`null` to create nothing, and
            :php:`updateProfileFromFrontendUser()`.

..  _developers-extension-points-traits:

Traits
======

..  list-table::
    :header-rows: 1
    :widths: 50 50

    *   -   Trait
        -   What it is for
    *   -   :php:`\FGTCLB\AcademicBase\Controller\GetCurrentContentRecordMethodTrait`
        -   Returns the record of the current content element, which a
            plugin assigns as the :fluid:`record` view variable so that the
            header of the content element renders on TYPO3 v14.
    *   -   :php:`\FGTCLB\AcademicBase\Controller\GetSelectItemsForTcaManagedTableFieldMethodTrait`
        -   Reads the items of a select field as the backend would offer them,
            with the item provider applied and the labels translated.

..  _developers-extension-points-models:

Domain models
=============

The domain models are what the templates read, so their public getters are
public API. A project may extend a model to add fields of its own. Extbase
creates a model through :php:`GeneralUtility::getClassName()`, so the way to
do that is a subclass registered as the XCLASS of the model:

..  code-block:: php
    :caption: EXT:my_extension/ext_localconf.php

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\FGTCLB\AcademicPersons\Domain\Model\Profile::class] = [
        'className' => \MyVendor\MyExtension\Domain\Model\Profile::class,
    ];

Extbase maps the subclass by its own class name, so it needs an entry in the
project's :file:`Configuration/Extbase/Persistence/Classes.php`: the
:php:`tableName` of the model, and its :php:`recordType` if the extension
declares one - a subclass inherits the :php:`properties` of the parent entry,
but neither of those two. The new columns need their TCA and their database
fields; a :php:`properties` entry is needed only for a column whose name does
not match the property. A model the extensions create themselves with
:php:`new`, a profile a profile factory creates for instance, is still of the
original class. The :ref:`upgrade check
<upgrade-check-configuration>` lists such an XCLASS as a notice: it does not
fail the check, and it is a reminder to make sure the getters and setters the
subclass overrides still exist after an update.

..  list-table::
    :header-rows: 1
    :widths: 30 70

    *   -   Extension
        -   Models
    *   -   :guilabel:`academic_contacts4pages`
        -   :php:`\FGTCLB\AcademicContacts4pages\Domain\Model\Contact`,
            :php:`\FGTCLB\AcademicContacts4pages\Domain\Model\Role`
    *   -   :guilabel:`academic_jobs`
        -   :php:`\FGTCLB\AcademicJobs\Domain\Model\Job`
    *   -   :guilabel:`academic_partners`
        -   :php:`\FGTCLB\AcademicPartners\Domain\Model\Partner`,
            :php:`\FGTCLB\AcademicPartners\Domain\Model\Partnership`,
            :php:`\FGTCLB\AcademicPartners\Domain\Model\Role`
    *   -   :guilabel:`academic_persons`
        -   :php:`\FGTCLB\AcademicPersons\Domain\Model\Address`,
            :php:`\FGTCLB\AcademicPersons\Domain\Model\Contract`,
            :php:`\FGTCLB\AcademicPersons\Domain\Model\Email`,
            :php:`\FGTCLB\AcademicPersons\Domain\Model\FrontendUser`,
            :php:`\FGTCLB\AcademicPersons\Domain\Model\FunctionType`,
            :php:`\FGTCLB\AcademicPersons\Domain\Model\Location`,
            :php:`\FGTCLB\AcademicPersons\Domain\Model\OrganisationalUnit`,
            :php:`\FGTCLB\AcademicPersons\Domain\Model\PhoneNumber`,
            :php:`\FGTCLB\AcademicPersons\Domain\Model\Profile`,
            :php:`\FGTCLB\AcademicPersons\Domain\Model\ProfileInformation`
    *   -   :guilabel:`academic_persons_sync`
        -   :php:`\FGTCLB\AcademicPersonsSync\Domain\Model\FrontendUser`
    *   -   :guilabel:`academic_programs`
        -   :php:`\FGTCLB\AcademicPrograms\Domain\Model\Program`
    *   -   :guilabel:`academic_projects`
        -   :php:`\FGTCLB\AcademicProjects\Domain\Model\Project`
    *   -   :guilabel:`category_types`
        -   :php:`\FGTCLB\CategoryTypes\Domain\Model\Category`,
            :php:`\FGTCLB\CategoryTypes\Domain\Model\CategoryType`

The models of :guilabel:`category_types` are not Extbase models; the extension
builds them itself. Their getters are public API all the same, but they cannot
be extended through an XCLASS.

..  _developers-extension-points-minimum:

The extension points every extension should have
================================================

Three kinds of extension point together make subclassing and XCLASSing
unnecessary. Not every extension offers all three yet:

..  list-table::
    :header-rows: 1
    :widths: 30 35 35

    *   -   Extension point
        -   Offered today
        -   Planned
    *   -   A view event per plugin action, to assign further view variables
        -   the plugins of the seven extensions named at the event above,
            through one event
        -   none: the profile editing of :guilabel:`academic_persons_edit`
            has an event before each of its writes instead
    *   -   A demand event per repository query a plugin runs, to change what
            is queried
        -   :guilabel:`academic_persons`, :guilabel:`academic_partners`,
            :guilabel:`academic_projects`
        -   the list plugins of the other extensions
    *   -   An after-save event per write, to react to a saved record
        -   the profile of :guilabel:`academic_persons`, the job form of
            :guilabel:`academic_jobs`
        -   the other writes of the frontend editing

A planned extension point is listed here once it is dispatched, and not
before.
