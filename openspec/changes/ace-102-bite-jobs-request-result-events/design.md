## Context

`packages/fgtclb/academic-bite-jobs/Classes/Services/BiteJobsService.php` is
`final` with no interface and no event. `fetchBiteJobs()` reads the plugin
FlexForm of the current content element through `Core\Service\FlexFormService`
(`:33-39`), JSON encodes a fixed payload (`:44-56`: `key`, `channel` 0,
`locale` `de`, `page.offset` 0, empty `filter`, `sort`), posts it and stores
the decoded body in the property `$responseBody` (`:21`, `:66`). On an
exception it only logs, then reads `$this->responseBody` again (`:73`), which
still holds the previous call's response on a shared instance. The limit is
applied last (`:77-79`). The `@return string[]` annotation is wrong; the
method returns decoded postings.

`Documentation/Changelog/2.1/Breaking-RemoveProjectSpecificCustomFields.rst`
removed the custom-field code. Its migration section no longer reads
`[TODO]`: ACE-677 (`listings-04`) rewrote it to describe the upgrade wizard
and the TypoScript grouping, so this change adds a pointer to the events
there instead of replacing a placeholder.
`Tests/Functional/Services/BiteJobsServiceTest.php` stubs Guzzle at handler
level and captures the handled request, because `RequestFactory` and
`GuzzleClientFactory` are `readonly` on v14 and not on v13.

## Goals / Non-Goals

**Goals:**

- A stateless service with two extension points.
- The forking project's filter and grouping become possible as two listeners.

**Non-Goals:**

- Migrating `FlexFormService`. It is a v15 blocker whose replacement does not
  exist on v13 (`AGENTS.md`).

## Decisions

### Stateless service

The decoded body becomes a local variable, and the class becomes `final
readonly`, since no property is left that changes. The dispatcher is added
by constructor injection; `Configuration/Services.yaml` already autowires
the package.

### `ModifyBiteJobPostingsRequestEvent` carries the whole payload

Drafted as `ModifyBiteJobsRequestEvent`. The review asked for one spelling of
the two names, which are frozen once 3.0.0 is released, so both say
`BiteJobPostings`, after the `jobPostings` key of the API.

`final`, with `getPayload()`/`setPayload(array)`, `getSettings()` (the
`settings.jobs` array of the FlexForm), `getRequest()` and
`getPluginControllerActionContext()`. It is dispatched after the payload is
built and before it is encoded.

The plugin context was not in the first draft. The class design rule of
ACE-767 says an event a plugin action dispatches carries the context of
`academic_base`, and the TypoScript settings of the plugin (the grouping
field, for example) only reach a listener through it, since the service reads
the FlexForm alone. `BiteJobsController::listAction()` builds its context
once and hands the same object to the service and to the plugin view event.
The service takes it as an optional second argument, so both events declare it
nullable, as `ModifyPageContactsEvent` does for its data processor path.

The payload is encoded inside the existing `try`, so a payload a listener
made unencodable is logged like a failed request and never sent. With
`JSON_THROW_ON_ERROR` the log names the JSON error. Without it Guzzle would
refuse the `false` body with an error about its type, which says nothing
about the cause.

Rejected: one setter per payload key. The B-ITE API has more keys than the
extension sends today, and a listener has to be able to send those too.
Rejected: generic FlexForm fields for a custom field and its values. B-ITE
custom fields differ per customer, and their labels come from the options
API, which a listener can call and a FlexForm cannot express.

### `ModifyBiteJobPostingsEvent` sees the postings before the limit

The first draft called it `AfterBiteJobsFetchedEvent` with `getJobs()` and
`setJobs()`. The review renamed it: `class-design.md` reserves `After…Event`
for an event that announces something, and names an event a listener changes
something through `Modify…Event`. The getters follow the `jobPostings` key of
the API and the word the documentation uses.

`final`, with `getJobPostings()`/`setJobPostings(list<array<string, mixed>>)`,
`getResponseData()` (the decoded body, `[]` on failure or when the body is
not JSON), `getSettings()`, `getRequest()` and
`getPluginControllerActionContext()`. `setJobPostings()` refuses anything but a list
of arrays (codes 1790774355, 1790774356), because the templates render the
list as it is. The postings handed to the event are the array entries of
`jobPostings`, re-indexed. A non-array entry, which the templates could not
render either, is dropped.

It is dispatched on every call, also after a failed request with an empty
list, so a listener never has to know about the failure path. The limit is
applied to what the listener returns, so a grouping listener sees every
posting.

### What `getSettings()` returns

Both events return the values stored below `settings.jobs` in the FlexForm,
as the service reads them: not merged with TypoScript and not normalised. The
docblocks and the manual say so, and point to the plugin action context for
the settings the plugin works with. A later switch to merged settings, for
example with the migration of `FlexFormService`, is then visibly a breaking
change.

### A content element without a FlexForm

The first draft left the `Undefined array key` warnings of a content element
without a stored FlexForm as a defect of its own. Passing the missing settings
on to the typed event constructors would have turned those warnings into a
`TypeError`, so the service now reads missing settings as empty and sends the
request with the same empty values as before, without a warning.

### Migration of the forking project

The documentation shows the two listeners: one adds the `custom.zuordnung`
filter to the payload, one writes a `relationName` into every posting that
the grouping of candidate `listings-04` groups by. They are examples in the
manual, not shipped code.

They live in a new `Documentation/Developers/` chapter rather than in
`Documentation/Configuration/`, the shape the other extensions with events
use and the one the extension points page of `academic_base` links to. The
example reads the filter value from a FlexForm field of the project named
`settings.jobs.relation`, because the upgrade wizard of ACE-677 removes the
old `settings.jobs.custom.zuordnung` from every job list.

### A failed request is an Important changelog entry

A page with two job lists, whose second request fails, renders "no jobs"
after the update instead of the postings of the first list. That is a change
of the rendered output of an installation that changed nothing, so it gets
`Important-FailedRequestRendersNoJobs.rst` next to the Feature entry.

### Decided: ACE-102 is the issue of this change

ACE-102 stays, ACE-154 is closed as its duplicate, and the change references
only ACE-102 and is named `ace-102-bite-jobs-request-result-events`. One
check precedes the rename: if ACE-40, the parent of ACE-154, turns out to be
the epic of `academic_bite_jobs`, the issue under that epic stays instead.

Checked on 2026-09-30: ACE-40 is the epic "Academic Jobs" of
`academic_jobs`, ACE-41 "Academic Jobs b-ite" is the one of this extension
and the parent of ACE-102. ACE-102 stays, ACE-154 is closed as its duplicate.

Both issues ask for the custom-field API the 2.1 breaking note promised, with
identical text; the older one is the natural survivor.

## Risks / Trade-offs

- [The payload array becomes public API] → The manual lists every key with
  its meaning; later changes to the keys are breaking and documented as such.
- [An exception in a listener reaches the visitor] → Not caught; a listener
  is project code and should fail loudly during development.

## Migration Plan

The project that runs a fork of 2.0.2 replaces it with
`fgtclb/academic-bite-jobs` plus the two listeners, and
`settings.jobs.groupBy = relationName` once `listings-04` is merged.

## Open Questions

None.
