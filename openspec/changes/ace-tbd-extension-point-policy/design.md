## Context

Measured on main over `packages/fgtclb/*/Classes`:

- 120 classes are `final`. The controllers of `academic_persons`,
  `academic_persons_edit`, `academic_jobs` and `academic_contacts4pages` are
  `final`; those of `academic_partners`, `academic_programs` (two),
  `academic_projects` and `academic_bite_jobs` are not.
- 13 event classes exist, all `final`: one in `academic_base`, two in
  `academic_jobs` and ten in `academic_persons`, one of which lives in
  `Classes/Service/Event/` rather than `Classes/Event/`.
- `academic_contacts4pages` and `academic_persons_edit` own no event, but they
  dispatch events of other extensions (the TCA select item event of
  `academic_base` and the profile update event of `academic_persons`).
  `academic_bite_jobs`, `academic_partners`, `academic_programs`,
  `academic_projects`, `academic_study_plan`, `academic_persons_sync` and
  `category_types` dispatch none.
- Several events are dispatched through a variable
  (`$this->eventDispatcher->dispatch($event)`), not as `dispatch(new …)`.
- `docs/architecture/class-design.md` states "final by default" and that
  extensibility comes from swapping implementations behind an interface.
  `academic_base` has no `Developers/` documentation section yet;
  `academic_persons` has one.

## Goals / Non-Goals

**Goals:**

- One integrator-facing statement of the public API, in the rendered manual.
- A contributor rule that makes new events uniform.
- A test that catches an event class that is not `final` or never used.

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

### The architecture test sits in packages-dev

The test scans `packages/fgtclb/*/Classes/` for classes whose name ends in
`Event` below any `Event/` directory, asserts `final` by reflection, and
asserts that another file under `Classes/` instantiates the class. It is
placed in a `packages-dev/` package, which the phpunit configuration already
globs, because it spans all extensions and must not travel into a split
repository, where the sibling packages do not exist.

Rejected: the test in `academic-base/Tests/Unit/`. It would be split out with
`academic_base` and find nothing there. Rejected: a custom PHPStan rule; it
needs rule infrastructure the repository does not have, for two assertions.

"Instantiated" is the deliberate proxy for "dispatched", because dispatching
through a variable hides the event class from a `dispatch(new …)` search.

### Naming applies to new events

`ChooseProfileFactoryEvent` and the persons command environment event do not
follow the `Modify…Event` / `After…Event` rule. They stay; a rename is a
breaking change with no gain for a listener.

### Changes to listed API follow the changelog rules

A change to anything the page lists needs a `Breaking-` or `Deprecation-`
changelog entry. The page says so, and the contributor rule points at it.

### Decided: `@api` on the classes, the page as the integrator's list

Every class, interface and trait the page lists carries an `@api` docblock
tag, and the page stays the list integrators read. `@api` is already the
convention in academic_base (`GetCurrentContentRecordMethodTrait`,
`GetSelectItemsForTcaManagedTableFieldMethodTrait`), and the marker sits next
to the code a contributor changes, which is where a `Breaking-` entry has to
be triggered. Rejected: the page as the only list.

### Decided: landing order single context, policy, view event

`ace-tbd-single-action-context-interface` lands first, then this change,
then `ace-tbd-generic-plugin-view-event`. The context change is small and
does not break listeners, because the persons interface extends the base
one, and the view event already depends on it. The rule can then name the
academic_base plugin action context as the one context type, without a
transitional paragraph about the persons copy.

## Risks / Trade-offs

- [The policy promises extension points that do not exist yet] → the page
  lists only what is dispatched today and names the planned ones as planned.
- [Grep-based detection misses an event created through a factory] → none
  exists today; the test message says how to register an exception.

## Open Questions

None.
