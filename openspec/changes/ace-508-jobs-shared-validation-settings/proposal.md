## Why

`academic_jobs` (`packages/fgtclb/academic-jobs`) reads its
`Configuration/AcademicJobs/Settings.yaml` with a loader, a registry and two
ViewHelpers of its own. Three readers understand three different keyword sets
(ACE-429): `url` validates but changes nothing in the backend, `number`
renders a number input but validates nothing, keywords are case sensitive, a
typo is printed as the HTML input type, and the backend never sees the file.
`academic_persons` has used the shared classes of `academic_base` for all of
this since ACE-501. The planned move of the jobs form pieces to
`academic_base`, for a second form extension, needs jobs on the shared classes
first.

## What Changes

- The jobs settings file is read by the shared loader of `academic_base`
  (`packages/fgtclb/academic-base`) and normalised once into validation sets.
  **BREAKING**: files of several packages are merged recursively, so an
  override changes only the fields it names. An override that left a field out
  to drop its flags now has to name it with an empty list.
- Flags follow the shared vocabulary: matched without regard to case, and an
  unknown flag is ignored instead of becoming the HTML input type.
- The new-job form, the server-side validator and, new, the backend record
  editor read the same validation set. **BREAKING**: a listener applies the
  flags to the TCA of the job table after every TCA override, like persons
  does. With the shipped file, company name and description become required in
  the backend, and a project TCA override of `required` or `readOnly` on a
  configured column is overruled by the settings.
- The shipped file marks `contactPhone` with `tel` instead of `number`, so the
  form accepts `+49 30 123`.
- **BREAKING**: `AcademicJobsSettingsLoader`, `AcademicJobsSettingsRegistry`,
  the ViewHelpers `j:validation.requiredFromValidation` and
  `j:validation.fieldTypeFromValidation`, the two jobs exceptions and the
  unregistered `ServiceProvider` are removed. A project partial that calls one
  of the ViewHelpers fails to render until it is migrated.

The behaviour is the same on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-jobs/validation-settings`: how the jobs settings file is merged
  and read, and what each flag does in the new-job form, in the server-side
  validation and in the backend record editor.

### Modified Capabilities

None. `academic-jobs/new-job-form` keeps its requirements.

## Impact

- `academic_jobs`: the settings classes, `JobValidator`, `JobController`, the
  form partials `Job/Forms/FieldWrapper.html` and `Textfield.html`, a TCA
  listener, `Services.yaml`, the shipped settings file, the manual page
  *Validation settings* and its changelog entries.
- `academic_base`: no code change. It gains a second consumer of its
  `@internal` settings classes and of `ValidationEnsureViewHelper`.
- `docs/architecture/validation-settings.md` loses its second implementation.
- No database change. Main only, v3 line.

## Non-goals

- ACE-429's first half: `required` still accepts `0` for job type and
  employment type. That is a bug on both branches with its own fix.
- A frontend lock: `readonly` and `disabled` lock the backend column, the
  new-job form keeps offering the field.
- Moving the form partials to `academic_base`, which is a change of its own.
- Backporting to branch `2`.
