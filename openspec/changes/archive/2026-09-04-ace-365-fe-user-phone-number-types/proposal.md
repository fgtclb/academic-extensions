## Why

The `academic_persons` extension (`packages/fgtclb/academic-persons`) currently imports the `fe_users.telephone` and `fe_users.fax` values with the types `phone` and `fax`, although neither type is selectable in the default installation. Existing records therefore carry values the backend cannot resolve, and telephone-only frontend-user data can also lose its contract because the update guard reads the non-existent `phone` field.

## What Changes

- Add separate `profile.fe_users.telephoneNumberType` and `profile.fe_users.faxNumberType` extension options, both defaulting to `business`.
- Validate configured import types against the installation's selectable phone-number types and use the valid undefined value `''` for an unknown configuration.
- Give imported telephone numbers the stable identifier `telephone:fe_users:<uid>` while keeping `fax:fe_users:<uid>` for fax numbers.
- Reuse and normalize legacy `phone:fe_users:<uid>` records during synchronisation so a skipped upgrade wizard does not create duplicates.
- Preserve already valid types and replace only the legacy invalid `phone` and `fax` values.
- Add an idempotent upgrade wizard for existing imported records.
- Correct the contract-data guard to read `fe_users.telephone`.
- Document the new feature and upgrade path.

The behaviour is identical on supported TYPO3 v13 and v14 installations.

## Capabilities

### New Capabilities

- `academic-persons/frontend-user-profile-sync`: Defines how frontend-user telephone and fax data is imported, validated, identified, updated, and migrated into profile contracts.

### Modified Capabilities

None.

## Impact

- Extension configuration and labels in `academic_persons`.
- Profile creation and update through `academic:createprofiles` and `academic:updateprofiles`.
- Existing imported phone-number records through a TYPO3 upgrade wizard.
- Functional and unit test fixtures for profile synchronisation and migration.
- Integrator documentation and the 3.0 feature changelog.
- No database schema or dependency changes.

## Non-goals

- Configuring or migrating physical-address or email-address types.
- Removing fax numbers from contracts or changing their presentation.
- Automatically merging or deleting pre-existing duplicate telephone records.
- Backporting the change to branch `2`.
