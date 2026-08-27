..  index:: Configuration; Validation
..  _configuration-validations:

============================
Editing validation ownership
============================

:guilabel:`academic_persons` owns the domain model, database schema and stable
base TCA. It does not declare frontend-editing validators in
:file:`Configuration/AcademicPersons/Settings.yaml` any more.

When :guilabel:`academic_persons_edit` is installed, its independent
:file:`Configuration/AcademicsPersonsEdit/Settings.yaml` supplies the
section-local validation metadata for frontend editing. It does not read or
modify the base TCA. Backend FormEngine behavior therefore remains identical
whether the edit extension is installed or whether a frontend validator flag
is added or removed.

The normalized frontend rules drive:

*   Fluid and JSON input metadata, including required markers;
*   server-side Extbase validators; and
*   the allow-list used by partial inline updates.

The native date-only TCA and SQL storage remain independently defined in this
domain extension for TYPO3 13 and TYPO3 14.

There is no fallback from one edit section to another. In particular,
``documentSections.cooperation.validators.year`` cannot affect a different
record type.

See the :guilabel:`academic_persons_edit` configuration manual for the complete
schema, validator flags, aliases and override rules. The public
:yaml:`profile.structure` and :yaml:`profile.details` settings documented in
:ref:`configuration-sections` never participate in editing validation.
