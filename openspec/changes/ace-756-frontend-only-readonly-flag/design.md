## Context

The `main` change decides what the flag means and why. This change carries
the same decisions to branch `2`. A file-level diff between the branches at
`561fa14bb` (`main`) and `451e9b7bf` (`2`) gave:

- The flags are evaluated in `AcademicPersonsSettingsFactory` of
  `academic_persons` here. `academic_base` has no validation normaliser on
  this branch, so nothing there changes.
- The flag computation of the factory is line for line the one the
  normaliser on `main` was taken from, so the same change applies.
- There is no expanded document map form and no legacy settings migrator on
  this branch. Both parts of the `main` change are left out.
- All six TCA files merge the `tcaConfig` of their set, and every domain
  factory of the editor refuses a read-only property, as on `main`.
- The form partials differ. They put the HTML `readonly` attribute on every
  control, and browsers ignore it on a select and a checkbox, so those stay
  operable and a changed value is discarded on save. `main` renders them
  `disabled`. That is true for `readonly` already, is documented here, and is
  fixed separately as ACE-757, because the fix changes `readonly` as well.
- The settings files are merged by their top-level keys, not recursively. An
  installation that lists the flag restates the whole `validations` map, as
  for any other change of it. The flag does not change that.

## Goals / Non-Goals

**Goals:**

- The same flag with the same meaning as on `main`, with the same changelog
  file in `academic_persons`.

**Non-Goals:**

- The `academic_base` changelog of `main`, which describes a class this
  branch does not have.

## Decisions

### Same computation as on `main`

`frontendreadonly` sets `Validation::$readOnly` after the TCA fragment is
built, so the backend keeps the field editable. A `required` beside it keeps
the TCA `required` and `minitems` and drops the frontend `NotEmpty`
validator.

### Tests in the shape of this branch

There is no unit test of the settings factory on this branch. One is added,
with a mocked package manager and cache and a fixture settings file, because
the factory reads the files itself. The functional tests use a fixture
extension that restates the shipped `validations` map, because a partial map
would replace the shipped one.

## Risks / Trade-offs

- [Integrators confuse the two flags] The flag list in `Settings.yaml` and
  the manual state the difference in one line each.
