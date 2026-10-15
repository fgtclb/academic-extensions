..  _upgrade-from-2-3-to-2-4:

=========================
Upgrading from 2.3 to 2.4
=========================

Version 2.4 delivers the configuration of :guilabel:`academic_contacts4pages`
per component, hides its content element until a site enables it and gives a
contact a position of its own in its contract and in its contacts role. A new
upgrade wizard migrates the content elements of version 1.x. Work through the
sections in order. Skip a step that does not apply to the installation.

..  _upgrade-from-2-3-to-2-4-before-you-start:

Before you start
================

Version 2.4 runs on TYPO3 12.4 from 12.4.22 on and on TYPO3 13.4, with PHP 8.1
or later. That is the same as for 2.3, so the TYPO3 core does not have to
change for this upgrade.

:guilabel:`academic_contacts4pages` 2.4 needs these packages. Upgrade them
first or in the same deployment, in this order:

*   :composer:`fgtclb/environment-state-manager` 1.x, new in 2.4, which
    :guilabel:`academic_base` 2.4 and :guilabel:`academic_persons` 2.4 require.
    Composer installs it on its own. A classic installation installs
    :guilabel:`environment_state_manager` before it activates either of them.
*   :composer:`fgtclb/academic-base` 2.4, the same release as this extension.
*   :composer:`fgtclb/academic-persons` 2.4, the same release as this
    extension. Its upgrade chapter has steps of its own.

The extension now declares the system extension :guilabel:`install` as a
dependency, for its upgrade wizards. TYPO3 keeps that extension active in every
installation, so this needs no action.

..  _upgrade-from-2-3-to-2-4-package:

Update the package
==================

..  code-block:: bash

    composer require -W \
        'fgtclb/academic-base':'~2.4.0' \
        'fgtclb/academic-persons':'~2.4.0' \
        'fgtclb/academic-contacts4pages':'~2.4.0'

A project that requires branch ``2`` as ``2.x-dev`` keeps an inline alias such
as ``2.x-dev as 2.4.x-dev`` working, see :ref:`important-1790796661`.

On a classic installation, install :guilabel:`environment_state_manager` 1.x
from the TER in :guilabel:`Admin Tools > Extensions` first, then update
:guilabel:`academic_base`, :guilabel:`academic_persons` and
:guilabel:`academic_contacts4pages` there, in this order. Run the commands of
this page with :bash:`typo3/sysext/core/bin/typo3` instead of
:bash:`vendor/bin/typo3`.

..  code-block:: bash

    vendor/bin/typo3 cache:flush

..  _upgrade-from-2-3-to-2-4-database:

Update the database
===================

Apply the database changes in :guilabel:`Admin Tools > Maintenance > Analyze
Database Structure`, or on the command line:

..  code-block:: bash

    vendor/bin/typo3 extension:setup

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

The language columns of the table are declared in its TCA now. They exist
already, so the schema does not change for them, see
:ref:`important-1791547202`.

..  _upgrade-from-2-3-to-2-4-wizards:

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

..  _upgrade-from-2-3-to-2-4-configuration:

Adapt the configuration
=======================

The TypoScript and the page TSconfig are now delivered per content element,
and the :guilabel:`Contacts for this page` content element is hidden in the
whole installation until the page TSconfig of its component is included. See
:ref:`breaking-site-sets-and-static-templates-restructured`.

..  _upgrade-from-2-3-to-2-4-components:

Include the components
----------------------

*   **Site set, TYPO3 13 only.** A site that depends on the set
    `fgtclb/academic-contacts4pages` needs no change. The set keeps its name
    and now brings the page TSconfig along.
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
*   **Page TSconfig.** A site without the site set, which is every site on
    TYPO3 12, adds :guilabel:`Academic Contacts4Pages: All components
    (academic_contacts4pages)` in the field :guilabel:`Include static Page
    TSconfig (from extensions)` on the tab :guilabel:`Resources` of its root
    page, see :ref:`static-pagetsconfig`. Without it the content element is not
    offered for new content.
*   Use one of the two mechanisms per site, see
    :ref:`one-mechanism-per-site`.

Until the component is included, an existing content element keeps rendering
and keeps its type: :guilabel:`academic_base` 2.4 shows the stored type in the
field :guilabel:`Type` followed by :guilabel:`(not enabled on this page)`, and
saving keeps it. The warning of the changelog entry that saving such a record
rewrites its type describes the state before that fix. A content element that
an older version or a page TSconfig of your own already turned into another type
is not repaired. Correct it by hand from its record history.

..  _upgrade-from-2-3-to-2-4-own-configuration:

Adjust your own configuration
-----------------------------

*   The page TSconfig directory is :file:`Configuration/TSconfig/` now, and the
    TypoScript moved into :file:`Configuration/TypoScript/List/`. Take the new
    path of every :typoscript:`@import` from the table of
    :ref:`breaking-site-sets-and-static-templates-restructured`. An import of a
    missing file fails silently.
*   The setup reads three constants now, the Fluid root paths of the plugin. A
    site package that imports a :file:`setup.typoscript` of this extension
    imports the :file:`constants.typoscript` of the same folder into its
    constants as well, otherwise the plugin fails with a missing template:

    ..  code-block:: typoscript
        :caption: TypoScript constants of the site package

        @import 'EXT:academic_contacts4pages/Configuration/TypoScript/List/constants.typoscript'

*   A site package that replaces the shipped setup with a copy of its own adds
    :typoscript:`plugin.tx_academiccontacts4pages.settings.groupByRole = 1` to
    the copy. Existing content elements store no value for the new option
    :guilabel:`Group by role` and read this default, see
    :ref:`feature-1790316809`.
*   On TYPO3 12, add
    `tx_academiccontacts4pages_domain_model_contact:sys_language_uid` to the
    allowed excludefields of every backend user group whose members set the
    language of a page contact, see :ref:`important-1791547202`.

..  _upgrade-from-2-3-to-2-4-templates:

Adapt templates and assets
==========================

A copy of a template or partial in a site package keeps rendering the copy, so
compare each copy with the shipped file and take over the changes. The paths
below are relative to :file:`Resources/Private/`:

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
            :file:`Partials/Contacts/Item.html`.
        -   :ref:`important-ace-322-academic-contacts4pages`,
            :ref:`feature-1790316809`
    *   -   :file:`Partials/Contacts/Item.html`
        -   New. A site package that already ships a partial of that name in a
            partial root path of this plugin has it rendered for every contact
            now. Rename it, or turn it into the contact card.
        -   :ref:`feature-1790316809`
    *   -   :file:`Partials/Profile/Item.html` of
            :guilabel:`academic_persons`
        -   The name leaves out an empty middle name, which a copy of the
            partial takes over in its :html:`header` argument.
        -   :ref:`important-ace-853-academic-contacts4pages`

The view variable :html:`{contacts}` is a plain list now, no query result. A
template that reads :html:`{contacts.first}` gets nothing, see
:ref:`important-contacts-without-visible-profile-are-skipped`.

No asset of this extension changed.

..  _upgrade-from-2-3-to-2-4-code:

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
    the constructor now. Code that reads the contacts of the processor or the
    plugin as a query result accepts a list. See
    :ref:`important-contacts-without-visible-profile-are-skipped`.

..  _upgrade-from-2-3-to-2-4-check:

Check the result
================

*   The :guilabel:`Contacts for this page` content element is offered for new
    content on the pages that should have it, and existing ones keep their
    type.
*   The order of the contacts on a page, in a contract and in a contacts role
    stays as it was after one of them is saved.
*   Pages that mix contacts with and without a role list all of them.
*   On TYPO3 12, translations of page contacts that an import created may point
    at a wrong original. The query in :ref:`important-1791547202` finds them.
*   Translated contacts may point at a translation of their contacts role, and
    localizing a contacts role may have left duplicates. The queries in
    :ref:`important-translated-contacts-keep-their-role` find both.
*   The address selects of a page contact are relations now. Existing page
    contacts get their rows in the reference index on their next save, or at
    once with :bash:`vendor/bin/typo3 referenceindex:update`.
