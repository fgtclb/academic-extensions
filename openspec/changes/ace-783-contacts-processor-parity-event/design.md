## Context

The change was written on 2026-09-12, before ACE-101 was merged. Checked
against `main` again before implementing, ACE-101 has already done part of
what this change planned:

- `PageContactsProvider` (`final readonly`) exists and returns a
  `PageContacts` value object with `contacts`, `roles` and
  `contactsWithoutRole`. It drops contacts whose contract or profile does not
  resolve.
- `ContactsController` (`final`) asks the provider and assigns all three
  lists. It then hands the `AddressRecordProvider` to every contact while
  "show hidden records" is on, so that hidden address records render.
- `ContactsProcessor` declares strict types, gets the provider by
  constructor injection and is a public service through
  `#[Autoconfigure(public: true)]`, because the shipped TypoScript names it by
  class name at `page.10.dataProcessing.400`. It is not `final` and carries
  `@api`.

What still holds from the original premise: the processor ignores its
`$processorConfiguration`, never shows hidden contacts, always reads the
current page, returns early for anything but a `pages` record, and writes
only `contacts` and `roles`. Nothing dispatches an event for the contacts of
a page.

The frontend of TYPO3 13.4.34 and 14.3.7 looks a data processor up by the
`identifier` of its `data.processor` tag first (`DataProcessorRegistry`),
then by service id, and instantiates the class itself only when the
container does not know the name (`ContentDataProcessor::process()` and
`getDataProcessor()`). Neither version ships a TYPO3 attribute for data
processors.

## Goals / Non-Goals

**Goals:**

- Plugin and processor produce their lists in one place.
- One extension point for both outputs.
- `showHiddenRecords` means the same for both outputs.

**Non-Goals:**

- Rendering anything in the processor.
- Changing the grouping of the content element (candidate `listings-18`).

## Decisions

### The event is dispatched inside the provider

The provider dispatches `ModifyPageContactsEvent` (`final`, `@api`) with
`getPageUid()`, `getContacts()`/`setContacts(list<Contact>)`,
`getOutput()`, `getRequest()` and `getPluginControllerActionContext()`. The
contacts the listeners see are the resolvable ones. `roles` and
`contactsWithoutRole` are derived after the event from the final list, so
they cannot disagree with it. A contact a listener adds is rendered as it
is, exactly as a replaced result of the list plugin events is. The provider
stays stateless and gets the `EventDispatcherInterface` through its
constructor.

`getOutput()` returns the backed enum `PageContactsOutput` (`Plugin`,
`DataProcessor`), in `Classes/Event/` next to the event, as
`ProfileUpdateOrigin` of `academic_persons` is.

The original design named the enum `PageContactsContext` and its getter
`getContext()`. Renamed, because `docs/architecture/class-design.md`
requires every event a plugin action dispatches to carry the plugin action
context of `academic_base` through `getPluginControllerActionContext()`
(ACE-767), and two getters called "context" on one event would be read as
the same thing. The plugin hands its one context object in, the processor
has none and passes `null`.

Rejected: one event per output. A listener that should affect both would
register twice, and one that forgot the second is how the outputs diverged in
the first place. Rejected too: an event in the controller only, which is the
gap one project worked around.

### Hidden address records follow the provider, on copies

The controller hands the `AddressRecordProvider` to the contacts while "show
hidden records" is on, and resets it otherwise. That moves into the provider,
after the event, so the processor option `showHiddenRecords` shows hidden
address records exactly as the plugin option does, and a contact a listener
added gets the same treatment.

The provider sets it on shallow copies of the contacts, not on the contacts
the listeners handed back. Extbase hands every output of a request the same
contact objects, and Fluid reads the address records only when it prints
them. A page template that renders a content element before it prints its own
contacts would otherwise print them with the option of the content element,
and a processor attached twice would print both with the option of the second.
Resetting the option after each call does not help against the first case.
The contract, profile and role behind a copy stay shared, so a listener
changes the list and not the objects in it.

### `as` wraps all three lists

With `as` set, the processor writes one variable of that name holding
`contacts`, `roles` and `contactsWithoutRole`. Without it, the three keys are
written at the top level, `contacts` and `roles` as before.

Rejected: `as` renaming only `contacts`. `roles` would stay at the top level
and collide as soon as the processor is attached twice to one page object.

### Registration by identifier, class name kept working

The processor is tagged `data.processor` with the identifier
`academic-page-contacts` in `Configuration/Services.yaml`, as the partner and
project processors are. The shipped TypoScript switches to the identifier.
Installations reference the class name in their own TypoScript, so the
service stays public through the `#[Autoconfigure(public: true)]` it already
has, and a functional test proves the class name still resolves to the
injected service.

Rejected: Symfony's `#[AutoconfigureTag]` on the class. It is registered as an
`_instanceof` rule, so a project's subclass would carry the same identifier,
and of two services with one identifier the tagged locator silently keeps the
first.

The processor does not become `final`, as the original design said, and
`process()` keeps its signature without a return type. The 3.0 changelog of
ACE-101 describes how a project subclasses it, and a final class or a return
type a subclass does not declare would turn that into a fatal error without a
breaking entry. The event is the way to change the contacts without a
subclass.

### Options

`as`, `showHiddenRecords` (default `0`) and `pageUid` (default the current
page) are read with `stdWrap`, as the core data processors read theirs.
`showHiddenRecords` is passed to the provider like the plugin setting of the
same name. The `pages` guard applies only when `pageUid` is not configured
to a positive number, so the processor also works on a page object that
renders another record.

## Risks / Trade-offs

- [The list passed through the event is public API] → It is typed as
  `list<Contact>`, and the setter rejects anything else.
- [Listeners run while the page is rendered and cached with it] → They
  cannot vary per visitor on a cached page. The manual says so.
- [The provider signature changes] → The provider is not listed on the
  extension points page, so it is not API. Its callers are the controller,
  the processor and the tests.
- [A listener mutates a shared object] → The manual says to change the list
  and not the objects in it.

## Open Questions

None.
