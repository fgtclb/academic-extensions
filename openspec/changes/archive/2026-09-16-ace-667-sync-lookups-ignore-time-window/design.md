## Context

- `ProfileRepository::includeHiddenRecords()`
  (`academic-persons/Classes/Domain/Repository/ProfileRepository.php:171-175`)
  ignores only the `disabled` enable field. The profile TCA declares
  `disabled`, `starttime`, `endtime` and `fe_group`
  (`Configuration/TCA/tx_academicpersons_domain_model_profile.php:31-36`).
  `findByFrontendUser($uid, true)` therefore misses time-windowed profiles,
  and `updateProfileForUser()` returns at
  `Classes/Profile/AbstractProfileFactory.php:149-151`.
- `FrontendUserProvider` removes only `HiddenRestriction` in
  `getUsersWithoutProfileResult()` (`:50`) and `getUsersWithProfileResult()`
  (`:120`).
- The create query does not duplicate time-windowed profiles, because the MM
  left join (`:52-66`) ignores profile visibility.
- The contact sub-records (address, e-mail, phone) have only `disabled` as an
  enable column. Their existing "including hidden" lookups already cover them.

Branch `2` has the same code (`ProfileRepository.php:157-158`,
`FrontendUserProvider.php:50,120`).

## Goals / Non-Goals

**Goals:**

- Name the ignored enable fields explicitly, so an enable column added later
  is a deliberate decision.

**Non-Goals:**

- A new public finder parameter or signature change.

## Decisions

### A sync-only helper, the shared helper stays as it is

`includeHiddenRecords()` is not sync-only. It also serves three public paths:

- the `showHiddenRecords` demand listing (`ProfileRepository.php:145-148`);
- `findByUids()` for selected profiles (`:281`);
- `findByUidIncludingHidden()` for the public detail view
  (`Classes/Controller/ProfileController.php:232`).

The candidate proposed renaming that helper and widening it. That would start
showing time-windowed profiles in all three places, so it is rejected.

Instead, add a private `includeRestrictedRecordsForSynchronization()` that
passes `disabled`, `starttime`, `endtime` and `fe_group`, as far as the
profile table declares them (see the decision below), to
`setEnableFieldsToBeIgnored()`. Use it in `findByFrontendUser()` when
`$showHidden` is `true`. The only caller passing `true` is
`AbstractProfileFactory.php:148`. The editor's list action
(`academic-persons-edit/Classes/Controller/ProfileController.php:353`) uses the
default `false` and is unaffected. The docblock of `findByFrontendUser()` is
updated to say that `$showHidden` means "every enable field, for the
synchronisation".

Rejected: `setIgnoreEnableFields(true)` without a list, which is one
project's patch. With an empty list Extbase ignores every enable field,
including ones added later. Also rejected: a new finder or a new `bool`
parameter, which widens public API when the existing parameter already has
exactly one, sync-only caller.

### Remove the start and end time restrictions in both provider queries

Remove `StartTimeRestriction` and `EndTimeRestriction` next to the existing
`HiddenRestriction` removal. `fe_users` has no `fe_group` enable column, and
`DeletedRestriction` stays.

### Decided: ignore all four enable fields, as far as the table defines them

The synchronisation lookup ignores `disabled`, `starttime`, `endtime` and
`fe_group` of the profile. A command-line run has no frontend groups, so with
`fe_group` still active it would miss every group-restricted profile and
return early, which is the defect class this change fixes. The project that
patches the lookups today already ignores every enable field of the profile.

The list passed to `setEnableFieldsToBeIgnored()` is not hard-coded: the
helper intersects the four candidates with the enable fields the profile
table really declares, read from `TcaSchemaFactory` through
`hasCapability()` / `getCapability()` of `TcaSchemaCapability`
`RestrictionDisabledField`, `RestrictionStartTime`, `RestrictionEndTime` and
`RestrictionUserGroup` (present on 13.4.35; checked on v14 in the task). An
installation that removes one of them from the profile TCA is therefore
handled without naming a field the table does not have, and an enable field
added later outside the four stays active.

### No core version split

Both restriction classes, the query settings API and the TCA schema
capabilities used above are the same on v13 and v14.

## Risks / Trade-offs

- [`fe_group`-restricted profiles are now synchronised and announced] → This
  is intended. It is named in the changelog.
- [A later caller passes `$showHidden = true` for display purposes] → The
  docblock names the parameter's sync meaning, and a functional test pins
  the public listing and detail behaviour.

## Open Questions

None.
