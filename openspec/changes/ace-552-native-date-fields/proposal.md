## Why

A profile timeline entry stores a year as an integer. A year is not a date: it
cannot be formatted for a locale, it cannot be compared with a contract date,
and it forces the frontend editor to render a number field where every other
date renders a date control. The editor's own date fields are meanwhile a text
control, because the native browser picker was deferred when the editor was
rewritten. Both halves are settled here, while 3.0.0 is unreleased and the
schema can still change.

## What Changes

- **BREAKING** `academic_persons` (`packages/fgtclb/academic-persons/`): the
  three integer columns `year`, `year_start` and `year_end` of
  `tx_academicpersons_domain_model_profile_information` become the nullable SQL
  date columns `date`, `date_start` and `date_end`, with the model properties
  `date`, `dateStart` and `dateEnd`. No `year_only` column is introduced.
- **BREAKING** The settings vocabulary follows: the document row field and
  validator key `year` becomes `date`, and the aliases `from` and `to` address
  `dateStart` and `dateEnd`.
- A date field declares, per field, which parts of a stored date a visitor is
  shown — year, month and day, independently — and the value is then formatted
  for the locale of the matched site language rather than the browser's.
- A date field declares its input granularity — full date, year and month, or
  year alone — together with the rule that completes the parts the editor did
  not ask for.
- **BREAKING** `academic_persons_edit`
  (`packages/fgtclb/academic-persons-edit/`): a date field is edited in a
  native browser date control, the contract dates `validFrom` and `validTo`
  included. The `dd.mm.yyyy` format hint of those two disappears.
- An abstract, deliberately unregistered upgrade wizard ships as the starting
  point of a project's own year-to-date migration. The extension never invents
  a month or a day by itself.
- `academic_jobs` (`packages/fgtclb/academic-jobs/`): the job form prefills its
  native date inputs in a format a browser discards, so a stored job date is
  lost on every edit. The prefill is corrected and stored job dates are
  rendered for the site locale.

Behaviour is identical on TYPO3 v13 and v14. The one version difference met on
the way — `f:format.date` gained a `timezone` argument in v14 — is not used.

## Capabilities

### New Capabilities

- `academic-persons/profile-information-dates`: what a timeline entry stores,
  and how much of it a visitor is shown.
- `academic-persons-edit/profile-editing-dates`: how a date is edited, hinted,
  submitted and refused in the frontend editor.
- `academic-jobs/job-form-dates`: how the job form shows and keeps a date.

### Modified Capabilities

None. No existing spec describes date behaviour.

## Impact

The profile information table, its TCA and model; the persons settings graph
and the shipped `Settings.yaml`; the public timeline partial; the editor's
control partials, prototypes, endpoints and TypeScript; the jobs form partial
and controller; ten functional CSV fixtures; the development seed and both seed
manifests; both development instances.
