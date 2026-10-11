..  _breaking-site-sets-and-page-tsconfig-restructured:

============================================================
Breaking: Site sets and page TSconfig have been restructured
============================================================

Description
===========

This extension ships one piece of page TSconfig: the label of the content
element group :guilabel:`Academic`, the group every academic extension sorts
its content elements into. It lived in
:file:`Configuration/TsConfig/Wizards/NewContentElement.tsconfig` and was
reachable through :file:`Configuration/page.tsconfig`, which TYPO3 includes for
the whole installation, and through the :file:`page.tsconfig` of the site set
:yaml:`fgtclb/academic-base-ctype-group` in
:file:`Configuration/Sets/AcademicBaseCTypeGroup/`.

The file moved to :file:`Configuration/TSconfig/CTypeGroup/page.tsconfig` and is
now reachable in three ways, all of them reading that one copy. Note the
directory as well: :file:`Configuration/TsConfig/` was renamed to
:file:`Configuration/TSconfig/`, the spelling TYPO3 itself uses, so that all
academic extensions agree on one.

*   :file:`Configuration/page.tsconfig`, unchanged in effect. The group is
    labelled on every installation, whatever the site configuration says.
*   The site set :yaml:`fgtclb/academic-base-ctype-group`, which moved to
    :file:`Configuration/Sets/CTypeGroup/` and points its :yaml:`pagets` key
    at the file instead of importing it from a :file:`page.tsconfig` of its
    own. Site sets arrived in TYPO3 v13.1, so this way exists on v13 and not
    on v12.
*   The page field :guilabel:`Page TSconfig`, for which the file is registered.

The extension also gained the aggregate set :yaml:`fgtclb/academic-base`,
following the convention every academic extension now uses: one set per
component, plus one aggregate named after the package.

Impact
======

A site package that imported one of the old files by path fails to resolve it.
:typoscript:`@import` of a missing file is silent, so there is no error
message. The group label itself does not go missing, because
:file:`Configuration/page.tsconfig` of this extension still delivers it to every
installation. What is left is an import that loads nothing, and an override in
the same file that relied on the order of the import can end up applied before
the label instead of after it.

Nothing else changes. The set :yaml:`fgtclb/academic-base-ctype-group` keeps its
name, the group label keeps being delivered to every installation without any
configuration, and the TCA registration of the group did not move.

What did change alongside the path is the :yaml:`label` of that set, from
`Academic Base - CType Group` to `Academic Base: Content element group`. It is
what the backend shows in the list of available sets. Nothing references a set
by its label, so this needs no action.

Affected Installations
======================

Installations that import one of the page TSconfig files of this extension by
path, from an own site package or from own page TSconfig.

Migration
=========

Adjust every :typoscript:`@import`:

..  list-table::
    :header-rows: 1

    *   -   Old path
        -   New path
    *   -   `EXT:academic_base/Configuration/TsConfig/Wizards/NewContentElement.tsconfig`
        -   `EXT:academic_base/Configuration/TSconfig/CTypeGroup/page.tsconfig`
    *   -   `EXT:academic_base/Configuration/TsConfig/Wizards/*.tsconfig`
        -   `EXT:academic_base/Configuration/TSconfig/Full/page.tsconfig`
    *   -   `EXT:academic_base/Configuration/Sets/AcademicBaseCTypeGroup/page.tsconfig`
        -   `EXT:academic_base/Configuration/TSconfig/CTypeGroup/page.tsconfig`

..  code-block:: typoscript

    # Before
    @import 'EXT:academic_base/Configuration/TsConfig/Wizards/NewContentElement.tsconfig'

    # After
    @import 'EXT:academic_base/Configuration/TSconfig/CTypeGroup/page.tsconfig'

An import that only served to label the group can be removed instead, since
:file:`Configuration/page.tsconfig` of this extension delivers the label on
every installation.

An installation that includes page TSconfig through the page field
:guilabel:`Page TSconfig` rather than through a site set can select the file
there instead of importing it:

..  list-table::
    :header-rows: 1

    *   -   Entry
        -   Delivers
    *   -   :guilabel:`Academic Base: Content element group (academic_base)`
        -   The label of the content element group :guilabel:`Academic`.
    *   -   :guilabel:`Academic Base: All components (academic_base)`
        -   Every component this extension ships, in one entry.

Site configurations need no change. :yaml:`fgtclb/academic-base-ctype-group`
still exists under that name, and :yaml:`fgtclb/academic-base` is the new
aggregate that depends on it.

..  index:: TSConfig, Backend, ext:academic_base
