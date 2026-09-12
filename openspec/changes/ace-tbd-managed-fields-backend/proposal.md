## Why

Projects that fill profiles from LDAP or an external source want the
synchronised values locked in the backend, but only on the records the
synchronisation owns. Today the only lock is the global `readonly` flag of the
persons settings: it applies to every record of a table, so it also locks the
e-mail address an editor added by hand, and it leaks from the frontend editor
into TCA. Four projects work around it with TCEFORM resets or their own
FormEngine data providers, one of them with a defect that applies the field
lists of every table to every table.

## What Changes

- A new `managedFields` map in the persons settings graph of `academic_persons`
  (`packages/fgtclb/academic-persons`) lists, per record type (`profile`,
  `contracts`, `emailAddresses`, `phoneNumbers`, `physicalAddresses`), the
  fields the synchronisation owns. The default is empty, so nothing changes for
  an installation that does not configure it.
- A field is managed on a record that carries an import identifier and whose
  profile takes part in the synchronisation (not excluded by `skip_sync`).
- The backend record form renders a managed field read-only, with a short hint
  that it is maintained by the synchronisation and from which source.
- Records without an import identifier, and every record of a profile excluded
  from the synchronisation, stay fully editable.
- Only the list of the record's own type applies.

The behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-persons/managed-fields`: which fields of a synchronised person
  record are owned by the synchronisation, and how the backend form presents
  them.

### Modified Capabilities

None.

## Impact

- `academic_persons`: settings graph and its `Settings.yaml` documentation, one
  FormEngine data provider, one stateless resolver that later changes reuse
  (frontend editor, import writer), labels.
- No database schema change, no TCA change of existing columns.
- Integrator documentation and a 3.0 feature changelog entry.

## Non-goals

- The frontend editor. It is a change of its own
  (`ace-tbd-managed-fields-editor`) built on the same settings.
- Changing the global `readonly` flag or its leak into TCA; that is
  `ace-tbd-frontend-only-readonly-flag`.
- Enforcing the lock in DataHandler. An admin, an import or a script may still
  write a managed field; this is a presentation lock of the backend form.
- Translation records: they keep the behaviour core gives them.
- Backporting to branch `2`: the settings graph exists on 3.0 only.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`persons-data-10`). Four of the six analysed projects carry their own code for
this today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-managed-fields-backend` when the issue is filed after
implementation.

Relates to ACE-234 and ACE-279.
