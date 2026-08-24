..  _feature-settings-driven-structured-profile-sections:

====================================================
Feature: Settings-driven structured profile sections
====================================================

Description
===========

The inline profile page renders contracts and all profile-information
collections below :guilabel:`About me` in the exact order of
:yaml:`documentSections`. ``ProfileDocumentSectionProvider`` now consumes the
typed section objects from :guilabel:`academic_persons` directly. It no longer
prepends a locally defined contract section or relies on the removed
``profileInformationTypes`` registry.

Every view-model entry carries the configured identifier, relation field,
record type, LLL label, read-only state, validation metadata and position.
Contracts and profile-information records remain strongly typed. Date
presentation is selected from the configured record type.

The current inline templates display these records without mutation controls.
They expose the configured read-only and validation metadata at the stable
section boundary for template integrations, while :guilabel:`Edit all` remains
limited to direct profile fields.

The InlineProfile plugin registers only ``InlineProfileController``. Legacy
contract, profile-information and contact controllers are not part of its
normal or non-cacheable action maps.

Impact
======

Template overrides must consume ``{documentSections}`` and should retain the
document ``data-section-*`` and record ``data-item-*`` attributes. Section order
and mapping now have one source of truth in
:file:`Configuration/AcademicPersons/Settings.yaml`.

..  index:: AJAX, Contracts, Fluid, Frontend, Profile information, NotScanned, ext:academic_persons_edit
