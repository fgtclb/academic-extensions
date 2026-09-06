..  _important-study-plan-record-icons-follow-the-colour-scheme:

========================================================
Important: Record icons follow the backend colour scheme
========================================================

Description
===========

The three tables of this extension pointed at their icon files directly,
through the TCA :php:`ctrl.iconfile` option. That bypasses the icon registry
altogether: the file gets the core provider, which renders the default markup as
an :html:`<img>` tag. An image is opaque to CSS, so the icons kept the colours
of their files whatever the backend colour scheme said.

The tables now point at the identifiers :php:`academic-study-plan-category`,
:php:`academic-study-plan-module` and :php:`academic-study-plan-semester`
through :php:`ctrl.typeicon_classes`. Those identifiers were already registered
in :file:`Configuration/Icons.php` and unused; they now carry
:php:`\FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider`.

The three files were three-colour illustrations and are redrawn in a single
colour, which is what following the text colour means. The shapes are kept: the
coloured body becomes an outline, the lighter shapes on it become solid. The
module and the semester icon differed in colour and in size only, so they now
look alike apart from their proportions.

Impact
======

The category, module and semester record icons take the text colour of the
backend, so they stay legible in a dark colour scheme - and they no longer
carry an orange, teal or grey of their own.

Affected Installations
======================

Every installation of this extension.

.. index:: Backend, TCA, ext:academic_study_plan
