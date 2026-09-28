## Why

The six TCA files of `academic_persons` (`packages/fgtclb/academic-persons`)
merge the validation of the persons settings into their own table, at the end
of `Configuration/TCA/`. Every `Configuration/TCA/Overrides` file runs after
that. A site package that replaces a person column as a whole, for a label or
a rich text configuration, drops `required` and `readOnly` of that column
without a word, and one analysed project does so for five profile columns. The
settings are also built while the TCA is incomplete, so a column a project adds
in its overrides cannot be checked against them. Project columns editable in
the frontend editor (`ace-tbd-editor-custom-profile-fields`) need exactly that
check, so this change comes first.

## What Changes

- The settings are applied to the TCA of the six person tables once the TCA is
  compiled, after every TCA override, and the TCA cache and the TCA schema cache
  hold the result.
- **BREAKING**: the settings win over a TCA override of `required` and
  `readOnly` on a column they configure.
- A column a TCA override replaces keeps what the settings say about it.
- A field of the settings whose column the TCA does not have adds no incomplete
  column.
- The TCA listener of EXT:content_blocks stays before the settings, without
  that extension becoming a dependency or a suggestion.

The behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `academic-persons/validation-flags`: when the flags reach the backend form,
  relative to the TCA overrides of other packages.

## Impact

- `academic_persons`: a TCA listener replaces the merges of six TCA files.
- A fixture extension with TCA overrides and a listener standing in for the
  one of EXT:content_blocks.
- A 3.0 Breaking changelog entry, the validations chapter, the upgrade guide,
  `docs/architecture/validation-settings.md`.

## Non-goals

- The settings of `academic_jobs`, a separate implementation whose TCA method
  has no caller.
- Project columns in the settings, the frontend editor, and the check of a
  declared column. Those are `ace-tbd-editor-custom-profile-fields`.
- Backporting to branch `2`.
