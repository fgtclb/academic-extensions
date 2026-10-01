## Context

See `proposal.md` for the motivation. Verified on `main`:

- `Job::setAlumniRecommend(int)` and `setInternationalsWelcome(int)`
  (`Classes/Domain/Model/Job.php:120`, `:130`) take `int`, and `Job` is not
  final. The columns are TCA `check` with default 0 and `tinyint(1)`.
- `Partials/Job/Properties/Job.html` renders no flag. `Partials/Job/Forms/Checkbox.html`
  is shipped and referenced by no template.
- The checkbox ViewHelper renders an empty hidden field for its name
  (`renderHiddenFieldForEmptyValue()`), so an unchecked box submits `''`.
  Extbase's `IntegerConverter` turns `''` into `null` (installed 13.4.35,
  :40-41), which the `int` setter cannot take.
- Form labels follow `create.job.<identifier>.label` (`Forms/FieldWrapper.html:11`).
  `create.job.alumniRecommend.label` exists ("Created by Alumni"),
  `create.job.internationalsWelcome.label` does not.
- `initializeCreateAction()` (`JobController.php:235-258`) already configures
  property mapping for the date fields and the image upload.
- `New.html:34-41` renders the properties partial and the submit button, with
  nothing in between.

## Goals / Non-Goals

**Goals:**

- The flags are settable through the form without changing the model API.
- A project adds a field by overriding one partial.

**Non-Goals:**

- A validation hook for additional fields. The extension dispatches no event
  before the job is stored, so a site checks such a field before the request
  reaches the plugin. An event for that is a change of its own.

## Decisions

### Render the flags through the shipped checkbox partial

`Properties/Job.html` renders both flags with `Job/Forms/Checkbox`, identifiers
`alumniRecommend` and `internationalsWelcome`. The missing label key is added
in English and German.

### Map an empty flag to `0` before property mapping

`initializeCreateAction()` replaces a submitted `''` for the two properties
with `'0'` in the `job` argument of the request, before the arguments are
mapped. The setters stay `int`.

Rejected: widening the setters to `?int`. `Job` is not final, and a project
subclass that overrides a setter with an `int` parameter would then violate
the parent signature and fail with a fatal error.

Rejected: a hidden field with value `0` in the template. The checkbox
ViewHelper emits its own empty hidden field for the same name, so a
template-side default cannot win reliably.

### An empty slot partial

`Job/Forms/AdditionalFields.html` is empty and rendered in `New.html` before
the submit button with `{_all}`. The documentation states that fields in the
slot must not be bound to properties the job model does not have, because the
property mapping of the `job` argument fails with an exception for a property
the target object lacks. A captcha field is named outside the `job` argument,
and Extbase leaves a value it has no argument for alone.

The path sits next to the other partials of the form in `Job/Forms/`, like
`Errors.html`, which renders for the whole form as well.

The change lands as two commits: the flags with the mapping and the label as a
bugfix, then the slot as a feature with a changelog entry of its own.

### Decided: reword the alumni form label

`create.job.alumniRecommend.label` changes from "Created by Alumni" /
"Erstellt von Alumni" to the wording agreed for the detail label of ACE-596,
"Recommended by alumni" / "Von Alumni empfohlen". The other flag gets the
detail wording too, "International applicants welcome" / "Internationale
Bewerbungen willkommen".

The key is used by the new-job form only, and today's text contradicts the
field: the backend label is "Alumni recommends" (`locallang_be.xlf:118`).
Changing a label text is not breaking. A site that overrides the key keeps
its own text.

## Risks / Trade-offs

- [A site does not want the flags in the form] → It overrides
  `Properties/Job.html`, as for any other field today.
- [A site relies on the old wording "Created by Alumni"] → It overrides the
  label. The `Important-` changelog entry names the new text.

## Migration Plan

None. Stored records keep 0 and 1.

## Open Questions

None.
