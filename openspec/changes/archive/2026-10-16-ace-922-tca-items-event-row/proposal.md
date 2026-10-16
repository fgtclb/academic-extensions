## Why

The select items event of `academic_base` (`packages/fgtclb/academic-base`)
declares the record of the item provider parameters, `row`, as always present.
The job form of `academic_jobs` (`packages/fgtclb/academic-jobs`) dispatches the
same event in the frontend for its fields "Job Type" and "Employment Type",
and passed no `row` there. A listener that read it without a fallback raised a
warning in the job form, which made the form fail where warnings are turned
into exceptions, as with the debug preset, while static analysis told its
author that the key is always there (ACE-922). The manuals already advise a
fallback, so the declared contract and the documentation disagree.

## What Changes

- The job form hands an empty record to the listeners of the select items
  event, so a listener that reads the record itself works there instead of
  raising a warning.
- The select items event, and the two item providers of the job record,
  declare the record optional, so a listener author is told by static
  analysis to read it with a fallback.
- The event chapters of the `academic_base` and `academic_jobs` manuals say
  that the record is optional and empty in the job form, the upgrade guides
  of `academic_base` from 2.3 name the fallback, and `academic_base` gets a 2.4
  `Important` entry, as the fix lands on branch `2` as well.
- Neither change is breaking: the backend form passes the record as before.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `academic-jobs/new-job-form`: a requirement is added that a listener of the
  select items event finds an empty record when the job form builds its
  selects. The existing requirements hold unchanged.

## Impact

- `academic_base`: the parameter array of the select item helper trait, the
  declared parameter shape of the event, a new functional test of the helper,
  the event chapter of the manual, the upgrade guides `From23To24` and
  `From23To30`, and the 2.4 entry
  `Important-SelectItemHelperPassesAnEmptyRow.rst`.
- `academic_jobs`: the declared parameter shape of the item providers of the
  job type and the employment type, a new functional test of the job form
  with the fixture extension `test_job_select_items_listener`, and the event
  chapter of the manual.
- `docs/architecture/backend-select-items.md` and
  `docs/testing/fixture-extensions.md`.
- TYPO3 v13 and v14 behave the same. No TCA, database, TypoScript or
  dependency change.

## Non-goals

- A record with the defaults of the job table as a stand-in. Its page would
  be invented: a submitted job lands in the storage folder of the plugin,
  which the select items are built without, and the page of the form is
  passed separately already.
- The item providers of `academic_persons` and `academic_contacts4pages`. They
  are called by the backend form only, which always passes the record, so
  their declared shape stays as it is.
- The inline parent keys and the FlexForm parent record, which the helper
  does not pass and which the event declares optional already.
