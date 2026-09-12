## Why

Only the fe_users synchronisation fills person records today. A project that
imports from another source (one project runs a complete HR system import of
several thousand lines of code) writes its own DataHandler pipeline. That pipeline
ignores `import_identifier` and `skip_sync` and never announces the profile
update, so translations and slugs go stale. `academic_persons_sync` ships no
logic that could help.

## What Changes

- `academic_persons` (`packages/fgtclb/academic-persons`) gains an import
  writer: import code hands over one person as a tree of plain data (profile,
  contracts, their e-mail addresses, phone numbers and physical addresses,
  each with an identifier and field values) and the writer stores it.
- Records are matched by import identifier; writing the same person twice
  yields one profile.
- A profile excluded from the synchronisation is left untouched.
- A new record gets every supplied field. An existing record gets only the
  supplied fields declared as managed (`ace-tbd-managed-fields-backend`), so
  local data survives.
- The visibility of existing records is never changed.
- A listener can change or veto each record write.
- A separate retire call hides or deletes the records of one source that are
  no longer supplied; manually created records and excluded profiles are
  never touched.
- Every write goes through DataHandler, so history, workspaces rules and the
  profile update announcement of `ace-tbd-backend-save-announces-profile-update`
  apply. The writer marks its runs as an import, so each person is announced
  once, in the same request, with the origin import.

The behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-persons/profile-import`: how an external source's persons are
  created, updated and retired.

### Modified Capabilities

None.

## Impact

- `academic_persons`: the writer, its data objects, a result object, one
  event, a retire policy.
- Depends on `ace-tbd-import-identifier-lookup`,
  `ace-tbd-managed-fields-backend` and
  `ace-tbd-backend-save-announces-profile-update`.
- Developer and integrator documentation, a 3.0 feature changelog.

## Non-goals

- Source adapters (LDAP, HR systems) or a mapping UI.
- Creating organisational units or function types; they are looked up by
  identifier.
- Profile information (vita, publications).
- Replacing the fe_users synchronisation.
- Backporting to branch `2`.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`persons-data-17`). One of the six analysed projects carries its own code for
this today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-profile-import-writer` when the issue is filed after implementation.

Relates to ACE-360, ACE-115, ACE-277, ACE-278 and ACE-246.

Relates to ACE-644.
