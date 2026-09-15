## Why

The frontend-user synchronisation keeps hidden profiles and disabled frontend
users up to date (ACE-242, 2.4). Profiles and frontend users outside their
start and end time are still invisible to both lookups. A profile whose end
time has passed, whose start date lies in the future or that is restricted to
a frontend user group is therefore never updated, and a frontend user outside
its window is not picked up at all. Installations that store a directory
visibility window in these fields lose the sync for exactly those people.

This backports the `main` change archived as
`openspec/changes/archive/2026-09-16-ace-667-sync-lookups-ignore-time-window`
(pull request #635), re-derived from the backport analysis.

## What Changes

- `academic:updateprofiles` also finds profiles that are hidden by their start
  time, end time or frontend user group, and updates them like any other
  profile. Only the visibility fields the profile table declares in the
  installation are ignored.
- `academic:createprofiles` and `academic:updateprofiles` also select
  frontend users outside their start and end time. Deleted records stay
  excluded.
- The synchronisation still never writes visibility: `hidden`, `starttime`,
  `endtime` and `fe_group` of a profile stay as they are.

Only `academic_persons` (`packages/fgtclb/academic-persons`) is affected. The
behaviour is identical on TYPO3 v12 and v13.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `academic-persons/frontend-user-profile-sync`: adds the requirement that
  the synchronisation ignores the visibility window of profiles and frontend
  users, and never changes it.

## Impact

- The profile lookup of the update run and both frontend-user queries of the
  create and update runs. A behaviour change without an API change, with an
  `Important-` entry in the 2.4 changelog.

## Non-goals

- Hiding or deleting profiles whose frontend user was disabled or removed.
- Mapping a visibility window from frontend-user data onto the profile.
- Changing what the public plugins show.
- The per-profile update event of `main` (ACE-490), which this branch does
  not have.

## Source

ACE-667, one issue for `main` and this backport. Relates to ACE-242, whose
hidden-profile part is already on this branch.
