## Why

A profile created from a frontend user by `academic:createprofiles` reports
itself as a translation until it is loaded from the database again. The
translation synchronisation skips translations, so a newly created profile
gets no translation in any allowed language. That lasts until someone edits
the default-language profile.

## What Changes

- A profile that was never loaded from the database, including one that was
  just created and persisted, no longer reports itself as a translation.
- Profiles created by `academic:createprofiles` get their translations
  straight away, in every language configured in the `academic_persons_edit`
  option `profile.allowedLanguages`.
- The answer for profiles loaded from the database does not change.

The extensions involved are `academic_persons`
(`packages/fgtclb/academic-persons`), where the fix lives, and
`academic_persons_edit` (`packages/fgtclb/academic-persons-edit`), whose
translation synchronisation benefits. The behaviour is identical on TYPO3 v13
and v14.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `academic-persons/frontend-user-profile-sync`: adds the requirement that a
  profile created from a frontend user is treated as a default-language
  profile, so it is translated.

## Impact

- The translation flag of the profile model in `academic_persons`.
- Profile creation through `academic:createprofiles`, the only caller of the
  profile creation on this branch. There is no creation at login.
- No database, configuration or public API change.

## Non-goals

- Dispatching the profile update event for backend saves. That is the change
  `ace-tbd-backend-save-announces-profile-update`, which depends on this one.
- Changing how translations are synchronised, or which languages are allowed.
- Creating translations for profiles that were already created without them.
  The next update run or edit of those profiles catches up, as it does today.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`persons-data-01`, which absorbed `persons-display-01`). Four of the six
analysed projects are affected today: one carries its own patch of the model,
the other three create profiles through the same code path without a patch.
No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-<slug>` when the issue is filed after implementation.

Implements ACE-610.
