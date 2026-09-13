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

The tables now point at the identifiers
:php:`tx-academicstudyplan-record-category`,
:php:`tx-academicstudyplan-record-module` and
:php:`tx-academicstudyplan-record-semester` through
:php:`ctrl.typeicon_classes`, registered with
:php:`\FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider`
- see :ref:`breaking-study-plan-icons-replaced-by-font-awesome`.

The three files were three-colour illustrations. They are replaced by
single-colour Font Awesome Free drawings, which is what following the text
colour means.

Impact
======

The category, module and semester record icons take the text colour of the
backend, so they stay legible in a dark colour scheme - and they no longer
carry an orange, teal or grey of their own.

Affected Installations
======================

Every installation of this extension.

.. index:: Backend, TCA, ext:academic_study_plan
