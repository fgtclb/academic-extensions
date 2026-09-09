.. _important-job-dates-use-native-controls-and-the-site-locale:

============================================================
Important: Job dates use native controls and the site locale
============================================================

Description
===========

The three date fields of the :guilabel:`New job form` plugin —
:guilabel:`Employment start date`, :guilabel:`Start time` and
:guilabel:`End time` — are rendered as native :html:`<input type="date">`
controls. Such a control accepts an ISO ``yyyy-mm-dd`` value and nothing else:
a browser discards any other notation silently, with no parse error and no hint
in the markup. The form prefilled them with the German ``d.m.Y`` notation
instead, so a date a job already carried reached the visitor as an empty
control, and submitting the untouched form cleared the field.

The controls are now prefilled with ``Y-m-d``, which is the format
:php:`JobController::initializeCreateAction()` configures the
:php:`DateTimeConverter` with — the two ends of the round trip now agree.

The attribute :html:`data-render="datepicker"` the controls carried has been
removed. Nothing in this extension read it: there is no JavaScript module, no
stylesheet and no template that looks for it, and a native date control brings
its own picker.

The job list and the job detail view formatted every date as ``d.m.Y`` as well,
which is the German notation for every visitor of every language. Both now
render a date for the locale of the matched site language, through the new
:php:`\FGTCLB\AcademicBase\ViewHelpers\Format\LocalizedDateViewHelper`:

..  code-block:: html

    <b:format.localizedDate date="{job.employmentStartDate}"/>

The same record therefore reads ``01.10.2026`` under ``de-DE`` and
``Oct 1, 2026`` under ``en-US``.

One further correction was needed to render a form that carries a job at all:
the partial :file:`Partials/Job/Forms/Errors.html` passed the form object where
:html:`<f:form.validationResults>` expects the name of the form object. The
partial argument is therefore called ``objectName`` now and takes the string
``job``, which :file:`Templates/Job/New.html` passes.

Impact
======

Visitors read job dates in the notation of the language they browse the site
in, and an integrator who binds an existing job to the new job form — through
:php:`\FGTCLB\AcademicJobs\Event\ModifyJobControllerNewActionViewEvent` — gets
its dates offered for editing instead of empty controls.

Installations that override :file:`Partials/Job/Forms/DateTime.html`,
:file:`Partials/Job/Item.html`, :file:`Partials/Job/Information.html`,
:file:`Partials/Job/Forms/Errors.html` or :file:`Templates/Job/New.html` keep
their own copy and see none of this until they adopt the changes.

Stored records are untouched.

Affected Installations
======================

Every installation rendering the :guilabel:`New job form`, :guilabel:`Job list`
or :guilabel:`Job detail` plugin.

.. index:: Frontend, Fluid, ext:academic_jobs
