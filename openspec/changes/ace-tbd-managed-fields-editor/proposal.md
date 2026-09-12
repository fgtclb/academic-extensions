## Why

In the frontend editor, read-only is either a whole section or a field on
every record. Projects that synchronise contacts from LDAP want the imported
e-mail address locked while an address the person added by hand stays
editable, as one project requires. The 2.x template overrides that did this
are all dead on 3.0, so today there is no way to get it.

## What Changes

- The frontend editor of `academic_persons_edit`
  (`packages/fgtclb/academic-persons-edit`) reads the managed fields declared
  for `academic_persons` (`packages/fgtclb/academic-persons`) by
  `ace-tbd-managed-fields-backend`.
- On a managed record (import identifier set, profile not excluded from the
  synchronisation) a managed field is rendered read-only with a
  "synchronised" marker, and a submitted value for it is ignored; the stored
  value is kept.
- A managed contract or contact row cannot be deleted. Its edit action is not
  offered when every editable field of the row is managed.
- Hiding, showing and sorting a managed row stay available: the
  synchronisation never changes visibility (ACE-235).
- Rows without an import identifier and every row of a profile excluded from
  the synchronisation keep the configured actions.

The behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-persons-edit/managed-fields-editing`: how the frontend editor
  presents and protects fields and rows owned by the synchronisation.

### Modified Capabilities

None.

## Impact

- `academic_persons_edit`: field descriptors of profile, documents and
  contacts, the per-row action list, the factories that apply submitted
  values, one template marker and its labels.
- The JSON endpoints answer 403 with the existing error codes for a refused
  delete or edit of a managed row.
- Functional tests of the editor, integrator documentation, 3.0 changelog.

## Non-goals

- The backend form (`ace-tbd-managed-fields-backend`).
- Profile information (vita, publications); it has no import identifier.
- Letting the owner unlock a managed field; excluding the profile from the
  synchronisation is the existing way.
- Backporting to branch `2`: the editor there is a different code base.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`persons-data-11`). Four of the six analysed projects carry their own code for
this today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-managed-fields-editor` when the issue is filed after implementation.

Relates to ACE-234 and ACE-279.
