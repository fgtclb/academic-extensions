## Context

- `ValidationNormalizer::normalizeValidation()`
  (`academic-base/Classes/Settings/ValidationNormalizer.php:66-99`) derives
  `readOnly` from `readonly` or `disabled`. It suppresses `required` for
  read-only fields and always writes `$tcaConfig['readOnly'] = $readOnly`.
- `TcaValidationMerger::merge()` folds `tcaConfig` into the TCA columns with
  `ArrayUtility::mergeRecursiveWithOverrule()`. That TCA covers the profile,
  contract, address, e-mail and phone tables of `academic_persons`.
- `Validation` already carries `readOnly`, `tcaConfig` and the normalised
  `flags` (`academic-base/Classes/Settings/Validation.php:32-42`). The editor
  reads `readOnly`.
- The only user of the normaliser is `AcademicPersonsSettingsFactory`.
  `academic_jobs` has its own implementation.
- On branch `2` the normaliser does not exist. The flags are evaluated in
  `academic_persons`' `AcademicPersonsSettingsFactory.php:99-110` there.

## Goals / Non-Goals

**Goals:**

- A frontend lock without any backend effect.

**Non-Goals:**

- Moving the `disabled` `@todo` (backend handling) forward.

## Decisions

### A new flag, not a new meaning for `readonly`

With `frontendreadonly`, the normaliser sets `Validation::$readOnly = true`
and leaves `$tcaConfig['readOnly']` at the value `readonly` or `disabled`
give. Without those, that value is `false`, exactly as for a field without
any lock.

Rejected: making `readonly` frontend-only. One of the analysed projects
relies on the backend lock.

### `required` stays a backend constraint

For the frontend, `frontendreadonly` suppresses the `NotEmpty` validator,
like `readonly`, because a read-only value cannot be corrected by the user.
The TCA `required` and `minitems` from a `required` flag stay. A backend
editor can still be forced to fill the field.

Rejected: suppressing `required` in both places, which would silently drop a
backend constraint an integrator asked for.

### No core version split

The normaliser and the merger are plain PHP on both core versions.

## Risks / Trade-offs

- [Integrators confuse the two flags] → The validator list in `Settings.yaml`
  and the manual state the difference in one line each.
