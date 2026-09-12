## Why

The only extension point of the frontend-user synchronisation is swapping the
whole profile factory. Four projects ship their own LDAP or fe_users
factories. Three of them query LDAP inside a shared service
and cache the results as instance state. Two have to create empty profiles
for users without data, because a factory must return a profile.

## What Changes

- Before mapping, the synchronisation announces the frontend-user data. A
  listener can:
  - add or change values, such as an LDAP room, which the configurable
    mapping can then use;
  - skip the user, so nothing is created or updated.
- After mapping, the synchronisation announces the mapped profile together
  with the source data. Listeners can apply value maps such as gender, a
  visibility window or initials.
- A custom factory may decline to create a profile. The command then creates
  nothing for that user and continues with the next one.
- Existing factories keep working unchanged.

Only `academic_persons` (`packages/fgtclb/academic-persons`) is affected. The
behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `academic-persons/frontend-user-profile-sync`: adds requirements for
  enriching or skipping frontend-user data and for adjusting the mapped
  profile.

## Impact

- Two new PSR-14 events in `academic_persons`.
- The protected factory method that builds a profile may return nothing.
  Subclasses declaring a non-nullable return type stay compatible.
- `academic:createprofiles` and `academic:updateprofiles` honour a skip.
- Uses the mapping of `ace-tbd-settings-driven-fe-user-mapping` for added
  values. The skip and the nullable creation work without it.

## Non-goals

- An upstream LDAP integration.
- Replacing the factory swap, which stays available.
- A backport to branch `2`.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`persons-data-06`). Three of the six analysed projects carry their own code
for this today: LDAP factories with instance caches, request faking and empty
profiles. A fourth replaces its factory as well. No YouTrack issue is filed
yet; the change is renamed to `ace-<NNN>-<slug>` when the issue is filed
after implementation.
