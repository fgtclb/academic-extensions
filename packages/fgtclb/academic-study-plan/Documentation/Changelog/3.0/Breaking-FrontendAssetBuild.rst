..  _breaking-frontend-assets-are-es-modules:

=====================================================================
Breaking: The study plan assets are loaded by the template, as module
=====================================================================

Description
===========

Version 2.4 built the stylesheet and the script of the study plan from sources
and moved them into a :file:`frontend/` subdirectory, see
:ref:`breaking-frontend-assets-are-built`. The TypoScript of the content element
still added both to every page of the site, through :typoscript:`page.includeCSS`
and :typoscript:`page.includeJSFooter`, and the script was a classic script.

The script is an **ES module** now. It is registered in
:file:`Configuration/JavaScriptModules.php` and addressed by the bare specifier
:code:`@fgtclb/academic-study-plan/frontend/academic-study-plan.js`. TypoScript
has no counterpart for loading an ES module, so the template of the content
element loads both assets now, and only pages that render a study plan request
them. The TypoScript of the extension, the deprecated path of version 2.3
included, no longer adds them to the page. Both can be switched off, see
:ref:`feature-study-plan-assets-can-be-switched-off`.

Impact
======

An installation that uses the shipped template needs to do nothing.

An installation that overrides the template, or that adds the files with its own
:typoscript:`includeCSS` or :typoscript:`includeJSFooter` entries, no longer
gets them from the extension. A classic script tag of its own does not execute
the module.

Affected Installations
======================

Installations that override :file:`AcademicStudyPlan.html`, or that reference
either asset path from their own TypoScript or site package.

Migration
=========

Drop own :typoscript:`includeCSS` or :typoscript:`includeJSFooter` entries for
these files. In an overridden template, load them the way the shipped one does:

..  code-block:: html

    <f:asset.css identifier="academicStudyPlan" href="EXT:academic_study_plan/Resources/Public/Css/frontend/academic-study-plan.css" />
    <f:asset.module identifier="@fgtclb/academic-study-plan/frontend/academic-study-plan.js" />

:html:`<f:asset.script>` cannot be used for the script any more, because a
classic script tag does not execute an ES module.

..  index:: Frontend, JavaScript, TypoScript, ext:academic_study_plan
