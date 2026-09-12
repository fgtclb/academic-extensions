## Why

ACE-242 made the frontend-user synchronisation keep hidden profiles and
disabled frontend users up to date. Profiles and frontend users outside their
start and end time are still invisible to both lookups. A profile whose end
time has passed, or whose start date lies in the future, is therefore never
updated. A frontend user outside its window is not picked up at all.
Installations that store an LDAP visibility window in these fields lose the
sync for exactly those people.

## What Changes

- `academic:updateprofiles` also finds profiles that are hidden only by their
  start time, end time or frontend user group. It updates and announces them
  like any other profile. Only the visibility fields the profile table
  declares in the installation are ignored.
- `academic:createprofiles` and `academic:updateprofiles` also select
  frontend users outside their start and end time. Deleted records stay
  excluded.
- The synchronisation still never writes visibility: `hidden`, `starttime`,
  `endtime` and `fe_group` of a profile stay as they are.

Only `academic_persons` (`packages/fgtclb/academic-persons`) is affected. The
behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `academic-persons/frontend-user-profile-sync`: adds the requirement that
  the synchronisation ignores the visibility window of profiles and frontend
  users, and never changes it.

## Impact

- The profile lookup of the update run and both frontend-user queries of the
  create and update runs.
- Listeners of the profile update event now also run for time-windowed
  profiles. This is a behaviour change without an API change and gets an
  `Important-` changelog entry.

## Non-goals

- Hiding or deleting profiles whose frontend user was disabled or removed.
  That is `ace-tbd-profile-cleanup-command`.
- Mapping a visibility window from frontend-user data onto the profile.
- Changing what the public plugins show.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`persons-data-02`). One of the six analysed projects carries its own code
for this today: two composer patches, one for the profile lookup and one for
the frontend-user provider. No YouTrack issue is filed yet; the change is
renamed to `ace-<NNN>-<slug>` when the issue is filed after implementation.

Relates to ACE-242.
