## Why

This backports ACE-365 from `main`, where it is archived as
`openspec/changes/archive/2026-09-04-ace-365-fe-user-phone-number-types/`. It is tracked on this branch as ACE-545 and re-derived here rather than cherry-picked, because this branch supports TYPO3 v12 and v13 and PHP 8.1.

The `academic_persons` extension (`packages/fgtclb/academic-persons`) currently imports the `fe_users.telephone` and `fe_users.fax` values with the types `phone` and `fax`, although neither type is selectable in the default installation. Existing records therefore carry values the backend cannot resolve, and telephone-only frontend-user data can also lose its contract because the update guard reads the non-existent `phone` field.

## What Changes

- Add separate `profile.feuser.telephoneNumberType` and `profile.feuser.faxNumberType` extension options, both defaulting to `business`.
- Validate configured import types against the installation's selectable phone-number types and use the valid undefined value `''` for an unknown configuration.
- Give imported telephone numbers the stable identifier `telephone:fe_users:<uid>` while keeping `fax:fe_users:<uid>` for fax numbers.
- Reuse and normalize legacy `phone:fe_users:<uid>` records during synchronisation, so existing records are repaired where they are found rather than migrated in bulk.
- Preserve already valid types and replace only the legacy invalid `phone` and `fax` values.
- Correct the contract-data guard to read `fe_users.telephone`.
- Order the imported phone-number query by `uid` beside `sorting`, so the record match is deterministic across a tie.
- Document the new feature and what a synchronisation run does not reach.

The behaviour is identical on supported TYPO3 v12 and v13 installations.

## Capabilities

### New Capabilities

- `academic-persons/frontend-user-profile-sync`: Defines how frontend-user telephone and fax data is imported, validated, identified, updated, and migrated into profile contracts.

### Modified Capabilities

None.

## Impact

- Extension configuration and labels in `academic_persons`.
- Profile creation and update through `academic:createprofiles` and `academic:updateprofiles`.
- Existing imported phone-number records, repaired by the next synchronisation run of their frontend user.
- Functional and unit test fixtures for profile synchronisation and migration.
- Integrator documentation and the 3.0 feature changelog.
- No database schema or dependency changes.

## Non-goals

- Configuring or migrating physical-address or email-address types.
- Removing fax numbers from contracts or changing their presentation.
- Automatically merging or deleting pre-existing duplicate telephone records.
- Backporting the change to branch `2`.
