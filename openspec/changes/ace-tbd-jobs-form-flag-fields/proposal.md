## Why

The new-job form cannot set the two job flags "internationals welcome" and
"recommended by alumni", although both exist on the job. A project that adds
them through the shipped checkbox partial breaks the form: an unchecked box
submits an empty value that the job cannot take. A project that needs an
extra field such as a captcha has to copy the whole form template.

## What Changes

- The new-job form offers both flags as checkboxes.
- The alumni checkbox label says what the flag means, "Recommended by
  alumni" ("Von Alumni empfohlen"), instead of "Created by Alumni", matching
  the detail label of ACE-596.
- An unchecked flag is stored as "not set" instead of failing the
  submission.
- The form gets an empty, documented slot for additional fields, rendered
  before the submit button, so a project overrides one small partial instead
  of the form template.

The behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-jobs/new-job-form`: what the new-job form offers and how it
  stores the two job flags.

### Modified Capabilities

None.

## Impact

- `academic_jobs` (`packages/fgtclb/academic-jobs`): the form partial
  `Resources/Private/Partials/Job/Properties/Job.html`, the template
  `Resources/Private/Templates/Job/New.html`, a new partial
  `Resources/Private/Partials/Job/Forms/AdditionalFields.html`, the create
  action's argument handling and the form labels.
- Visible output: two new optional checkboxes in the form; the reworded
  alumni label in English and German.
- Functional form submission tests; a `Feature-` changelog entry.

## Non-goals

- Validating or storing the content of additional fields; the slot only
  renders.
- Changing the type of the flags on the job model.
- Rendering the flags in the detail view; that is a change of its own.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`listings-06`). Two of the six analysed projects carry their own code for this
today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-jobs-form-flag-fields` when the issue is filed after implementation.
