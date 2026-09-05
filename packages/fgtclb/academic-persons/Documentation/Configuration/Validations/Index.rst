..  index:: Configuration; Validation
..  _configuration-validations:

============================
Editing validation ownership
============================

:guilabel:`academic_persons` owns the domain model, database schema and stable
base TCA. The shared validation metadata is declared in
:file:`Configuration/AcademicPersons/Settings.yaml` and is consumed by both
the public/edit frontend code and the backend TCA configuration.

When :guilabel:`academic_persons_edit` is installed, it uses the same
normalized settings graph for its inline controls and server-side validation.
The domain extension continues to own the base TCA, while applying the shared
field state and required flags to the relevant backend fields.

The normalized frontend rules drive:

*   Fluid and JSON input metadata, including required markers;
*   server-side Extbase validators; and
*   the allow-list used by partial inline updates.

The native date-only TCA and SQL storage remain defined in this domain
extension for TYPO3 13 and TYPO3 14. Character limits remain
frontend/server-side metadata and do not change the database schema.

There is no fallback from one edit section to another. In particular,
``documentSections.cooperation.validators.year`` cannot affect a different
record type.

See the :guilabel:`academic_persons_edit` configuration manual for the complete
schema, validator flags, aliases and override rules. The :yaml:`profile`
section contains both :yaml:`structure`/:yaml:`details` for public rendering
and the editable field definitions used for validation.
