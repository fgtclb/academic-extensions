..  _breaking-site-sets-and-static-templates-restructured:

===============================================================
Breaking: Site sets and static templates have been restructured
===============================================================

Description
===========

This extension already shipped both delivery mechanisms, and it already hid its
content element by default — it is the only academic extension that did. What it
shipped them through were four one-line :typoscript:`@import` files around three
real ones:

*   :file:`Configuration/TypoScript/Default/setup.typoscript` and
    :file:`Configuration/Sets/AcademicStudyPlan/setup.typoscript` both imported
    :file:`Configuration/TypoScript/Includes/ContentElement.typoscript` and
    :file:`Configuration/TypoScript/Includes/Page.typoscript`.
*   :file:`Configuration/TsConfig/Default.tsconfig` and
    :file:`Configuration/Sets/AcademicStudyPlan/page.tsconfig` both imported
    :file:`Configuration/TsConfig/Includes/academic-study-plan.tsconfig`.

The indirection is gone. Both mechanisms now read one physical copy of every
file, and both of them deliver the extension per component:

*   :file:`Configuration/TypoScript/ContentElement/` holds the TypoScript of the
    :guilabel:`Academic Study Plan` content element — the content object, the
    Fluid root paths and the two frontend assets — and is what the static
    template registers *and* what the set points its :yaml:`typoscript` key at.
*   :file:`Configuration/TSconfig/ContentElement/page.tsconfig` holds its page
    TSconfig and is what the page field :guilabel:`Page TSconfig` offers *and*
    what the set points its :yaml:`pagets` key at.
*   :file:`Configuration/TypoScript/Full/` and
    :file:`Configuration/TSconfig/Full/page.tsconfig` are the aggregates for
    installations that do not use site sets — which is every installation on
    TYPO3 v12, where site sets do not exist.

The directory holding the page TSconfig was renamed from
:file:`Configuration/TsConfig` to :file:`Configuration/TSconfig` in the same
release, and that directory is part of a value stored in page records — see
:ref:`Important: The page TSconfig directory is now spelled TSconfig
<important-1787141400>`.

Three things changed beyond the paths:

*   The three Fluid root paths of the content element are **constants** now,
    :typoscript:`plugin.tx_academicstudyplan.view.templateRootPath`,
    :typoscript:`…partialRootPath` and :typoscript:`…layoutRootPath`. They were
    assigned in the setup directly and could only be changed by overriding the
    content element object. The shipped values are unchanged, and the new
    :file:`constants.typoscript` is delivered by both mechanisms together with
    the setup.
*   The new content element wizard entry moved out of the always-included
    :file:`Configuration/page.tsconfig` into the page TSconfig of the component,
    together with the :typoscript:`show` key that TYPO3 v12 needs to offer the
    element in the wizard at all — so both arrive with the component instead of
    with every installation. The :typoscript:`[typo3.branch == "12.4"]` condition
    around them is gone: the entry is registered on TYPO3 v13 as well now, where
    it changes nothing, because the wizard is built from TCA there and the
    entry carries the same key.
*   The published set :yaml:`fgtclb/academic-study-plan-default` is an alias
    without payload now. It depends on the new aggregate
    :yaml:`fgtclb/academic-study-plan`, which depends on the component set
    :yaml:`fgtclb/academic-study-plan-content-element`.

Impact
======

A :sql:`sys_template` record that selected the old static template keeps its
stored value, and that value now points at a folder holding no
:file:`constants.typoscript` and no :file:`setup.typoscript`. It is not an
error — the frontend simply loses the content element configuration, and the
element renders as an empty content element without its stylesheet and script.

A page record that selected the old page TSconfig file keeps its stored value
too, and that value now points at a file that does not exist. An unresolved page
TSconfig include is silent, so the content element stops being selectable on
that page tree without any message.

A site package that imported one of the shipped files by path fails to resolve
it. :typoscript:`@import` of a missing file is silent, so this also shows up as
missing configuration rather than as an error message.

..  warning::

    Do not open an existing :guilabel:`Academic Study Plan` record in the backend
    form on a page that does not include that page TSconfig. An item removed
    through :typoscript:`TCEFORM.tt_content.CType.removeItems` is excluded from
    the :guilabel:`[ invalid value ]` fallback TYPO3 otherwise adds for a stored
    value it does not know, and the stored value is dropped from the form data
    as well. The field :guilabel:`Type` therefore comes up with nothing
    selected, and **saving the record writes whatever the browser preselected
    into** :sql:`CType` — the record silently becomes another content element.
    The frontend keeps rendering it correctly until that happens.

    Include the page TSconfig of the component on every page tree that holds
    such records, and do it before editing them. This is what the old page
    TSconfig file did, so an installation that stops resolving it is exposed to
    exactly this.

Affected Installations
======================

Installations that select the static template or the page TSconfig file of this
extension, that depend on its site set, or that import one of the shipped files
from an own site package.

Migration
=========

Replace the static template entry in the :sql:`sys_template` record:

..  list-table::
    :header-rows: 1

    *   -   Old entry
        -   New entry
    *   -   :guilabel:`Academic StudyPlan (Default) (academic_study_plan)`,
            stored as `EXT:academic_study_plan/Configuration/TypoScript/Default`
        -   :guilabel:`Academic Study Plan: All components (academic_study_plan)`,
            stored as
            `EXT:academic_study_plan/Configuration/TypoScript/Full` — or
            :guilabel:`Academic Study Plan: Content element (academic_study_plan)`,
            stored as
            `EXT:academic_study_plan/Configuration/TypoScript/ContentElement`

Replace the page TSconfig entry in the page record of the site root, tab
:guilabel:`Resources`, field :guilabel:`Page TSconfig`:

..  list-table::
    :header-rows: 1

    *   -   Old entry
        -   New entry
    *   -   :guilabel:`Academic StudyPlan (Default) (academic_study_plan)`,
            stored as
            `EXT:academic_study_plan/Configuration/TsConfig/Default.tsconfig`
        -   :guilabel:`Academic Study Plan: All components (academic_study_plan)`,
            stored as
            `EXT:academic_study_plan/Configuration/TSconfig/Full/page.tsconfig` —
            or
            :guilabel:`Academic Study Plan: Content element (academic_study_plan)`,
            stored as
            `EXT:academic_study_plan/Configuration/TSconfig/ContentElement/page.tsconfig`

On TYPO3 v12 this is the only way to get the content element back — site sets
arrived in TYPO3 v13.1 and the set files of this extension are never read on v12.

Sites on TYPO3 v13 that use the site set instead need no migration — but they
must not use both mechanisms at once, see the :guilabel:`Configuration` chapter.

Adjust every :typoscript:`@import` in an own site package:

..  list-table::
    :header-rows: 1

    *   -   Old path
        -   New path
    *   -   `EXT:academic_study_plan/Configuration/TypoScript/Default/setup.typoscript`
        -   `EXT:academic_study_plan/Configuration/TypoScript/ContentElement/setup.typoscript`
    *   -   `EXT:academic_study_plan/Configuration/TypoScript/Includes/ContentElement.typoscript`
        -   `EXT:academic_study_plan/Configuration/TypoScript/ContentElement/setup.typoscript`,
            together with
            `EXT:academic_study_plan/Configuration/TypoScript/ContentElement/constants.typoscript`
            in the constants of the same template
    *   -   `EXT:academic_study_plan/Configuration/TypoScript/Includes/Page.typoscript`
        -   `EXT:academic_study_plan/Configuration/TypoScript/ContentElement/setup.typoscript`,
            which now carries the two frontend assets as well
    *   -   `EXT:academic_study_plan/Configuration/TsConfig/Default.tsconfig`
        -   `EXT:academic_study_plan/Configuration/TSconfig/ContentElement/page.tsconfig`
    *   -   `EXT:academic_study_plan/Configuration/TsConfig/Includes/academic-study-plan.tsconfig`
        -   `EXT:academic_study_plan/Configuration/TSconfig/ContentElement/page.tsconfig`

A site configuration on TYPO3 v13 may name the new sets instead of the published
one:

..  list-table::
    :header-rows: 1

    *   -   Set
        -   Delivers
    *   -   `fgtclb/academic-study-plan-default`
        -   Unchanged in name and in what it delivers, now an alias of the
            aggregate below.
    *   -   `fgtclb/academic-study-plan`
        -   Everything this extension ships.
    *   -   `fgtclb/academic-study-plan-content-element`
        -   The :guilabel:`Academic Study Plan` content element only.

..  index:: TypoScript, TSConfig, Backend, ext:academic_study_plan
