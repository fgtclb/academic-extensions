## Why

One project built `fgtclb/academic-apartments` 1.0.0 in its own tree as a
clone of `academic_jobs` (`packages/fgtclb/academic-jobs`): 36 of its 76 files
map to jobs files one to one after renaming, and 220 of 328 unique lines of
its controller match the jobs controller. It targets APIs `main` no longer has
(the removed file upload converter, the frontend controller object, CKEditor 4
from a CDN), so it cannot be adopted as it is. A second copy of the jobs form
machinery upstream would also make every later jobs fix a manual copy.

## What Changes

This is a proposal only. It creates no extension.

- It records the prerequisite: `academic_jobs` has to share its form
  machinery before a second form extension can build on it. That is two
  steps.
  - Adopting the shared settings and validation classes of `academic_base`
    (`packages/fgtclb/academic-base`) in `academic_jobs`. That is ACE-508,
    and `docs/architecture/validation-settings.md` calls it a behaviour
    change for jobs, so it stays a change of its own.
  - Moving the generic frontend-submission pieces from `academic_jobs` into
    `academic_base`: the flash message creation mode, the form field
    partials, and the redirect and flash message decision after a save. The
    jobs form behaves exactly as before, and project overrides of the jobs
    form partials keep working. This change plans this step, and it starts
    only once ACE-508 is merged, so the moved partials read the shared
    settings from the start.
- It records that a housing exchange is not in scope for the academic
  family now, and the conditions for revisiting that later: the maintainer
  reverses that decision; both steps above are merged; the job list
  pagination of candidate `listings-16` is merged, so the list plugin has a model to follow; the
  project's model fields are taken over, not its code; the TER key, the split
  repository and the maintenance owner are settled.

The move is identical on TYPO3 v13 and v14.

Two premises of the candidate did not hold when it was written. Moving jobs
onto the shared settings layer is not free of behaviour change, which is why
it was split off as ACE-508, merged before this change. And a public class
that moves needs a deprecated alias: `FlashMessageCreationMode` is `@api`,
because `AfterSaveJobEvent` hands it to listeners in project code.

## Capabilities

### New Capabilities

None. The move changes no behaviour, so the change sets `skip_specs: true`.
An apartments extension would bring its own specs in a change of its own.

### Modified Capabilities

None.

## Impact

- `academic_jobs`: the flash message creation mode, the form field partials
  below `Resources/Private/Partials/Job/Forms/`, the save and redirect code
  of the controller, a class alias map.
- `academic_base`: a new form namespace, form field partials, and a
  stateless service for the redirect and flash message decision.
- No database change.

## Non-goals

- Creating `academic_apartments`.
- Adopting the project's code.
- ACE-508 itself.
- Events for the job list and detail views.
- Backporting to branch `2`.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`listings-23`). One of the six analysed projects carries its own code for
this today. Filed as ACE-797, which relates to ACE-508 and ACE-435.
