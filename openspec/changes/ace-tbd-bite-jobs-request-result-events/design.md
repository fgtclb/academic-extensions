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
removed the custom-field code and still carries `[TODO]` as its migration.
`Tests/Functional/Services/BiteJobsServiceTest.php` stubs Guzzle at handler
level and captures the handled request, because `RequestFactory` and
`GuzzleClientFactory` are `readonly` on v14 and not on v13.

## Goals / Non-Goals

**Goals:**

- A stateless service with two extension points.
- The forking project's filter and grouping become possible as two listeners.

**Non-Goals:**

- Migrating `FlexFormService`; it is a v15 blocker whose replacement does not
  exist on v13 (`AGENTS.md`).
- Guarding the missing-FlexForm warnings the test class documents; that is a
  defect of its own.

## Decisions

### Stateless service

The decoded body becomes a local variable, and the class becomes `final
readonly`, since no property is left that changes. The dispatcher is added
by constructor injection; `Configuration/Services.yaml` already autowires
the package.

### `ModifyBiteJobsRequestEvent` carries the whole payload

`final`, with `getPayload()`/`setPayload(array)`, `getSettings()` (the
`settings.jobs` array of the FlexForm) and `getRequest()`. It is dispatched
after the payload is built and before it is encoded.

Rejected: one setter per payload key. The B-ITE API has more keys than the
extension sends today, and a listener has to be able to send those too.
Rejected: generic FlexForm fields for a custom field and its values. B-ITE
custom fields differ per customer, and their labels come from the options
API, which a listener can call and a FlexForm cannot express.

### `AfterBiteJobsFetchedEvent` sees the postings before the limit

`final`, with `getJobs()`/`setJobs(list<array<string, mixed>>)`,
`getResponseData()` (the decoded body, `[]` on failure) and `getSettings()`.
It is dispatched on every call, also after a failed request with an empty
list, so a listener never has to know about the failure path. The limit is
applied to what the listener returns, so a grouping listener sees every
posting.

### Migration of the forking project

The documentation shows the two listeners: one adds the `custom.zuordnung`
filter to the payload, one writes a `relationName` into every posting that
the grouping of candidate `listings-04` groups by. They are examples in the
manual, not shipped code.

### Decided: ACE-102 is the issue of this change

ACE-102 stays, ACE-154 is closed as its duplicate, and the change references
only ACE-102 and is named `ace-102-bite-jobs-request-result-events`. One
check precedes the rename: if ACE-40, the parent of ACE-154, turns out to be
the epic of `academic_bite_jobs`, the issue under that epic stays instead.

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
