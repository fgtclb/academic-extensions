## Why

academic_persons reads `Configuration/AcademicPersons/Settings.yaml` from
every active package. The academic_base loader folds those files with a
top-level merge, so the package loaded last replaces a whole top-level map. A
project that changes one validation flag has to copy the complete `profile`,
`contracts` or `documentSections` map. Five projects ship such full copies, and
every copy freezes the upstream state: a field or section added upstream later
never reaches them.

## What Changes

- The settings file loader of academic_base (`packages/fgtclb/academic-base`)
  merges maps recursively. A later package changes only the keys it names, at
  any depth; every other key keeps the value of the earlier package.
- A list, such as the flags of a field or the element order of a public profile
  column, is replaced as a whole.
- The value `null` removes a key.
- A map that restates every key of the earlier map keeps its own key order. A
  partial map keeps the earlier order, and its new keys are appended.
- **BREAKING**: a project that removed an upstream entry by leaving it out of
  its copy gets the entry back and has to set it to `null`. A complete copy,
  reordered or not, gives the same result as before.
- academic_persons (`packages/fgtclb/academic-persons`) is the only consumer.
  Its settings, the legacy settings report and the migration command work
  unchanged apart from the merge.
- The behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-base/settings-file-merge`: how the settings files that several
  active packages ship for one academic extension are combined.

### Modified Capabilities

None.

## Impact

- The settings file loader of academic_base and its unit tests.
- The persons settings documentation (`Documentation/Configuration/Sections`
  and `Validations`) and `docs/architecture/validation-settings.md`, which
  both document the top-level merge today.
- The cached persons settings are rebuilt on the first request after the
  deployment's cache flush, as for any settings change.
- No database, TCA or dependency change.

## Non-goals

- A dotted per-field override syntax or an `overrides:` key.
- Merging lists by value or by position.
- A loader parameter for per-extension replace-only keys.
- Changing the persons settings schema or its legacy overlay.
- Backporting to branch `2`, which carries the pre-3.0 settings and must not
  change merge semantics in a minor release.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`cross-cutting-05`). Five of the six analysed projects carry their own code for
this today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-settings-loader-deep-merge` when the issue is filed after
implementation.

Filed as a sub-issue of ACE-109, with ACE-161 linked as its duplicate (the
generic settings system this is the merge part of). Relates to ACE-536.
