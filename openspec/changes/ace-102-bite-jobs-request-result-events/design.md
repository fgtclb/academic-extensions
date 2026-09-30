## Context

This is the backport of the `main` change of the same name, archived there as
`openspec/changes/archive/2026-09-30-ace-102-bite-jobs-request-result-events`.
The decisions are the ones taken there, and only what differs on this branch
is added.

- `BiteJobsService` is byte-identical to the one `main` had before the
  change: `final`, no event, a fixed payload, the decoded body kept in
  `$responseBody`, the limit applied last.
- `BiteJobsController::listAction()` builds no plugin context and dispatches
  no plugin view event here. `ModifyPluginViewEvent` does not exist on this
  branch.
- `academic_base` ships `PluginControllerActionContext` and its interface
  here too, with the same constructor. `ModifyJobControllerNewActionViewEvent`
  of `academic_jobs` declares that interface already. The events of
  `academic_persons` declare the older interface of their own extension, so
  they are no precedent here. Unlike on `main`, the interface carries no
  `@api` tag on this branch, although both new `@api` events hand it out. It
  is used as on `main` so that a listener works on both versions, and tagging
  it is left to a change of its own.
- TYPO3 v12 has no `TYPO3\CMS\Core\Attribute\AsEventListener`. Listeners are
  registered with the `event.listener` tag in `Services.yaml`.
- The branch supports PHP 8.1, which has no `readonly` classes.
- The test harness is the same as on `main`: `BiteJobsServiceTest` stubs
  Guzzle at handler level, `test_bitejobs_stub` answers the frontend, and the
  plugin test fixtures of ACE-677 exist here.

## Goals / Non-Goals

**Goals:**

- The same two events with the same methods as on `main`, so a listener
  written for 2.4 keeps working on 3.0.
- A stateless service.

**Non-Goals:**

- The plugin view event and the extension points page of `main`.

## Decisions

### The events are the same classes as on `main`

`ModifyBiteJobPostingsRequestEvent` and `ModifyBiteJobPostingsEvent` are copied
unchanged, including `getPluginControllerActionContext()`. They were drafted
as `ModifyBiteJobsRequestEvent` and `AfterBiteJobsFetchedEvent` on `main` and
renamed in its review: an event a listener changes something through is a
`Modify…Event`, and both names spell `BiteJobPostings` after the
`jobPostings` key of the API. The controller
builds a `PluginControllerActionContext` from its request and settings and
hands it to the service. Rejected: leaving the context out here, which would
make a listener that reads the TypoScript settings of the plugin fail on 2.4
and work on 3.0.

### `final` with `readonly` properties

The service keeps `final class` and declares its three promoted dependencies
`private readonly`. That says the same as `final readonly class` on `main` on
PHP 8.1.

### Listeners by tag

The fixture extension registers its listeners in `Services.yaml`, the
recording one with a `method` and an `event` per tag. The manual shows the
same registration, which works on v12 and v13 alike.

### A content element without a FlexForm

As on `main`, missing settings are read as empty, so a content element without
a stored FlexForm sends the request with the same empty values as before,
without the warnings it raised, and without a `TypeError` from the typed
event constructors.

### Changelog

`Documentation/Changelog/2.4/Feature-RequestAndResultEvents.rst` and
`Important-FailedRequestRendersNoJobs.rst`, as on `main`, and the same
pointer in the 2.1 breaking entry.

## Risks / Trade-offs

- [The payload array becomes public API on 2.4 already] → The same keys as on
  `main`, listed in the manual of both versions.

## Migration Plan

The project that runs a fork of 2.0.2 replaces it with
`fgtclb/academic-bite-jobs` 2.4 plus the two listeners, registered in its
`Services.yaml`, and `settings.jobs.groupBy = relationName`.

## Open Questions

None.
