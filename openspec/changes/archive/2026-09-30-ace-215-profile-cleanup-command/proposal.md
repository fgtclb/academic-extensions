## Why

The backport of the `main` change of the same name, ACE-215, archived there as
`openspec/changes/archive/2026-09-30-ace-215-profile-cleanup-command`.

When a frontend user is disabled or deleted, its profile stays online.
`academic:updateprofiles` deliberately never changes visibility. Two projects
running 2.x wrote the same `academic:cleanupprofiles` command independently.
One of them writes raw SQL, which bypasses history, cache clearing and
translations.

## What Changes

- New command `academic:cleanupprofiles` in `academic_persons`
  (`packages/fgtclb/academic-persons`).
- Only profiles the synchronisation manages are looked at: with an import
  identifier, or with a frontend user of the record type the synchronisation
  reads. A profile qualifies only when every frontend user linked to it is
  disabled, past its end time or deleted. Profiles excluded from
  synchronisation (`skip_sync`) and profiles with no linked frontend user are
  never touched.
- For profiles whose users are disabled or expired, `--disabled` chooses
  `hide` (default) or `keep`. For profiles whose users are all deleted,
  `--deleted` chooses `delete` (default), `hide` or `keep`.
- `--include-pids` and `--exclude-pids` choose the profiles looked at by the
  pages of their frontend users: one user on an included page takes a profile
  in, one on an excluded page leaves it alone. `--dry-run` lists what would
  change and writes nothing.
- All writes go through the DataHandler, so history, cache clearing and
  translations follow. A deleted or restored profile now also clears the
  cached list and detail pages, in the backend as well.
- Profiles are never shown again automatically.

The behaviour is identical on TYPO3 v12 and v13, and the same as on `main`.

Different from `main`: this branch has no DataHandler execution helper in
`academic_persons`, so the change adds a small one with the one method the
command needs. The profile table has no workspace columns here, and there is
no announcement of a DataHandler save, so the hidden profile is not
announced here.

## Capabilities

### New Capabilities

- `academic-persons/profile-cleanup`: which profiles the cleanup hides or
  deletes, what it never touches, and what a dry run reports.

### Modified Capabilities

None.

## Impact

- A new console command, schedulable like the other two.
- Deleting is destructive. The defaults and the dry run are named in the
  documentation, and deleted records stay restorable through the history.
- Depends on `ace-667-sync-lookups-ignore-time-window`, merged on this branch,
  so that the synchronisation and the cleanup agree on what "outside the time
  window" means.

## Non-goals

- Showing a profile again when its frontend user is re-enabled. Re-enabling
  stays manual. A marker column is a later, additive follow-up under ACE-229
  (see `design.md`).
- Moving profiles to an alumni folder.
- Cleaning up records of external imports that are not linked to frontend
  users.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`persons-data-07`). Implements ACE-215. Relates to ACE-229 and ACE-77.
