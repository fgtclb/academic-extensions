## Context

See `proposal.md` for the motivation. `JobController::createAction()` of
`academic_jobs` resolves the redirect page and the message mode through
`AfterSaveResolver` of `academic_base`, dispatches `AfterSaveJobEvent`,
applies the deprecated `listPid`, sends the mail and asks
`AfterSaveResolver::decide()` for an `AfterSaveDecision`: the redirect page
and whether to queue the confirmation. Both classes are `@internal`, the
enum `FlashMessageCreationMode` is `@api`.

Verified on TYPO3 13.4.36 and 14.3.9:

- `redirect('list')` builds its link for the `List` plugin, the only plugin
  of the extension that offers `list`. The answer was a `303` to
  `/new-job?tx_academicjobs_list[action]=list&tx_academicjobs_list[controller]=Job&cHash=…`
  on both cores. The form plugin got no argument and rendered its `new`
  action, an empty form. The exception 1313855175 the issue names does not
  follow from this code.
- A returned redirect reaches the browser on v13 only through `header()`,
  the frontend answers `200` with the whole page. v14 answers `303` and still
  renders the page into the body.
- `getErrorFlashMessage()` queues its message in the session on v13 and in
  memory on v14, into the queue of the plugin.
- The form actions are not cacheable, the content element is a `USER_INT`, so
  a queue rendered in `New.html` never reaches the page cache.

## Goals / Non-Goals

**Goals:**

- One exit for "back to the form" and one for "to the redirect page", both
  answering `303` without rendering a page, on v13 and v14.
- The decision stays in `AfterSaveResolver`, the controller maps it to a
  queue and a URL.

**Non-Goals:**

- Anything for redirect pages beyond the documented snippet.
- A change of the `@api` enum or of `AfterSaveJobEvent`.

## Decisions

### `AfterSaveDecision::$returnsToForm`

`decide()` sets `returnsToForm` when the redirect page is null, which already
covers 0 and below, or equal to the current page. `redirectPageId` keeps what
it held, so the decision for "redirect to the current page" still names that
page. The controller reads only the flag to choose the exit, and the redirect
page only when the flag is false.

Rejected: setting `redirectPageId` to null for the current page. It would make
the flag redundant, but change what the existing decision tests and a reader
of the class expect, and lose the information that a page was configured.

### The queue of the form

`JobController::CONFIRMATION_QUEUE_IDENTIFIER` is
`academic_jobs.new_job_form.confirmation`, not configurable.
`Templates/Job/New.html` renders it with
`<f:flashMessages queueIdentifier="academic_jobs.new_job_form.confirmation" />`
inside `.academic-jobs-new`, above the error alert. The literal is repeated in
the template, as Fluid has no access to the constant, and the functional tests
hold both together. When the visitor is sent to another page the message goes
to the default queue of the plugin, so the documented snippet for redirect
pages keeps working. `addFlashMessageToQueue()` is now called with its
identifier for the form queue and without one for the plugin queue.

Rejected: rendering the default queue in `New.html` (option A). A message
queued for a redirect page that does not render it would show up on the form
at the next visit, and so would the error message of Extbase.

### The exits, thrown

Back to the form: `$this->uriBuilder->reset()->setCreateAbsoluteUri(true)->build()`,
the current page with the current language and no argument. To the redirect
page: the same after `setTargetPageUid()`. Both are thrown as
`PropagateResponseException` around `redirectToUri()`, the pattern of the list
filters (ACE-723), so the `ResponsePropagation` middleware answers `303` on
both cores and no page is rendered.

Throwing skips what Extbase does after an action: the
`AfterRequestDispatchedEvent` and `clearCachesOfRegisteredPageIds()` of
`Extbase\Core\Bootstrap`. The controller calls
`CacheService::clearCachesOfRegisteredPageIds()` itself before it throws. The
saved job is hidden, so no cached page shows it, but Extbase also flushes the
pages named in `TCEMAIN.clearCacheCmd` of the storage folder, and a site may
rely on that. `CacheService` is a singleton, the injected instance is the one
the persistence backend registered the record with. The event is not
replaced: nothing in the extensions listens to it, and the test listener that
recorded the response is removed.

### Extbase's error message

`getErrorFlashMessage()` returns `false`. The form shows the message of each
rejected field already, and the generic message named a PHP method.

### The null uid branch

It queues "Job not created" for the form queue and leaves through the "back
to the form" exit, so it never reaches the event, the mail or the decision.
`listPid` is read as `(int)($this->settings['listPid'] ?? 0)` and used only
when it is above 0.

## Risks / Trade-offs

- [A project copy of `New.html` does not render the new queue] → the
  confirmation waits in the session. The changelog and both 2.3 upgrade
  guides name the line to add.
- [Script code read the body of the answer on v13] → it gets an empty `303`
  now, named in the changelog.
- [Mode "always" with a redirect page that renders nothing] → the message
  stays in the session, as before, but no longer reaches the form. The manual
  keeps the snippet for the redirect page.
- [The null uid branch is not covered by a functional test] → it needs a
  persistence that fails without an exception, which the test instance does
  not offer without mocking. Stated in the commit.
