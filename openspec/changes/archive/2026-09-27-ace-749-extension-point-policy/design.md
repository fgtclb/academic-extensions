## Context

Measured on main (`56917440c`) over `packages/fgtclb/*/Classes`:

- 166 of 283 classes are `final`. The controllers of `academic_persons`,
  `academic_persons_edit`, `academic_jobs` and `academic_contacts4pages` are
  `final`; those of `academic_bite_jobs`, `academic_partners`,
  `academic_programs` (two) and `academic_projects` are not.
- 19 event classes exist, all `final`: one in `academic_base`, two each in
  `academic_jobs`, `academic_partners` and `academic_projects`, and twelve in
  `academic_persons`, one of which lives in `Classes/Service/Event/` rather
  than `Classes/Event/`. Each is instantiated by production code.
- `academic_contacts4pages` and `academic_persons_edit` own no event, but they
  dispatch events of other extensions (the TCA select item event of
  `academic_base` and the profile update event of `academic_persons`).
  `academic_bite_jobs`, `academic_programs`, `academic_study_plan`,
  `academic_persons_sync` and `category_types` dispatch none.
- Several events are dispatched through a variable
  (`$this->eventDispatcher->dispatch($event)`), not as `dispatch(new …)`.
- Two traits of `academic_base` carry `@api`; nothing else does. 77 files
  carry `@internal`.
- 11 interfaces: two of them are `@internal` (the record synchroniser and the
  profile form data factory), one is the deprecated persons copy of the plugin
  action context interface (ACE-442, removed in 4.0 by ACE-747).
- 19 classes extend the Extbase `AbstractEntity`; `category_types` models its
  categories, types and groups as plain classes under `Domain/Model/`.
- `docs/architecture/class-design.md` states "final by default" and that
  extensibility comes from swapping implementations behind an interface.
  `academic_base` has no `Developers/` documentation section yet;
  `academic_persons`, `academic_partners`, `academic_projects` and
  `category_types` have one.

## Goals / Non-Goals

**Goals:**

- One integrator-facing statement of the public API, in the rendered manual.
- A contributor rule that makes new events uniform.
- A test that catches an event class that is not `final` or never used, and a
  page and tags that drift apart.
- An upgrade check that agrees with the page about extending a model.

**Non-Goals:**

- Enforcing the minimum set of extension points by test; the families' own
  changes add the events and their tests.

## Decisions

### The page lives in academic_base

Every academic extension requires `academic_base`, so its manual is the one
place all of them can link. Rejected: one page per extension, twelve copies
that drift. Rejected: `docs/` only, which integrators do not read; they read
the manual on docs.typo3.org.

### The page lists events with their dispatch location

Each listed event names the extension, the action or command that dispatches
it, and what a listener may change. Before an event is listed, its dispatch
is verified in the source. That is the ACE-445 lesson turned into a writing
rule.

### Decided: only what the page lists is API

The page is a whitelist. Everything it does not list may change in any
release, whether the class is `final` or not. `@internal` makes that visible
in the code; the absence of `@api` says the same for the rest. An XCLASS or a
subclass is unsupported for every class but a domain model, listed or not: a
listed class keeps what it promises to a caller, not what it offers to a
subclass. The five controllers that are not `final` yet are named explicitly:
they stay open only so that existing subclasses keep working until 4.0, and
the page points at the events that replace subclassing them. Rejected:
declaring only `final` and `@internal` classes as not API, which leaves every
other open class in a grey zone.

### Decided: domain models are API, repositories are not

The domain models are listed and tagged: their public getters are what
templates read, and extending a model through the Extbase class mapping to add
fields is supported, so a getter that is removed or renamed needs a
`Breaking-` entry. That covers the 19 Extbase entities and the category and
category type of `category_types`. Its `CategoryTypeGroup` is not listed:
nothing creates or reads it. Repositories are not API; the
demand and query events replace an XCLASS of them. Rejected: neither, which
contradicts what every template relies on. Rejected: both, which freezes
repository signatures and works against the events.

### Decided: the types an event hands over are API, and five interfaces

A listener types against whatever an event's methods declare, so those types
are listed and tagged with the event: the plugin action context interface of
`academic_base` (and the persons copy while it exists), the persons demand
interface and profile demand, the partner and project demands, the profile
factory interface, the category collection and the enums
`ProfileUpdateOrigin`, `ProfileActionType` and `FlashMessageCreationMode`, and
the filter collection the partner and project demands carry. Classes of core
or of another package are not listed. The interfaces are listed too:
`GetCategoryCollectionInterface`, `ProfileRichTextSanitizerInterface`,
`TypesInterface`, `DemandValuesInterface` and `ProgramFactsSourceInterface`.
The abstract base classes behind them are not. The list, the type and the
demand value interfaces cannot be swapped: the extension resolves those lists
by their own class names, and the page says so.

### The classes a manual already tells projects to use are API

Seven classes are named in project configuration or injected into project code
by the manuals already: the icon provider of `academic_base`, the contacts
data processor, the category type items provider, registry and page summary
renderer of `category_types`, the frontend user profile mapper and the write
correlation of `academic_persons`. A whitelist without them would call
documented usage unsupported, so they are listed as services a project names,
to use and never to subclass or replace. The persons command environment event
stays `@internal`, as its author marked it, and is named on the page as not
listed; no class carries `@api` and `@internal` at once.

### Decided: the upgrade check reports an XCLASS of a model as a notice

Extbase creates a model through `GeneralUtility::getClassName()`, so a project
adds fields to a model by registering a subclass as its XCLASS and mapping the
new columns in its own `Configuration/Extbase/Persistence/Classes.php`. That
is what the page now calls supported, and `academic:upgrade:check` reported
exactly that as a warning, "none of the academic extensions is an API for
subclassing", with exit status 1. The configuration check now reports an
XCLASS of a class implementing Extbase's `DomainObjectInterface` as a notice:
it is still listed, so an upgrade shows which models a project extends, and it
does not fail the run. The final and the removed class keep their error, every
other class its warning. Rejected: leaving the check alone, which contradicts
the page. Rejected: declaring models API only for reading, which leaves the
projects that extend them without a supported way.

The architecture test asserts that every class implementing
`DomainObjectInterface` carries `@api`, so the notice and the page cannot
disagree about which classes are models.

### The architecture test sits in packages-dev

The test scans `packages/fgtclb/*/Classes/` for classes whose name ends in
`Event` below any `Event/` directory, asserts `final` by reflection, and
asserts that another file under `Classes/` instantiates the class. A second
test collects the classes whose declaration carries `@api` and the
`\FGTCLB\…` class names of the page, and asserts that both sets are equal; the
page therefore names non-API classes without their namespace. The tests are
placed in `packages-dev/monorepo-shared`, which the phpunit configuration
already globs and which holds the other repository-wide checks, because they
span all extensions and must not travel into a split repository, where the
sibling packages do not exist.

Rejected: the test in `academic-base/Tests/Unit/`. It would be split out with
`academic_base` and find nothing there. Rejected: a custom PHPStan rule; it
needs rule infrastructure the repository does not have, for two assertions.

"Instantiated" is the deliberate proxy for "dispatched", because dispatching
through a variable hides the event class from a `dispatch(new …)` search.

### Naming applies to new events

`ChooseProfileFactoryEvent` does not follow the `Modify…Event` /
`After…Event` rule, the persons command environment event lives in
`Classes/Service/Event/`, and `AfterSaveJobEvent` lets a listener change the
redirect and the message that follow the save. All three stay; a rename or a
move is a breaking change with no gain for a listener.

### Changes to listed API follow the changelog rules

A change that breaks or deprecates anything the page lists needs a `Breaking-`
or `Deprecation-` changelog entry. The page says so, and the contributor rule
points at it.

### Decided: `@api` on the classes, the page as the integrator's list

Every class, interface, trait and enum the page lists carries an `@api`
docblock tag, and the page stays the list integrators read. `@api` is already
the convention in academic_base (`GetCurrentContentRecordMethodTrait`,
`GetSelectItemsForTcaManagedTableFieldMethodTrait`), and the marker sits next
to the code a contributor changes, which is where a `Breaking-` entry has to
be triggered. Rejected: the page as the only list.

### Decided: landing order single context, policy, view event

`ace-442-single-action-context-interface` landed first (ACE-442, merged on
2026-09-26), then this change, then `ace-tbd-generic-plugin-view-event`. The
rule names the academic_base plugin action context as the one context type;
the persons copy is listed as deprecated API until 4.0.

### The commit is a task

The change adds a test and docblock tags next to the documentation, so the
commit is `[TASK]`, not `[DOCS]`.

## Risks / Trade-offs

- [The policy promises extension points that do not exist yet] → the page
  lists only what is dispatched today and names the planned ones as planned.
- [Grep-based detection misses an event created through a factory] → none
  exists today; the test message says how to register an exception.
- [A whitelist declares code projects use today as unsupported] → nothing
  changes for them in 3.0; the five open controllers stay open, and the page
  names the events to move to.

## Open Questions

None.
