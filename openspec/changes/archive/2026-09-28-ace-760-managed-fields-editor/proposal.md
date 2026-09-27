## Why

In the frontend editor, read-only is either a whole section or a field on
every record. Projects that synchronise contacts from LDAP want the imported
e-mail address locked while an address the person added by hand stays
editable, as one project requires. The 2.x template overrides that did this
are all dead on 3.0, so today there is no way to get it.

Checking the editor for this turned up a defect in the lock it already has.
The browser sends every field of an open contract or contact when it saves,
the read-only ones included, and the server answers a submitted read-only
field with 422. A contract or contact with a `readonly` or `frontendreadonly`
field therefore cannot be saved from the browser at all, although the
validation settings promise that such a value is ignored. A per-record lock
built on the same answer would fail the same way, so the two are fixed
together.

## What Changes

- The frontend editor of `academic_persons_edit`
  (`packages/fgtclb/academic-persons-edit`) reads the managed fields declared
  for `academic_persons` (`packages/fgtclb/academic-persons`) by
  `ace-758-managed-fields-backend`.
- On a managed record (import identifier set, profile not excluded from the
  synchronisation) a managed field is rendered read-only with a
  "synchronised" marker.
- A submitted value for a locked field is ignored and the stored value is
  kept, while the other fields of the request are stored. This holds for a
  managed field and for a field locked with `readonly`, `frontendreadonly` or
  `disabled`, on the profile, contract and contact endpoints. Until now these
  endpoints answered 422. The single-purpose endpoints of the
  synchronisation switch and of the profile visibility keep refusing, because
  the one value they carry is the whole request.
- A read-only select or checkbox of a contract or contact is rendered
  disabled, since neither control knows a read-only state.
- A managed contract or contact row, one with at least one managed field,
  cannot be deleted. Its edit action is not offered when every editable
  field of the row is managed.
- Hiding, showing and sorting a managed row stay available: the
  synchronisation never changes visibility (ACE-235).
- Rows without an import identifier, every row of a profile excluded from
  the synchronisation, and every row of an installation that declares no
  managed field keep the configured actions.

The behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-persons-edit/managed-fields-editing`: how the frontend editor
  presents and protects fields and rows owned by the synchronisation, and
  what it does with a submitted value for any locked field.

### Modified Capabilities

None. `academic-persons/validation-flags` already requires a submitted value
of a `frontendreadonly` field to be ignored, which this change makes true.

## Impact

- `academic_persons`: the resolver of `ace-758-managed-fields-backend` also
  answers property names for a domain record.
- `academic_persons_edit`: field descriptors of profile, contracts and
  contacts, the per-row actions, the normalisation of submitted fields, the
  factories that apply submitted values, the field prototypes, one marker and
  its labels.
- The JSON endpoints answer 200 instead of 422 for a submitted locked value,
  and 403 with the existing error codes for a refused delete or edit of a
  managed row.
- Functional and JavaScript tests of the editor, integrator documentation,
  3.0 changelog.

## Non-goals

- The backend form (`ace-758-managed-fields-backend`).
- Profile information (vita, publications). It has no import identifier.
- Letting the owner unlock a managed field. Excluding the profile from the
  synchronisation is the existing way.
- Backporting to branch `2`: the editor there is a different code base.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`persons-data-11`). Four of the six analysed projects carry their own code for
this today. Filed as ACE-760.

Relates to ACE-234 and ACE-279.
