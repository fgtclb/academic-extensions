..  _upgrade-from-2-4-to-3-0:

=========================
Upgrading from 2.4 to 3.0
=========================

Version 3.0 drops TYPO3 12, renders the contact list with speaking classes and
the profile images as responsive pictures, and renames the icons of the
extension. With :guilabel:`academic_persons` 3.0 the contract a page contact
belongs to becomes workspace aware. It changes no database table of this
extension and brings no upgrade wizard. Work through the sections in order.
Skip a step that does not apply to the installation.

..  _upgrade-from-2-4-to-3-0-before-you-start:

Before you start
================

Version 3.0 runs on TYPO3 13.4 from 13.4.35 on and on TYPO3 14.3 from 14.3.7
on, with PHP 8.2 or later.

:guilabel:`academic_contacts4pages` 3.0 needs these packages. Upgrade them
first or in the same deployment, in this order:

*   :composer:`fgtclb/environment-state-manager` 2.x, which
    :guilabel:`academic_base` 3.0 and :guilabel:`academic_persons` 3.0 require
    instead of 1.x. Composer updates it on its own. A classic installation
    replaces :guilabel:`environment_state_manager` 1.x with 2.x before it
    activates either of them.
*   :composer:`fgtclb/academic-base` 3.0, the same release as this extension.
*   :composer:`fgtclb/academic-persons` 3.0, the same release as this
    extension. Its upgrade chapter has steps of its own.

These changes have to ship in the same deployment as the update:

*   A site package that imports the :file:`setup.typoscript` of this extension
    by path imports the :file:`constants.typoscript` of the same folder as
    well, see :ref:`upgrade-from-2-4-to-3-0-own-configuration`.
*   A view that renders the persons partials with replaced partial root paths
    lists the path of :guilabel:`academic_base`, see
    :ref:`upgrade-from-2-4-to-3-0-images`.

The module paths on this page are those of TYPO3 13, where the upgrade runs.

..  _upgrade-from-2-4-to-3-0-typo3:

Update TYPO3 first
==================

Update TYPO3 to 13.4.35 or later while the site still runs 2.4, which supports
TYPO3 13.4. Version 3.0 no longer installs on TYPO3 12, and Composer and the
Extension Manager refuse it next to an older patch release of TYPO3 13.

Stay on TYPO3 13 until the upgrade is complete. This extension requires
:guilabel:`academic_persons`, whose content element wizard reads the column
:sql:`tt_content.list_type`, which TYPO3 14 removes, so it only runs on
TYPO3 13, see its `upgrade chapter
<https://docs.typo3.org/p/fgtclb/academic-persons/main/en-us/Upgrade/From24To30.html>`__.
The same holds for the content element wizards of :guilabel:`academic_jobs`,
:guilabel:`academic_persons_edit` and :guilabel:`academic_projects` when the
installation also has them. Move to TYPO3 14.3.7 or later once they have run.

See :ref:`breaking-removed-typo3-v12-support` and
:ref:`important-minimum-typo3-versions`.

..  _upgrade-from-2-4-to-3-0-package:

Update the package
==================

..  code-block:: bash

    composer require -W \
        'fgtclb/academic-base':'~3.0.0' \
        'fgtclb/academic-persons':'~3.0.0' \
        'fgtclb/academic-contacts4pages':'~3.0.0'

On a classic installation, update :guilabel:`environment_state_manager` to 2.x
from the TER in :guilabel:`Admin Tools > Extensions` first, then update
:guilabel:`academic_base`, :guilabel:`academic_persons` and
:guilabel:`academic_contacts4pages` there, in this order. Run the commands of
this page with :bash:`typo3/sysext/core/bin/typo3` instead of
:bash:`vendor/bin/typo3`.

..  code-block:: bash

    vendor/bin/typo3 cache:flush

..  _upgrade-from-2-4-to-3-0-database:

Update the database
===================

..  code-block:: bash

    vendor/bin/typo3 extension:setup

Or apply the changes in :guilabel:`Admin Tools > Maintenance > Analyze
Database Structure`.

The comparison changes no table of this extension. It applies the changes of
the index definitions TYPO3 13.4.35 and 14.3.7 bring, and those of
:guilabel:`academic_persons`. The contract table of that extension, to which
page contacts belong as inline children, gains the columns of a workspace
aware table, so a contract and its page contacts are drafted and published
together. The section :guilabel:`Update the database` of the `upgrade chapter
of academic_persons
<https://docs.typo3.org/p/fgtclb/academic-persons/main/en-us/Upgrade/From24To30.html>`__
describes the comparison of that table on SQLite. See
:ref:`important-contact-inline-parent-is-workspace-aware`.

..  _upgrade-from-2-4-to-3-0-configuration:

Adapt the configuration
=======================

..  _upgrade-from-2-4-to-3-0-upgrade-check:

The upgrade check
-----------------

The console command :bash:`academic:upgrade:check` of :guilabel:`academic_base`
reads the stored configuration of the installation and reports the static
templates, page TSconfig imports and site sets that no longer reach an academic
extension. Run it on the staging copy right after the package update:

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

..  _upgrade-from-2-4-to-3-0-own-configuration:

Adjust your own configuration
-----------------------------

The site set, the static templates and the page TSconfig of this extension keep
their names and paths from 2.4.

*   A site whose content element layout renders no header can switch the
    header of the contact list on with
    :typoscript:`plugin.tx_academiccontacts4pages.renderContentElementHeader = 1`.
    A site package that imports the :file:`setup.typoscript` of this extension
    by path imports the :file:`constants.typoscript` of the same folder as
    well, otherwise the header renders as if it were switched on. See
    :ref:`feature-1790440965` and :ref:`configuration-content-element-header`.
*   The contacts take the crop variant and the gender placeholders of the
    persons lists, set in the site settings of :guilabel:`academic_persons`.
    See :ref:`important-1790709369`.

..  _upgrade-from-2-4-to-3-0-templates:

Adapt templates and assets
==========================

..  _upgrade-from-2-4-to-3-0-classes:

Classes and styles
------------------

The templates render speaking classes now, shared by all academic extensions,
and no classes of the theme they were developed with. The card of a contact is
the profile item of :guilabel:`academic_persons`, which carries the speaking
classes of that extension, see its `chapter Styling
<https://docs.typo3.org/p/fgtclb/academic-persons/main/en-us/Styling/Index.html>`__.
The extension shipped no stylesheet in 2.4 and ships none in 3.0. Move the
selectors of the site stylesheet to the classes in the table of
:ref:`breaking-contacts4pages-speaking-frontend-classes`. The chapter
:ref:`styling` describes the markup, and :ref:`styling-example` names the
stylesheets of the development instances as a starting point.

..  _upgrade-from-2-4-to-3-0-images:

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
name, the contracts and the image. A copy of :file:`Profile/Item.html` keeps
rendering what it rendered before. Drop it in favour of the small partial it
was meant to change, described in the `chapter Templates of academic_persons
<https://docs.typo3.org/p/fgtclb/academic-persons/main/en-us/Templates/Index.html>`__.
This plugin reads the persons partials through partial root paths of its own,
so an override of them is registered for
:typoscript:`plugin.tx_academiccontacts4pages` as well as for
:typoscript:`plugin.tx_academicpersons`. See
:ref:`important-profile-item-partials-are-overridable`.

..  _upgrade-from-2-4-to-3-0-icons:

Icons
-----

The icons of the extension are replaced and have new identifiers. They are
backend icons, registered in :file:`Configuration/Icons.php`:

..  list-table::
    :header-rows: 1

    *   -   2.4 identifier
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

..  _upgrade-from-2-4-to-3-0-overrides:

Template overrides
------------------

Version 3.0 changes these templates and partials. A copy of one of them in a
site package keeps rendering the copy, so compare each copy with the shipped
file and take over the changes. The paths below are relative to
:file:`Resources/Private/`:

..  list-table::
    :header-rows: 1

    *   -   File
        -   What changed
        -   Entry
    *   -   :file:`Templates/Contacts/List.html`
        -   The contacts sit in an `ace-content`, each group in an
            `ace-itemlist`, the outermost element carries
            `academic-contacts4pages-list`, and the header of the content
            element renders while
            :typoscript:`settings.renderContentElementHeader` is set.
        -   :ref:`upgrade-from-2-4-to-3-0-classes`,
            :ref:`feature-1790440965`
    *   -   :file:`Partials/Contacts/Item.html`
        -   The role carries `ace-role`.
        -   :ref:`upgrade-from-2-4-to-3-0-classes`

The upgrade check with the extension key and a site compares a copy with the
shipped file, see :ref:`upgrade-from-2-4-to-3-0-upgrade-check`.

..  _upgrade-from-2-4-to-3-0-code:

Adapt your code
===============

*   A copy of the controller of the content element, or a subclass of
    :php:`\FGTCLB\AcademicContacts4pages\DataProcessing\ContactsProcessor`,
    that only changes which contacts a page shows is replaced by a listener of
    :php:`\FGTCLB\AcademicContacts4pages\Event\ModifyPageContactsEvent`. See
    :ref:`feature-1790767100` and :ref:`developers-page-contacts-event`.
*   Additional view variables of the content element come from a listener of
    :php:`\FGTCLB\AcademicBase\Event\ModifyPluginViewEvent`, see
    :ref:`feature-plugin-view-event` and
    :ref:`developers-event-modify-plugin-view-event`.

..  _upgrade-from-2-4-to-3-0-check:

Check the result
================

*   The upgrade check exits with ``0``, without and with the extension key,
    see :ref:`upgrade-from-2-4-to-3-0-upgrade-check`. A folder that overrides
    the persons partials for this plugin as well is the exception: the check
    with this extension key reports its files as :bash:`missing-upstream`, so
    check that folder with :guilabel:`academic_persons` instead.
*   The content element is offered as :guilabel:`Person’s Contacts on the Page`
    now, see :ref:`important-content-element-titles-and-descriptions`.
*   A contact whose profile carries a title shows it in front of the name.
*   Every release of version 2 can hold page contacts that lost their page or
    their contacts role when a workspace was published by its publish date, or
    when a page and a contacts role were edited in the same workspace. The
    queries in :ref:`important-page-contacts-keep-their-page-on-publish` find
    them.
*   Translated contacts that point at a page without a translation in their
    language are left from older versions. They are not rendered twice, and
    can be deleted, see :ref:`important-1788197604`.
