..  _breaking-site-sets-and-static-templates-restructured:

===============================================================
Breaking: Site sets and static templates have been restructured
===============================================================

Description
===========

The TypoScript of this extension was shipped through a chain of
:typoscript:`@import` statements: the static template read
:file:`Configuration/TypoScript/`, whose :file:`setup.typoscript` imported
:file:`Configuration/TypoScript/Page/`, and the site set
:yaml:`fgtclb/academic-contacts4pages` shipped a :file:`setup.typoscript` of its
own that imported the first one again. The page TSconfig existed only as
:file:`Configuration/TsConfig/page.tsconfig`, reachable through the
auto-included :file:`Configuration/page.tsconfig` and through nothing else — it
was not selectable on a page at all.

Both mechanisms now read one physical copy of every file, and both of them
deliver the extension per component instead of as one block:

*   :file:`Configuration/TypoScript/List/` holds the TypoScript of the
    :guilabel:`Contact list` content element and is what the static template
    registers *and* what the set points its :yaml:`typoscript` key at. Its
    :file:`setup.typoscript` carries what
    :file:`Configuration/TypoScript/Page/Plugin.typoscript` and
    :file:`Configuration/TypoScript/Page/Fluidtemplate.typoscript` used to
    carry, with one difference: the three Fluid root paths were literals there
    and are constants now, declared in the :file:`constants.typoscript` beside
    it.
*   :file:`Configuration/TSconfig/List/page.tsconfig` holds its page TSconfig
    and is what the page field :guilabel:`Page TSconfig` offers *and* what the
    set points its :yaml:`pagets` key at.
*   :file:`Configuration/TypoScript/Full/` and
    :file:`Configuration/TSconfig/Full/page.tsconfig` are the aggregates for
    installations that do not use site sets — which on TYPO3 v12 is every
    installation, since site sets arrived in v13.1 (Feature: #103437).

Note the directory as well: :file:`Configuration/TsConfig/` was renamed to
:file:`Configuration/TSconfig/`, the spelling TYPO3 itself uses, so that all
academic extensions agree on one.

The extension ships a :file:`constants.typoscript` for the first time. It
declares the three Fluid root paths of the plugin —
:typoscript:`plugin.tx_academiccontacts4pages.view.templateRootPath`,
:typoscript:`…partialRootPath` and :typoscript:`…layoutRootPath` — with exactly
the values that were hard coded in the setup before, so a site that changes
nothing renders exactly as it did. What is new is that these paths can now be
overridden as constants, in the constant editor or in
:file:`config/sites/<site>/constants.typoscript`.

The content element is now **hidden by default**. The always-included
:file:`Configuration/page.tsconfig` removes
:typoscript:`academiccontacts4pages_list` from the selectable content element
types, and the page TSconfig of the component adds it back — so the element is
offered where it is wanted instead of on every page of every installation. The
TCA registration itself did not move, so the frontend renders existing records
exactly as before. Editing such a record in the backend is a different matter —
read the warning below before upgrading.

Impact
======

A :sql:`sys_template` record that selected the old static template keeps its
stored value, and that value now points at a folder holding no
:file:`constants.typoscript` and no :file:`setup.typoscript`. It is not an
error — the frontend simply loses the plugin configuration and the data
processor that assigns the contacts of a page to the page template, and the
plugin renders with no template paths.

A site package that imported one of the shipped files by path fails to resolve
it. :typoscript:`@import` of a missing file is silent, so this also shows up as
missing configuration rather than as an error message.

The :guilabel:`Contact list` content element is no longer offered in the backend
until the page TSconfig of the component is included, through the site set or
through the page field :guilabel:`Page TSconfig`. On TYPO3 v12 the page field is
the only one of the two that works, and that same file also carries the entry of
the new content element wizard, which v12 builds from page TSconfig rather than
from TCA.

..  warning::

    Do not open an existing :guilabel:`Contact list` record in the backend form
    on a page that does not include that page TSconfig. An item removed through
    :typoscript:`TCEFORM.tt_content.CType.removeItems` is excluded from the
    :guilabel:`[ invalid value ]` fallback TYPO3 otherwise adds for a stored
    value it does not know, and the stored value is dropped from the form data
    as well. The field :guilabel:`Type` therefore comes up with nothing
    selected, and **saving the record writes whatever the browser preselected
    into** :sql:`CType` — the record silently becomes another content element.
    The frontend keeps rendering it correctly until that happens.

    Include the page TSconfig of the component on every page tree that holds
    such records, and do it before editing them.

The set :yaml:`fgtclb/academic-contacts4pages` keeps its name and keeps
delivering everything, so a site configuration that depends on it needs no
change.

Affected Installations
======================

Installations that select the static template of this extension in a
:sql:`sys_template` record, that import one of the shipped files from an own
site package, or that use the content element without including the page
TSconfig of this extension.

Migration
=========

Replace the static template entry in the :sql:`sys_template` record:

..  list-table::
    :header-rows: 1

    *   -   Old entry
        -   New entry
    *   -   :guilabel:`Contacts for Pages (academic_contacts4pages)`, stored as
            `EXT:academic_contacts4pages/Configuration/TypoScript/`
        -   :guilabel:`Academic Contacts4Pages: All components
            (academic_contacts4pages)`, stored as
            `EXT:academic_contacts4pages/Configuration/TypoScript/Full` — or
            :guilabel:`Academic Contacts4Pages: Contact list
            (academic_contacts4pages)`, stored as
            `EXT:academic_contacts4pages/Configuration/TypoScript/List`

Add the page TSconfig entry, which did not exist before, in the page record of
the site root, tab :guilabel:`Resources`, field :guilabel:`Page TSconfig`:
:guilabel:`Academic Contacts4Pages: All components (academic_contacts4pages)`,
stored as
`EXT:academic_contacts4pages/Configuration/TSconfig/Full/page.tsconfig`. Without
it the content element is not selectable any more, and existing records of it
lose their :sql:`CType` when they are saved from the backend form.

Sites that use the site set instead need no migration — but they must not use
both mechanisms at once, see the :guilabel:`Configuration` chapter. That applies
to TYPO3 v13 only; on v12 the static entries above are not optional.

Adjust every :typoscript:`@import` in an own site package:

..  list-table::
    :header-rows: 1

    *   -   Old path
        -   New path
    *   -   `EXT:academic_contacts4pages/Configuration/TypoScript/setup.typoscript`
        -   `EXT:academic_contacts4pages/Configuration/TypoScript/List/setup.typoscript`
    *   -   `EXT:academic_contacts4pages/Configuration/TypoScript/Page/Plugin.typoscript`
        -   `EXT:academic_contacts4pages/Configuration/TypoScript/List/setup.typoscript`
    *   -   `EXT:academic_contacts4pages/Configuration/TypoScript/Page/Fluidtemplate.typoscript`
        -   `EXT:academic_contacts4pages/Configuration/TypoScript/List/setup.typoscript`
    *   -   `EXT:academic_contacts4pages/Configuration/TypoScript/Page/`
        -   `EXT:academic_contacts4pages/Configuration/TypoScript/List/setup.typoscript`
    *   -   `EXT:academic_contacts4pages/Configuration/TsConfig/page.tsconfig`
        -   `EXT:academic_contacts4pages/Configuration/TSconfig/List/page.tsconfig`

..  warning::

    A site package that imports the setup must import the constants as well.
    The three Fluid root paths are constants now, and TypoScript leaves an
    undefined constant as its own literal text rather than reporting it — so a
    site package that imports only
    :file:`…/Configuration/TypoScript/List/setup.typoscript` ends up with
    :typoscript:`templateRootPaths.10` set to the string
    `{$plugin.tx_academiccontacts4pages.view.templateRootPath}` and the plugin
    fails with a missing template.

    ..  code-block:: typoscript

        @import 'EXT:academic_contacts4pages/Configuration/TypoScript/List/constants.typoscript'

    Neither of the two shipped delivery mechanisms is affected: both read the
    whole folder.

A site configuration may name the new component set instead of the aggregate:

..  list-table::
    :header-rows: 1

    *   -   Set
        -   Delivers
    *   -   `fgtclb/academic-contacts4pages`
        -   Unchanged in name, now delivers through the component set below.
    *   -   `fgtclb/academic-contacts4pages-list`
        -   The :guilabel:`Contact list` content element only.

..  index:: TypoScript, TSConfig, Backend, ext:academic_contacts4pages
