## Context

See `proposal.md` for the motivation. On this branch
`JobController::createAction()` of `academic_jobs` decides everything after
the save inline: it resolves the redirect page and the message mode from the
plugin and TypoScript, dispatches `AfterSaveJobEvent`, applies the deprecated
`listPid`, maps a page id of 0 or below to none, sends the mail and asks the
enum `SaveForm\FlashMessageCreationMode` whether to queue the confirmation.
`main` moved that decision into `AfterSaveResolver` and `AfterSaveDecision` of
`academic_base`, which do not exist here. The enum is unchanged.

Verified on TYPO3 12.4.45 and 13.4.36 in this branch's vendor trees:

- `redirect('list')` answered the submission with `200` and the whole page,
  the redirect went out through `header()` only, on v12 as on v13.
- The `ResponsePropagation` middleware sits inside the frontend user
  authenticator on v12 as on v13 (after `typo3/cms-frontend/tsfe`), so the
  authenticator still stores the session and sends the `fe_typo_user` cookie
  for a thrown redirect.
- `getErrorFlashMessage()` is queued with `addFlashMessage()` and its default
  `storeInSession = true` on v12, and stayed in the session on v12 and v13.
- `CacheService` is a shared service on both, the one the persistence backend
  registers the saved record with.
- The v12 URI builder falls back to `$GLOBALS['TSFE']->id` without a target
  page.

## Goals / Non-Goals

**Goals:**

- The same behaviour as on `main`, on TYPO3 v12 and v13: one exit back to the
  form and one to the redirect page, both answering `303` without a page.
- No new class and no change to `academic_base`.

**Non-Goals:**

- Backporting `AfterSaveResolver` or `AfterSaveDecision`.
- A change of the enum or of `AfterSaveJobEvent`.

## Decisions

### "Returns to the form" is derived in the controller

The controller computes it right after the mapping of a page id of 0 or
below: the redirect page is null, or equal to the current page. That is
exactly what `AfterSaveDecision::$returnsToForm` says on `main`.

Rejected: backporting the resolver of `main` with it. It is `@internal`, it
would add a class to `academic_base` and move code of this branch only to
fix a defect, and the enum and the inline resolution are tested here as they
are.

### The form page is set explicitly

Back to the form builds the plain URL with
`$this->uriBuilder->reset()->setCreateAbsoluteUri(true)` and
`setTargetPageUid()` with the page of the frontend controller, which is how
this branch reads the current page on both cores (`frontend.page.information`
does not exist on v12). Without it the v12 builder falls back to the global
frontend controller, which gives the same page, but depends on a global.

### The label of the item `-1`

"Use the site or TypoScript setting", as on `main`: TYPO3 v12 has no site
sets, the item defers to the TypoScript constant there.

### The queue of the form

`JobController::CONFIRMATION_QUEUE_IDENTIFIER` is
`academic_jobs.new_job_form.confirmation`, as on `main`, rendered by
`Templates/Job/New.html` inside `.academic-jobs-new`, above the error alert.
A visitor sent to another page gets the confirmation in the default queue of
the plugin, so the documented snippet for redirect pages keeps working.
Rejected: the default queue in `New.html`, for the reasons named on `main`, a
message queued for a redirect page would turn up on the form, and so would
the error message of Extbase.

### The exits, thrown

Both exits throw `PropagateResponseException` around `redirectToUri()`, the
pattern `showAction()` of this controller already uses. The exception skips
what `Extbase\Core\Bootstrap` does after an action, so the controller calls
`PersistenceManager::persistAll()` and then
`CacheService::clearCachesOfRegisteredPageIds()` before it throws. The first
stores what a listener of `AfterSaveJobEvent` handed to a repository without
persisting it, which Extbase would have stored at the end of the request.
The `AfterRequestDispatchedEvent` is skipped as well. Nothing in the extensions
listens to it, and the test listener that recorded the response is removed.

### Extbase's error message, no job, the null uid and `listPid`

As on `main`: `getErrorFlashMessage()` returns `false`, a request without a
job leaves through the form exit instead of `redirect('new')`, the null uid
branch queues "Job not created" for the form queue and leaves through the form
exit before the event and the mail, `listPid` is read with a default of 0 and
used only above 0. The phpstan baseline entry of both cores for the null uid
handed to the mail is dropped.

### Tests on this branch

The testing helper here has no `submitFrontendForm()`. The tests post the job
through `postJob()` of the notification mail test case, which takes the
session cookie as an argument now, and the trait gets `frontendSessionCookie()`
and `followSeeOther()` as on `main`. The label test reads the FlexForm through
`FlexFormTools`, the trait `main` uses for it does not exist here.

## Risks / Trade-offs

- [A project copy of `New.html` does not render the new queue] → the
  confirmation waits in the session. The changelog, the upgrade guide and the
  template chapter of the manual name the line to add.
- [Script code read the body of the answer] → it gets an empty `303` now,
  named in the changelog.
- [The null uid branch is not covered by a functional test] → it needs a
  persistence that fails without an exception, which the test instance does
  not offer without mocking.
