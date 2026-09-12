## Why

When a frontend user is disabled or deleted, its profile stays online.
`academic:updateprofiles` deliberately never changes visibility. Two projects
wrote the same `academic:cleanupprofiles` command independently. One of them
writes raw SQL, which bypasses history, cache clearing and translations.

## What Changes

- New command `academic:cleanupprofiles` in `academic_persons`
  (`packages/fgtclb/academic-persons`).
- A profile qualifies only when every frontend user linked to it is
  disabled, past its end time or deleted. Profiles excluded from
  synchronisation (`skip_sync`) and profiles with no linked frontend user are
  never touched.
- For profiles whose users are disabled or expired, `--disabled` chooses
  `hide` (default) or `keep`. For profiles whose users are all deleted,
  `--deleted` chooses `delete` (default), `hide` or `keep`.
- `--include-pids` and `--exclude-pids` limit the frontend users by page,
  like the create and update commands. `--dry-run` lists what would change
  and writes nothing.
- All writes go through the DataHandler, so history, cache clearing and
  translations follow.
- Profiles are never shown again automatically.

The behaviour is identical on TYPO3 v13 and v14.

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
- Depends on `ace-tbd-sync-lookups-ignore-time-window`, so that the
  synchronisation and the cleanup agree on what "outside the time window"
  means.

## Non-goals

- Showing a profile again when its frontend user is re-enabled. Re-enabling
  stays manual; a marker column is a later, additive follow-up under ACE-229
  (see `design.md`).
- Moving profiles to an alumni folder.
- Cleaning up records of external imports that are not linked to frontend
  users.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`persons-data-07`). Three of the six analysed projects are concerned today:
two carry their own `academic:cleanupprofiles` command, and the third plans
the same. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-<slug>` when the issue is filed after implementation.

Implements ACE-215. Relates to ACE-229 and ACE-77.
