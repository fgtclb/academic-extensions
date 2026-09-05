..  _feature-configured-document-datepickers-and-image-cropper:

==========================================================
Feature: Configured document datepickers and image cropper
==========================================================

Description
===========

The ProfileEditing document modal now renders ``from``, ``to`` and ``year`` as
native date pickers whenever the matching section validator carries the
``date`` flag. The complete selected calendar date is persisted in the native
:sql:`DATE` fields used by the ``ProfileInformation`` model; no time value is
stored. The three date controls and the compact year-only checkbox each use
``col-12 col-md-3`` and share one responsive row on medium and larger
viewports. Their required state and marker are derived from the same
section-local validators: the shipped configuration requires ``year`` while
allowing ``from`` and ``to`` to remain empty. Changing these flags affects only
the frontend editor and its server-side request validation, never backend TCA.

The modal also exposes the persisted :guilabel:`Show year only` switch. It
changes the row, modal and public-profile presentation to four-digit years
without altering the stored month or day.

The image editor consumes ``special.image.renderType`` and
``special.image.settings.ratio``. A ``cropper`` render type with a valid ratio
activates the locally packaged CropperJS 2.2 module and uploads only the cropped
result. The module and its license are shipped with the extension; no CDN is
contacted. The crop selection itself cannot be moved. If the profile has no
persisted image, the placeholder remains visible and CropperJS stays inactive
until the user selects a real file, so the fallback image can never be cropped.

Every successful image upload also writes the composed profile name from
``title``, ``firstName``, ``middleName`` and ``lastName`` to the FAL
``alternative`` and ``title`` metadata and reference overlay.

Impact
======

Projects can enable fixed-ratio profile-image cropping in
:file:`EXT:academic_persons/Configuration/AcademicPersons/Settings.yaml`.
Existing non-cropper image configurations and
document fields without the ``date`` validator keep their previous controls.
The date fields require the matching ``academic_persons`` version that stores
``year``, ``year_start`` and ``year_end`` as native :sql:`DATE` values and adds
the :sql:`year_only` column. Run TYPO3's database schema analyzer after the
update.

..  index:: Configuration, Date, FAL, Frontend, Image, JavaScript
