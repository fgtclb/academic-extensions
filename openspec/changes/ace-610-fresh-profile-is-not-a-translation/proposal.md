## Why

A profile created from a frontend user reports itself as a translation until
it is loaded from the database again. The translation synchronisation of
`academic_persons_edit` starts from the default language record only and skips
a translation, so a newly created profile gets no translation in any allowed
language. That lasts until someone edits or re-announces the default-language
profile.

This backports the `main` change archived as
`openspec/changes/archive/2026-09-16-ace-610-fresh-profile-is-not-a-translation`
(pull request #619), re-derived from the backport analysis.

## What Changes

- A profile that was never loaded from the database, including one that was
  just created and persisted, no longer reports itself as a translation.
- Profiles created by `academic:createprofiles`, or on frontend user login,
  get their translations straight away, in every language configured in the
  `academic_persons_edit` option `profile.allowedLanguages`.
- The answer for profiles loaded from the database does not change, apart
  from a record kept in all languages (`sys_language_uid = -1`), which is no
  longer reported as a translation either.

The extensions involved are `academic_persons`
(`packages/fgtclb/academic-persons`), where the fix lives, and
`academic_persons_edit` (`packages/fgtclb/academic-persons-edit`), whose
translation synchronisation benefits. The behaviour is identical on TYPO3 v12
and v13.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `academic-persons/frontend-user-profile-sync`: adds the requirement that a
  profile created from a frontend user is treated as a default-language
  profile, so it is translated.

## Impact

- The translation flag of the profile model in `academic_persons`.
- Profile creation through `academic:createprofiles` and through a
  project-side login wiring, both of which go through
  `AbstractProfileFactory::createProfileForUser()`.
- No database, configuration or public API change. A behaviour change with an
  `Important-` entry in the 2.4 changelog.

## Non-goals

- Changing how translations are synchronised, or which languages are allowed.
- Creating translations for profiles that were already created without them.
  The next update run or edit of those profiles catches up, as it does today.
- Dispatching the profile update event for backend saves.

## Source

ACE-610, one issue for `main` and this backport. The model and the listener
gate are identical on both branches.
