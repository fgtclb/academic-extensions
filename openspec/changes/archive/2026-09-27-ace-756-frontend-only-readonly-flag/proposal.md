## Why

The backport of the `main` change of the same name, ACE-756, archived there as
`openspec/changes/archive/2026-09-27-ace-756-frontend-only-readonly-flag`.

The `readonly` and `disabled` validator flags of the `validations` map in
`Settings.yaml` lock a field in the frontend editor and in the backend form at
the same time. Projects that want synchronised fields locked only for profile
owners reset the backend lock field by field through TCEFORM. Two of the
projects that do so run 2.x, which is why the flag ships with 2.4 rather than
waiting for 3.0.

## What Changes

- A new case-insensitive validator flag, `frontendreadonly`, locks a field in
  the frontend editor only. Submitted values are ignored, as with `readonly`,
  and the backend form stays editable.
- A `required` beside it keeps the field required in the backend form, while
  the frontend editor does not ask the owner for a value.
- `readonly` and `disabled` keep their current meaning in both places, also
  when `frontendreadonly` is listed as well.
- The flag works in every set of the `validations` map.

Affected extension: `academic_persons` (`packages/fgtclb/academic-persons`),
whose settings factory reads the flags on this branch, and whose editor in
`academic_persons_edit` honours the flag without a code change. The behaviour
is identical on TYPO3 v12 and v13. The changelog file in
`Documentation/Changelog/2.4/` is byte-identical to the one on `main`.

## Capabilities

### New Capabilities

- `academic-persons/validation-flags`: how validator flags in `Settings.yaml`
  affect the frontend editor and the backend form, starting with the
  frontend-only lock.

### Modified Capabilities

None.

## Impact

- One more recognised flag in the settings factory. No change to the merged
  TCA of fields that do not use it.
- Documentation of the flag list in `Settings.yaml`, in the extension manual
  and in `docs/`.
- No database schema, TCA column or dependency change.

## Source

ACE-756, a subtask of the academic persons epic ACE-17.
