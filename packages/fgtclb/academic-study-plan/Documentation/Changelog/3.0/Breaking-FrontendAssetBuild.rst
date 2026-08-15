..  _breaking-frontend-assets-are-es-modules:

==========================================================
Breaking: Frontend assets are built and loaded differently
==========================================================

Description
===========

The stylesheet and the script of the study plan are now compiled from sources
in the repository rather than maintained as finished files, and both changed
where they live and how they are loaded.

Their paths gained a :file:`frontend/` segment:

..  code-block:: text

    EXT:academic_study_plan/Resources/Public/Css/academic-study-plan.css
    ->  EXT:academic_study_plan/Resources/Public/Css/frontend/academic-study-plan.css

    EXT:academic_study_plan/Resources/Public/JavaScript/academic-study-plan.js
    ->  EXT:academic_study_plan/Resources/Public/JavaScript/frontend/academic-study-plan.js

The script is now an **ES module**. It is registered in
:file:`Configuration/JavaScriptModules.php` and addressed by the bare specifier
:code:`@fgtclb/academic-study-plan/frontend/academic-study-plan.js`.

The TypoScript include :file:`Configuration/TypoScript/Includes/Page.typoscript`
has been **removed**. It added both files to every page of the site through
:typoscript:`page.includeCSS` and :typoscript:`page.includeJSFooter`. There is
no TypoScript counterpart for loading an ES module, so both assets are now
loaded by the plugin template instead — which also means they are only
requested on pages that actually render a study plan.

Impact
======

An installation that uses the extension as shipped needs to do nothing: the
template loads what it needs.

An installation that referenced either file by path, or that imported the
removed TypoScript include, no longer gets the assets.

Affected installations
======================

Installations that override :file:`AcademicStudyPlan.html`, that import
:file:`Includes/Page.typoscript` from their own TypoScript, or that reference
either asset path from a site package.

Migration
=========

Remove any import of the deleted TypoScript include and drop your own
:typoscript:`includeCSS` or :typoscript:`includeJSFooter` entries for these
files.

In an overridden template, load them the way the shipped one does:

..  code-block:: html

    <f:asset.css identifier="academicStudyPlan" href="EXT:academic_study_plan/Resources/Public/Css/frontend/academic-study-plan.css" />
    <f:asset.module identifier="@fgtclb/academic-study-plan/frontend/academic-study-plan.js" />

:html:`<f:asset.script>` cannot be used for the script any more — a classic
script tag does not execute an ES module.
