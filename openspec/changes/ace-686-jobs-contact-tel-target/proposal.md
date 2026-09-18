# Change: Make the job contact phone link dialable

## Why

The job detail view of `academic_jobs` writes the stored contact phone number
into the link target unchanged, so a number stored the way a reader wants to
read it produces a target that carries spaces and cannot be dialed. ACE-679
corrected the same defect in `academic_persons` and named this one as outside
its scope; it has since been named in two pull requests and never filed.

## What Changes

- The contact block of the job detail view builds the phone link target from
  the stored number with its spaces removed, and keeps the stored spelling as
  the visible link text. This is the shape `academic_persons` already uses.
- The functional test that asserts the current, wrong target is corrected, and
  the contact fixture gains a number that makes the difference visible.
- Not breaking: the markup, its classes and the link text are unchanged. Only
  the value of the `href` attribute changes.

## Non-goals

- **No prefix setting.** ACE-679 added
  `plugin.tx_academicpersons.phoneNumbers.telPrefix` for installations that
  store extension numbers only, on records the organisation itself maintains.
  A job contact phone is free text typed per posting by whoever posts the job,
  and is usually already complete; a site-wide prefix would corrupt it.
- **No validation or normalisation of what is stored.** The column keeps
  whatever an editor types; only the derived link target changes.
- **No change to the contact e-mail**, which already goes through a link
  ViewHelper, and none to the new-job form, where the same property is a form
  field rather than a link.

## Capabilities

**New Capabilities**

- `academic-jobs/job-contact` — what the contact block of the job detail view
  renders for a visitor.

**Modified Capabilities**

- none. The existing `academic-jobs/job-detail` capability covers the flags and
  the link of a job and keeps its scope.

## Impact

- `academic_jobs` (`packages/fgtclb/academic-jobs`):
  `Resources/Private/Partials/Job/Contact.html`, one line, plus its functional
  test and fixture. No PHP, no TCA, no setting, no column.
- TYPO3 v13 and v14 behave identically here; no version switch is needed.
- Branch `2`: the partial is byte-identical there, so a backport is
  recommended. The ViewHelper the fix uses is already in use on that branch in
  `academic_persons`, so it is available on TYPO3 v12 and v13.
- An installation that overrides the partial in its own site package keeps its
  own output and its own defect until it drops the copy.
