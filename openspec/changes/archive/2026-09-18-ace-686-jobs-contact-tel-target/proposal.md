## Why

The contact block of the job detail view of `academic_jobs`
(`packages/fgtclb/academic-jobs`) writes the stored contact phone number into
the link target unchanged, so a number stored the way a reader wants to read it
produces a `tel:` target with spaces in it that a device cannot dial. The
partial that does it is byte-identical to the one on `main`, so this branch
carries the same defect.

This is the backport of ACE-686, whose implementation on `main` is
pull request #665, re-derived for TYPO3 v12 and v13.

## What Changes

- The contact block builds the phone link target from the stored number with
  its spaces removed, and keeps the stored spelling as the visible link text.
  This is the shape `academic_persons` already uses on this branch.
- The plugin test of the list and detail views gains contact coverage, which it
  has none of today: a fixture with contact columns and three tests.
- Not breaking: the markup, its classes and the link text are unchanged. Only
  the value of the `href` attribute changes.

The behaviour is identical on TYPO3 v12 and v13.

## Capabilities

### New Capabilities

- `academic-jobs/job-contact`: what the contact block of the job detail view
  renders for a site visitor, and how the phone number of that block is offered
  as a link a device can act on.

### Modified Capabilities

None. The existing `academic-jobs/job-detail` capability covers the flags and
the link of a job and keeps its scope.

## Impact

- `academic_jobs`: `Resources/Private/Partials/Job/Contact.html` — one attribute
  value, plus the comment saying why the target and the label differ. No PHP, no
  TCA, no setting, no column.
- A contact fixture and three tests in the existing plugin test class, whose
  `setUpTestCase()` is parameterised for it — it hardcoded one fixture, while
  the copy on `main` already takes the fixture name.
- No schema, dependency or API change.

## Non-goals

- **No prefix setting.** A job contact phone is free text typed per posting by
  whoever posts the job and is usually already complete; a site-wide prefix
  would corrupt it. The `main` change rejected one for the same reason.
- **No validation or normalisation of what is stored.** The column keeps
  whatever an editor types; only the derived link target changes.
- No change to the contact e-mail, which already goes through a link
  ViewHelper, and none to the new-job form, where the same property is a form
  field rather than a link.

## Source

Backport of ACE-686 from `main`, re-derived from the file-level backport
analysis rather than cherry-picked. The partial was byte-identical, so the
patch is a literal copy — verified by diffing the two patched files against
each other. The coverage was not: this branch's `jobPages.csv` carries no
contact columns at all and its plugin test had no contact assertion, so the
tests are written here rather than adapted.
