..  _breaking-frontend-assets-are-built:

=================================================
Breaking: The frontend assets are built and moved
=================================================

Description
===========

The stylesheet and the script of the study plan are now compiled from sources
in the repository rather than maintained as finished files. Both moved into a
:file:`frontend/` subdirectory:

..  code-block:: text

    EXT:academic_study_plan/Resources/Public/Css/academic-study-plan.css
    ->  EXT:academic_study_plan/Resources/Public/Css/frontend/academic-study-plan.css

    EXT:academic_study_plan/Resources/Public/JavaScript/academic-study-plan.js
    ->  EXT:academic_study_plan/Resources/Public/JavaScript/frontend/academic-study-plan.js

How they are loaded does **not** change. The TypoScript of the content element
still adds both through :typoscript:`page.includeCSS` and
:typoscript:`page.includeJSFooter`, and the compiled script is still a classic
script rather than an ES module. The TypoScript file that adds them moved in the
same release, see :ref:`breaking-site-sets-and-static-templates-restructured`.

Impact
======

An installation that uses the extension as shipped needs to do nothing: the
files are loaded by the extension itself, from the new location.

An installation that referenced the old path keeps pointing at a file that no
longer exists.

Affected Installations
======================

Installations that reference either file by path from their own TypoScript,
site package or template override.

Migration
=========

Add the :file:`frontend/` segment to the path:

..  code-block:: typoscript

    page.includeCSS.academicStudyPlan = EXT:academic_study_plan/Resources/Public/Css/frontend/academic-study-plan.css
    page.includeJSFooter.academicStudyPlan = EXT:academic_study_plan/Resources/Public/JavaScript/frontend/academic-study-plan.js

Nothing else has to change.

..  index:: Frontend, JavaScript, TypoScript, ext:academic_study_plan
