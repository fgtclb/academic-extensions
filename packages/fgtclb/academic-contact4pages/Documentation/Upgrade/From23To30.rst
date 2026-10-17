..  _upgrade-from-2-3-to-3-0:

=========================
Upgrading from 2.3 to 3.0
=========================

This page takes an installation of :guilabel:`academic_contacts4pages` 2.3
straight to 3.0, without the step to 2.4. Version 3.0 drops TYPO3 12, renders
the contact list with speaking classes and the profile images as responsive
pictures, and renames the icons of the extension. From 2.4 it takes over the
configuration per component, the content element that stays hidden until a site
enables it, a position of its own for a contact in its contract and in its
contacts role, and an upgrade wizard for the content elements of version 1.x.
Version 3.0 replaces no step of 2.4 for this extension, so the page leaves none
out. Work through the sections in order. Skip a step that does not apply to the
installation.

..  _upgrade-from-2-3-to-3-0-before-you-start:

Before you start
================

Version 3.0 runs on TYPO3 13.4 from 13.4.35 on and on TYPO3 14.3 from 14.3.7
on, with PHP 8.2 or later.

:guilabel:`academic_contacts4pages` 3.0 needs these packages. Upgrade them
first or in the same deployment, in this order:

*   :composer:`fgtclb/environment-state-manager` 2.x, new since 2.4, which
    :guilabel:`academic_base` 3.0 and :guilabel:`academic_persons` 3.0 require.
    Composer installs it on its own. A classic installation installs
    :guilabel:`environment_state_manager` 2.x before it activates either of
    them.
*   :composer:`fgtclb/academic-base` 3.0, the same release as this extension.
*   :composer:`fgtclb/academic-persons` 3.0, the same release as this
    extension. Its upgrade chapter has steps of its own.

The extension declares the system extension :guilabel:`install` as a
dependency since 2.4, for its upgrade wizards. TYPO3 keeps that extension
active in every installation, so this needs no action.

These changes have to ship in the same deployment as the update:

*   A site without the site set includes the page TSconfig of the component,
    or the content element is not offered, see
    :ref:`upgrade-from-2-3-to-3-0-components`.
*   A site package that imports a :file:`setup.typoscript` of this extension
    imports the :file:`constants.typoscript` of the same folder as well, or
    the plugin fails with a missing template, see
    :ref:`upgrade-from-2-3-to-3-0-own-configuration`.
*   A view that renders the persons partials with replaced partial root paths
    lists the path of :guilabel:`academic_base`, see
    :ref:`upgrade-from-2-3-to-3-0-images`.

The module paths on this page are those of TYPO3 13, where the upgrade runs.

..  _upgrade-from-2-3-to-3-0-typo3:

Update TYPO3 first
==================

Update TYPO3 to 13.4.35 or later while the site still runs 2.3, which supports
TYPO3 13.4. Version 3.0 no longer installs on TYPO3 12, and Composer and the
Extension Manager refuse it next to an older patch release of TYPO3 13.

Stay on TYPO3 13 until the upgrade is complete. This extension requires
:guilabel:`academic_persons`, whose content element wizard reads the column
:sql:`tt_content.list_type`, which TYPO3 14 removes, so it only runs on
TYPO3 13, see its `upgrade chapter
<https://docs.typo3.org/p/fgtclb/academic-persons/main/en-us/Upgrade/From23To30.html>`__.
The same holds for the content element wizards of :guilabel:`academic_jobs`,
:guilabel:`academic_persons_edit` and :guilabel:`academic_projects` when the
installation also has them. Move to TYPO3 14.3.7 or later once they have run.

See :ref:`breaking-removed-typo3-v12-support` and
:ref:`important-minimum-typo3-versions`.

..  _upgrade-from-2-3-to-3-0-package:

Update the package
==================

..  code-block:: bash

    composer require -W \
        'fgtclb/academic-base':'~3.0.0' \
        'fgtclb/academic-persons':'~3.0.0' \
        'fgtclb/academic-contacts4pages':'~3.0.0'

On a classic installation, install :guilabel:`environment_state_manager` 2.x
from the TER in :guilabel:`Admin Tools > Extensions` first, then update
:guilabel:`academic_base`, :guilabel:`academic_persons` and
:guilabel:`academic_contacts4pages` there, in this order. Run the commands of
this page with :bash:`typo3/sysext/core/bin/typo3` instead of
:bash:`vendor/bin/typo3`.

..  code-block:: bash

    vendor/bin/typo3 cache:flush

..  _upgrade-from-2-3-to-3-0-database:

Update the database
===================

..  code-block:: bash

    vendor/bin/typo3 extension:setup

Or apply the changes in :guilabel:`Admin Tools > Maintenance > Analyze
Database Structure`.

For this extension the comparison adds five columns to
:sql:`tx_academiccontacts4pages_domain_model_contact`:

*   :sql:`contract_sorting` and :sql:`role_sorting`, the order of a contact in
    its contract and in its contacts role. Until the wizard of the next section
    fills them, both hold :sql:`0`, see
    :ref:`important-contract-and-role-sort-their-contacts`.
*   :sql:`email_address`, :sql:`phone_number` and :sql:`physical_address`, the
    address records a contact displays. The default :sql:`0` means
    :guilabel:`Display all`, the output of every existing contact, see
    :ref:`feature-dedicated-address-record-selection`.

It also applies the changes of the index definitions TYPO3 13.4.35 and 14.3.7
bring, and those of :guilabel:`academic_persons`. The contract table of that
extension, to which page contacts belong as inline children, gains the columns
of a workspace aware table, so a contract and its page contacts are drafted
and published together. The section :guilabel:`Update the database` of the
`upgrade chapter of academic_persons
<https://docs.typo3.org/p/fgtclb/academic-persons/main/en-us/Upgrade/From23To30.html>`__
describes the comparison of that table on SQLite. See
:ref:`important-contact-inline-parent-is-workspace-aware`.

..  _upgrade-from-2-3-to-3-0-wizards:

Run the upgrade wizards
=======================

Both wizards need the database changes of the previous section. Run them in
this order, in :guilabel:`Admin Tools > Upgrade > Upgrade Wizard` or on the
command line. Mind the identifiers, the first one is spelled without the s
of Contacts.

#.  ``academicContact4pages_seedContactSecondarySorting``, :guilabel:`Seed the
    contact sort order of contracts and contacts roles`. It fills the two new
    sort columns with the order the contract and contacts role forms show
    today. Run it right after the database comparison: until it has run, every
    contact holds :sql:`0` in both columns, and a contact saved in the backend
    gets no position of its own in those lists. Running it again is
    harmless.

    ..  code-block:: bash

        vendor/bin/typo3 upgrade:run academicContact4pages_seedContactSecondarySorting

    See :ref:`important-contract-and-role-sort-their-contacts`.

#.  ``academicContacts4pages_pluginUpgradeWizard``, :guilabel:`Migrate the
    academic_contacts4pages plugins of version 1.x`. It renames the content
    type :sql:`academiccontacts4pages_contactslist` of version 1.x to
    :sql:`academiccontacts4pages_list` in every content element, and carries
    the permission of backend user groups over. It replaces
    ``academicContact4pages_pluginUpgradeWizard`` of versions 2.0 to 2.3, which
    looked for a misspelled type and never migrated anything. The new wizard is
    a new class, and TYPO3 records a wizard as done by its class, so it is
    offered also where the old one is marked done. It is offered only while a
    content element or a backend user group of version 1.x is left, so an
    installation that started with 2.0 or later never sees it.

    ..  code-block:: bash

        vendor/bin/typo3 upgrade:run academicContacts4pages_pluginUpgradeWizard

    TypoScript, page TSconfig or templates of a site package that name the old
    type change it to ``academiccontacts4pages_list`` by hand. See
    :ref:`important-ace-897-academic-contacts4pages` and
    :ref:`installation-upgrade-from-version-1`.

A wizard that TYPO3 recorded as done is offered again once it is marked
undone. TYPO3 13 marks a wizard undone with
``vendor/bin/typo3 upgrade:mark:undone <identifier>``.

..  _upgrade-from-2-3-to-3-0-configuration:

Adapt the configuration
=======================

The TypoScript and the page TSconfig are delivered per content element since
2.4, and the content element is hidden in the whole installation until the page
TSconfig of its component is included. See
:ref:`breaking-site-sets-and-static-templates-restructured`.

..  _upgrade-from-2-3-to-3-0-upgrade-check:

The upgrade check
-----------------

The console command :bash:`academic:upgrade:check` of :guilabel:`academic_base`
reads the stored configuration of the installation and reports the static
templates, page TSconfig imports and site sets that no longer reach an academic
extension. Run it on the staging copy right after the package update, and again
once the steps of this section are done:

..  code-block:: bash

    vendor/bin/typo3 academic:upgrade:check

With the extension key and a site it also compares the template overrides of
the site with the templates this extension ships:

..  code-block:: bash

    vendor/bin/typo3 academic:upgrade:check academic_contacts4pages --site=<site identifier>

A folder that overrides the persons partials for this plugin as well is
checked with :guilabel:`academic_persons`. The check of
:guilabel:`academic_contacts4pages` compares it with the partials of this
extension, which ships no :file:`Profile/` partials, and reports every file in
it as :bash:`missing-upstream`. Each finding, and what to do about it, is
described in the :ref:`upgrade check
<fgtclb/academic-base:upgrade-check>`
of :guilabel:`academic_base`.

..  _upgrade-from-2-3-to-3-0-components:

Include the components
----------------------

*   **Site set.** A site that depends on the set
    `fgtclb/academic-contacts4pages` needs no change. The set keeps its name
    and brings the page TSconfig along.
*   **Static template.** The stored entry of 2.3 keeps working as
    :guilabel:`Academic Contacts4Pages: Path up to 2.3 (deprecated, use All
    components) (academic_contacts4pages)`, which is removed in version 4.0.
    Replace it with :guilabel:`Academic Contacts4Pages: All components
    (academic_contacts4pages)` in the field :guilabel:`Include TypoScript sets`
    on the tab :guilabel:`Advanced Options` of the template record. That entry
    brings the shared plugin settings of :guilabel:`academic_persons` along, so
    remove a separate entry :guilabel:`Academic Persons: Shared plugin settings
    (academic_persons)`, called :guilabel:`Academic Persons Settings
    (academic_persons)` in 2.3, from the same record. See
    :ref:`deprecation-legacy-static-template-path`.
*   **Page TSconfig.** A site without the site set adds
    :guilabel:`Academic Contacts4Pages: All components
    (academic_contacts4pages)` in the field :guilabel:`Include static Page
    TSconfig (from extensions)` on the tab :guilabel:`Resources` of its root
    page, see :ref:`static-pagetsconfig`. Without it the content element is not
    offered for new content.
*   Use one of the two mechanisms per site, see
    :ref:`one-mechanism-per-site`.

Until a component is included, an existing content element of it keeps
rendering and keeps its type: :guilabel:`academic_base` shows the stored type
in the field :guilabel:`Type` followed by
:guilabel:`(not enabled on this page)`, and saving keeps it. The warning of the
2.4 changelog entry that saving such a record rewrites its type describes the
state before a fix that :guilabel:`academic_base` 2.4 carries as well, see
:ref:`its changelog entry <fgtclb/academic-base:important-ace-666-academic-base>`.
A content element that a page TSconfig of your own already turned into another
type is not repaired. Correct it by hand from its record history.

..  _upgrade-from-2-3-to-3-0-own-configuration:

Adjust your own configuration
-----------------------------

*   The page TSconfig directory is :file:`Configuration/TSconfig/` now, and the
    TypoScript moved into :file:`Configuration/TypoScript/List/`. Take the new
    path of every :typoscript:`@import` from the table of the changelog entry
    linked above. An import of a missing file fails silently.
*   The setup reads constants now, the Fluid root paths of the plugin and the
    switch for the header of the content element. A site package that imports
    a :file:`setup.typoscript` of this extension imports the
    :file:`constants.typoscript` of the same folder into its constants as well.
    Without it the plugin fails with a missing template, and the header renders
    as if it were switched on:

    ..  code-block:: typoscript
        :caption: TypoScript constants of the site package

        @import 'EXT:academic_contacts4pages/Configuration/TypoScript/List/constants.typoscript'

*   A site whose content element layout renders no header can switch the
    header of the contact list on with
    :typoscript:`plugin.tx_academiccontacts4pages.renderContentElementHeader = 1`,
    see :ref:`feature-1790440965` and
    :ref:`configuration-content-element-header`.
*   A site package that replaces the shipped setup with a copy of its own adds
    :typoscript:`plugin.tx_academiccontacts4pages.settings.groupByRole = 1` to
    the copy. Existing content elements store no value for the option
    :guilabel:`Group by role` and read this default, see
    :ref:`feature-1790316809`.
*   A backend user group whose members set the language of a page contact
    needs `tx_academiccontacts4pages_domain_model_contact:sys_language_uid` in
    its allowed excludefields. A site that ran 2.3 on TYPO3 12 adds it, see
    :ref:`important-1791547202`.
*   The contacts take the crop variant and the gender placeholders of the
    persons lists, set in the site settings of :guilabel:`academic_persons`.
    See :ref:`important-1790709369`.

..  _upgrade-from-2-3-to-3-0-templates:

Adapt templates and assets
==========================

..  _upgrade-from-2-3-to-3-0-classes:

Classes and styles
------------------

The templates render speaking classes now, shared by all academic extensions,
and no classes of the theme they were developed with. The card of a contact is
the profile item of :guilabel:`academic_persons`, which carries the speaking
classes of that extension, see its `chapter Styling
<https://docs.typo3.org/p/fgtclb/academic-persons/main/en-us/Styling/Index.html>`__.
The extension ships no stylesheet. Move the selectors of the site stylesheet to
the classes in the table of
:ref:`breaking-contacts4pages-speaking-frontend-classes`. The chapter
:ref:`styling` describes the markup, and :ref:`styling-example` names the
stylesheets of the development instances as a starting point.

..  _upgrade-from-2-3-to-3-0-images:

The contact image and the persons partials
------------------------------------------

The image of a contact is a :html:`<picture>` with WebP sources now, and a
contact whose profile has no image shows the placeholder of the persons
plugins. CSS that selects the image as a direct child of the card no longer
matches. To keep contacts without an image empty, set
:typoscript:`plugin.tx_academicpersons.image.placeholder.default` to an empty
value. A view that renders :file:`Profile/Item`, the plugin view with replaced
partial root paths or the page view of a page template, lists the path of
:guilabel:`academic_base` below the others:

..  code-block:: typoscript

    plugin.tx_academiccontacts4pages.view.partialRootPaths {
        -1 = EXT:academic_base/Resources/Private/Partials/
    }

See :ref:`breaking-contacts4pages-profile-images-render-as-picture`.

The partial :file:`Profile/Item.html` of :guilabel:`academic_persons`, which
renders every contact, is built from small partials now: the detail link, the
name, the contracts and the image. The name leaves out an empty part and shows
the academic title. A copy of :file:`Profile/Item.html` keeps rendering what it
rendered before. Drop it in favour of the small partial it was meant to change,
described in the `chapter Templates of academic_persons
<https://docs.typo3.org/p/fgtclb/academic-persons/main/en-us/Templates/Index.html>`__.
This plugin reads the persons partials through partial root paths of its own,
so an override of them is registered for
:typoscript:`plugin.tx_academiccontacts4pages` as well as for
:typoscript:`plugin.tx_academicpersons`. See
:ref:`important-profile-item-partials-are-overridable`.

..  _upgrade-from-2-3-to-3-0-icons:

Icons
-----

The icons of the extension are replaced and have new identifiers. They are
backend icons, registered in :file:`Configuration/Icons.php`:

..  list-table::
    :header-rows: 1

    *   -   2.3 identifier
        -   3.0 identifier
    *   -   ``academic_contacts4pages``
        -   ``tx-academiccontacts4pages-plugin-contacts``
    *   -   ``tx_academiccontacts4pages_domain_model_contact``
        -   ``tx-academiccontacts4pages-record-contact``
    *   -   ``tx_academiccontacts4pages_domain_model_role``
        -   ``tx-academiccontacts4pages-record-role``

``tx_academiccontacts4pages_domain_model_contract`` is removed without a
successor, and the files :file:`Resources/Public/Icons/Contract.svg` and
:file:`Resources/Public/Icons/Role.svg` are removed. Replace the identifiers
in templates, page TSconfig, TCA overrides and PHP code, move a replacement
icon in the :file:`Configuration/Icons.php` of a site package to the new
identifier, and change :css:`.icon-*` selectors. See
:ref:`breaking-contacts4pages-icon-identifiers-renamed`.

..  _upgrade-from-2-3-to-3-0-overrides:

Template overrides
------------------

Versions 2.4 and 3.0 change these templates and partials. A copy of one of them
in a site package keeps rendering the copy, so compare each copy with the
shipped file of 3.0 and take over the changes. The paths below are relative to
:file:`Resources/Private/`:

..  list-table::
    :header-rows: 1

    *   -   File
        -   What changed
        -   Entry
    *   -   :file:`Templates/Contacts/List.html`
        -   Contacts without a role are rendered after the role groups, from
            the new variable :html:`{contactsWithoutRole}`. The option
            :guilabel:`Group by role` switches the role groups off, and every
            contact renders through the new partial
            :file:`Partials/Contacts/Item.html`. The contacts sit in an
            `ace-content`, each group in an `ace-itemlist`, the outermost
            element carries `academic-contacts4pages-list`, and the header of
            the content element renders while
            :typoscript:`settings.renderContentElementHeader` is set.
        -   :ref:`important-ace-322-academic-contacts4pages`,
            :ref:`feature-1790316809`,
            :ref:`upgrade-from-2-3-to-3-0-classes`
    *   -   :file:`Partials/Contacts/Item.html`
        -   New. A site package that already ships a partial of that name in a
            partial root path of this plugin has it rendered for every contact
            now. Rename it, or turn it into the contact card. The role carries
            `ace-role`.
        -   :ref:`feature-1790316809`,
            :ref:`upgrade-from-2-3-to-3-0-classes`

The view variable :html:`{contacts}` is a plain list now, no query result. A
template that reads :html:`{contacts.first}` gets nothing, see
:ref:`important-contacts-without-visible-profile-are-skipped`.

The upgrade check with the extension key and a site compares a copy with the
shipped file, see :ref:`upgrade-from-2-3-to-3-0-upgrade-check`.

..  _upgrade-from-2-3-to-3-0-code:

Adapt your code
===============

*   A class that extends
    :php:`\FGTCLB\AcademicContacts4pages\Domain\Repository\ContactRepository`
    and overrides :php:`findByPid()` adds the parameter
    :php:`bool $showHidden = false`, see
    :ref:`breaking-ace-250-academic-contacts4pages`.
*   :php:`\FGTCLB\AcademicContacts4pages\Backend\FormEngine\ContractItemsProcFunc`
    is removed. Own TCA or FlexForm configuration uses
    :php:`\FGTCLB\AcademicPersons\Backend\FormEngine\ContractItems->itemsProcFunc`
    instead, see :ref:`breaking-1783613369`.
*   A subclass of
    :php:`\FGTCLB\AcademicContacts4pages\DataProcessing\ContactsProcessor`
    that is named in TypoScript has to be autowired by the :file:`Services.yaml`
    of its site package, because the processor takes its collaborator through
    the constructor. Code that reads the contacts of the processor or the
    plugin as a query result accepts a list. See
    :ref:`important-contacts-without-visible-profile-are-skipped`.
*   A copy of the controller of the content element, or a subclass of the
    processor, that only changes which contacts a page shows is replaced by a
    listener of
    :php:`\FGTCLB\AcademicContacts4pages\Event\ModifyPageContactsEvent`. See
    :ref:`feature-1790767100` and :ref:`developers-page-contacts-event`.
*   Additional view variables of the content element come from a listener of
    :php:`\FGTCLB\AcademicBase\Event\ModifyPluginViewEvent`, see
    :ref:`feature-plugin-view-event` and
    :ref:`developers-event-modify-plugin-view-event`.

..  _upgrade-from-2-3-to-3-0-check:

Check the result
================

*   The upgrade check exits with ``0``, without and with the extension key,
    see :ref:`upgrade-from-2-3-to-3-0-upgrade-check`. A folder that overrides
    the persons partials for this plugin as well is the exception: the check
    with this extension key reports its files as :bash:`missing-upstream`, so
    check that folder with :guilabel:`academic_persons` instead.
*   The content element, called :guilabel:`Person’s Contacts on the Page` now,
    is offered for new content on the pages that should have it, and existing
    ones keep their type. See
    :ref:`important-content-element-titles-and-descriptions`.
*   The order of the contacts on a page, in a contract and in a contacts role
    stays as it was after one of them is saved.
*   Pages that mix contacts with and without a role list all of them.
*   Translations of page contacts that an import created on TYPO3 12 may point
    at a wrong original. The query in :ref:`important-1791547202` finds them.
*   Translated contacts may point at a translation of their contacts role, and
    localizing a contacts role may have left duplicates. The queries in
    :ref:`important-translated-contacts-keep-their-role` find both.
*   The address selects of a page contact are relations. Existing page
    contacts get their rows in the reference index on their next save, or at
    once with :bash:`vendor/bin/typo3 referenceindex:update`, see
    :ref:`important-ace-481-academic-contacts4pages`.
*   Every release of version 2 can hold page contacts that lost their page or
    their contacts role when a workspace was published by its publish date, or
    when a page and a contacts role were edited in the same workspace. The
    queries in :ref:`important-page-contacts-keep-their-page-on-publish` find
    them.
*   Translated contacts that point at a page without a translation in their
    language are left from older versions. They are not rendered twice, and
    can be deleted, see :ref:`important-1788197604`.
