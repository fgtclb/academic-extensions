## Why

The `readonly` and `disabled` validator flags in `Settings.yaml` lock a field
in the frontend editor and in the backend form at the same time. Projects
that want synced fields locked only for frontend users have to reset the
backend lock field by field through TCEFORM.

## What Changes

- A new case-insensitive validator flag, `frontendreadonly`, locks a field in
  the frontend editor only. Submitted values are ignored, as with `readonly`,
  and the backend form stays editable.
- `readonly` and `disabled` keep their current meaning in both places. When a
  field lists both `frontendreadonly` and `readonly`, `readonly` wins.
- The flag works for profile, contract and contract contact fields, which
  are all governed by `Settings.yaml`.

This affects:

- `academic_base` (`packages/fgtclb/academic-base`), which normalises the
  flags;
- `academic_persons` (`packages/fgtclb/academic-persons`), which ships and
  documents the settings and merges them into its TCA;
- `academic_persons_edit` (`packages/fgtclb/academic-persons-edit`), whose
  editor honours the flag without a code change.

The behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-persons/validation-flags`: how validator flags in `Settings.yaml`
  affect the frontend editor and the backend form, starting with the
  frontend-only lock.

### Modified Capabilities

None.

## Impact

- One more recognised flag in the validation normaliser. No change to the
  merged TCA of fields that do not use it.
- Documentation of the validator list in `Settings.yaml` and in the
  extension manual.

## Non-goals

- Changing `readonly` to frontend-only. That would break installations that
  want the backend lock too.
- Locking per record, depending on whether it was synchronised.
- Changing the flags of `academic_jobs`, which has its own implementation.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`persons-data-08`). Three of the six analysed projects carry their own code for
this today: two reset TCEFORM `readOnly`, one covers the same need in its
settings. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-<slug>` when the issue is filed after implementation.
