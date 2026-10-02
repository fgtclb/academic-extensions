..  _feature-shared-form-partials:

=========================================================
Feature: Form partials and the decision after a form save
=========================================================

Description
===========

The generic pieces of the new job form of :guilabel:`EXT:academic_jobs` moved
to this extension, so a later frontend form extension builds on them instead
of copying them:

*   The partials below
    :file:`Resources/Private/Partials/Academic/Form/` render the fields of an
    Extbase form, a text field, a text area, a select, a checkbox, a date and
    an upload, with their label, required mark, validation state and help
    text, and one alert for a form that failed validation. They are described
    in :ref:`templates-form`.
*   The enum :php:`\FGTCLB\AcademicBase\Form\FlashMessageCreationMode` decides
    whether a save queues its confirmation as a flash message. It moved here
    from :guilabel:`EXT:academic_jobs`, whose name for it is deprecated.
*   An internal service reads the redirect page and that mode from the plugin
    settings, with the same settings and the same fallbacks the job form has
    always read.

Impact
======

Nothing changes for an installation. The new job form renders the same fields
from these partials, and a project override of its partials keeps applying.

..  index:: Fluid, Frontend, ext:academic_base
