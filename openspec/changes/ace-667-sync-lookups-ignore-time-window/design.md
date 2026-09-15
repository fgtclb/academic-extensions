## Context

Backport analysis of the `main` change (ACE-667, #635) against this branch:

- `FrontendUserProvider` and the create command test are identical on both
  branches; the fixed files carry over unchanged.
- `ProfileRepository` differs only by the deterministic orderings of ACE-491,
  which this branch does not have; `includeHiddenRecords()` and
  `findByFrontendUser()` are the same.
- `AbstractProfileFactory::updateProfileForUser()` has neither the `skip_sync`
  guard nor the per-profile update event of ACE-490. Nothing of this change
  lives there, so the tests assert the written data only.
- There is no card plugin test on this branch. The display paths are guarded
  by repository tests in a frontend request instead.

## Decisions

### A sync-only helper, the shared helper stays as it is

As on `main`: `findByFrontendUser($uid, true)` uses a private
`includeRestrictedRecordsForSynchronization()`, and `includeHiddenRecords()`
keeps serving the "show hidden records" listing, `findByUids()` and
`findByUidIncludingHidden()` with the hidden flag only.

### The enable columns are read from the TCA

`main` reads them through `TcaSchemaFactory`, which does not exist on TYPO3
v12. The helper intersects `disabled`, `starttime`, `endtime` and `fe_group`
with the keys of `ctrl.enablecolumns` of the profile TCA instead. That is the
same array v12's `PageRepository::enableFields()` and v13's
`getDefaultConstraints()` evaluate, so no version switch is needed.

### Remove the start and end time restrictions in both provider queries

Unchanged from `main`. `StartTimeRestriction` and `EndTimeRestriction` exist on
v12.

### Frontend request in the repository test

Extbase honours the list of ignored enable fields only in a frontend request,
on v12 as on v13. The test request carries a `frontend.typoscript` attribute
with an empty setup array; the object is created without its constructor,
which differs between v12 and v13.

## Risks / Trade-offs

- [`fe_group`-restricted profiles are now synchronised] → Intended, named in
  the changelog.

## Open Questions

None.
