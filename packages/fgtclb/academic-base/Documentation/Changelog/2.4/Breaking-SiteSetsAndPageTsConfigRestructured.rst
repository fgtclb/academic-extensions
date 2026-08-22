..  _breaking-site-sets-and-page-tsconfig-restructured:

============================================================
Breaking: Site sets and page TSconfig have been restructured
============================================================

Description
===========

This extension ships one piece of page TSconfig: the label of the content
element group :guilabel:`Academic`, the group every academic extension sorts
its content elements into. It lived in
:file:`Configuration/TSconfig/Wizards/NewContentElement.tsconfig` and was
reachable only through :file:`Configuration/page.tsconfig`, which TYPO3 includes
for the whole installation.

The file moved to :file:`Configuration/TSconfig/CTypeGroup/page.tsconfig` and is
now reachable in three ways, all of them reading that one copy:

*   :file:`Configuration/page.tsconfig`, unchanged in effect — the group is
    labelled on every installation, whatever the site configuration says.
*   The site set :yaml:`fgtclb/academic-base-ctype-group`, which points its
    :yaml:`pagets` key at the file instead of carrying a copy.
*   The page field :guilabel:`Page TSconfig`, for which the file is registered.

The extension also gained the aggregate set :yaml:`fgtclb/academic-base`,
following the convention every academic extension now uses: one set per
component, plus one aggregate named after the package.

Impact
======

A site package that imported the old file by path fails to resolve it.
:typoscript:`@import` of a missing file is silent, so this shows up as an
unlabelled content element group in the new content element wizard rather than
as an error message.

Nothing else changes. The set :yaml:`fgtclb/academic-base-ctype-group` keeps its
name, the group label keeps being delivered to every installation without any
configuration, and the TCA registration of the group did not move.

What did change alongside the path is the :yaml:`label` of that set, from
`Academic Base - CType Group` to `Academic Base: Content element group`. It is
what the backend shows in the list of available sets; nothing references a set
by its label, so this needs no action.

Affected Installations
======================

Installations that import the page TSconfig file of this extension from an own
site package or from own page TSconfig.

Migration
=========

Adjust every :typoscript:`@import`:

..  list-table::
    :header-rows: 1

    *   -   Old path
        -   New path
    *   -   `EXT:academic_base/Configuration/TSconfig/Wizards/NewContentElement.tsconfig`
        -   `EXT:academic_base/Configuration/TSconfig/CTypeGroup/page.tsconfig`
    *   -   `EXT:academic_base/Configuration/TSconfig/Wizards/*.tsconfig`
        -   `EXT:academic_base/Configuration/TSconfig/Full/page.tsconfig`

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
