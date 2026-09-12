## Context

`packages/fgtclb/academic-contact4pages/Classes/DataProcessing/ContactsProcessor.php:21-47`
returns early unless `$cObj->currentRecord` is a `pages` record, fetches the
repository with `GeneralUtility::makeInstance()`, calls `findByPid()` without
the hidden flag and writes `contacts` and `roles` into the processed data. It
reads nothing from `$processorConfiguration`, lacks `declare(strict_types=1)`
(listed in `docs/architecture/class-design.md`) and is wired by class name at
`page.10.dataProcessing.400` in `Configuration/TypoScript/List/setup.typoscript`.

`ContactsController` is `final` and builds `roles` and
`contactsWithoutRole` itself. The partner and project processors are
registered in their `Services.yaml` with the `data.processor` tag and an
identifier (`partner-data`, `project-data`). The frontend of TYPO3 13.4.34
and 14.3.6 has no attribute that registers a data processor.

The page contacts provider is introduced by candidate `listings-02` and
exists only as a proposal. This change builds on it.

## Goals / Non-Goals

**Goals:**

- Plugin and processor produce their lists in one place.
- One extension point for both outputs.

**Non-Goals:**

- Rendering anything in the processor.
- Changing the grouping of the content element (candidate `listings-18`).

## Decisions

### The event is dispatched inside the provider

The provider dispatches `ModifyPageContactsEvent` (`final`) with
`getPageUid()`, `getContacts()`/`setContacts(list<Contact>)`, `getContext()`
returning a new backed enum `PageContactsContext` (`Plugin`,
`DataProcessor`) and `getRequest()`. `roles` and `contactsWithoutRole` are
derived after the event from the final list, so they cannot disagree with
it. The provider stays stateless and gets the `EventDispatcherInterface`
through its constructor.

Rejected: one event per output. A listener that should affect both would
register twice, and one that forgot the second is how the outputs diverged in
the first place. Rejected too: an event in the controller only, which is the
gap one project worked around.

### `as` wraps all three lists

With `as` set, the processor writes one variable of that name holding
`contacts`, `roles` and `contactsWithoutRole`. Without it, the three keys are
written at the top level as today.

Rejected: `as` renaming only `contacts`. `roles` would stay at the top level
and collide as soon as the processor is attached twice to one page object.

### Registration by identifier, class name kept working

The processor is registered in `Configuration/Services.yaml` with the
`data.processor` tag and the identifier `academic-page-contacts`, matching
the partner and project processors. It becomes `final`, gets the provider by
constructor injection and declares strict types. The shipped TypoScript
switches to the identifier. Because installations reference the class name
in their own TypoScript, the service is also `public`; a functional test
proves the class name still resolves to the injected service.

Rejected: an attribute based registration. None exists for data processors
on either supported version.

### Options

`showHiddenRecords` (default `0`) is passed to the provider like the plugin
setting of the same name. `pageUid` defaults to the current page; the
`pages` guard applies only when `pageUid` is not configured, so the processor
also works on a page object that renders another record.

## Risks / Trade-offs

- [The list passed through the event is public API] → It is typed as
  `list<Contact>`, and the setter rejects anything else.
- [Listeners run while the page is rendered and cached with it] → They
  cannot vary per visitor on a cached page. The manual says so.
- [The change depends on `listings-02`] → It is implemented after that
  change, on its provider.

## Open Questions

None.
