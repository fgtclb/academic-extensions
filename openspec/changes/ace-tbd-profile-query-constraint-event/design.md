## Context

The current state:

- `ProfileController` is `final` (`Classes/Controller/ProfileController.php:39`).
- `ProfileRepository::findByDemand()` dispatches only `ModifyProfileDemandEvent`
  (`Classes/Domain/Repository/ProfileRepository.php:73`). The helpers that build
  the query are `private` (`:82`, `:171`, `:193`, `:208`, `:228`, `:254`).
- `ProfileRepository::findByUids()` (`:275-290`) and
  `ContractRepository::findByUids()` (`Classes/Domain/Repository/ContractRepository.php:110`)
  dispatch nothing, and `ContractRepository` has no event dispatcher.
- `ModifyListProfilesEvent` runs after the query and hands out a
  `QueryResultInterface` (`Classes/Event/ModifyListProfilesEvent.php`).

The controller already builds a `PluginControllerActionContext` for its events
(`ProfileController.php:78`, `:251`, `:304`, `:335`); its interface lives in
`Classes/Domain/Model/Dto/PluginControllerActionContextInterface.php`.
`academic_base` ships its own `PluginControllerActionContextInterface`
(`academic-base/Classes/Domain/Model/Dto/`), the persons one plus
`getContentObjectRenderer()`.

Callers of the three finders in the mono repository: `ProfileController` only
(`:72`, `:198`, `:298`, `:329`). No subclass exists here.

## Goals / Non-Goals

**Goals:**

- A listener can add constraints. It cannot remove upstream constraints, and
  it cannot change orderings.
- One dispatch point per finder, after the upstream constraints are known.

**Non-Goals:**

- Exposing the repository helpers.

## Decisions

### Two final events that collect constraints

`FGTCLB\AcademicPersons\Event\ModifyProfileQueryEvent` and
`ModifyContractQueryEvent` are `final` and have the same shape:

- `getQuery(): QueryInterface`, to build expressions only;
- `getDemand(): ?DemandInterface`, which is null for uid lookups;
- `getContext(): ?PluginControllerActionContextInterface`, typed against the
  `academic_base` interface (see below), null when a caller outside the
  plugins passes none;
- `addConstraint(ConstraintInterface)` and `getConstraints(): list<ConstraintInterface>`.

The repository dispatches after it has computed its own constraints. It then
calls `matching()` once with the `logicalAnd()` of its own constraints and the
collected ones, and calls `setOrderings()` afterwards. A listener that calls
`matching()` or `setOrderings()` on the query itself is therefore overwritten.
That is documented, not guarded.

Rejected:

- Un-finalising `ProfileController` and making the helpers `protected`.
  Projects copy them again, as one analysed project already did, and regress
  the ACE-341 and ACE-482 fixes.
- `ModifyProfileDemandEvent` alone. The repository ignores unknown demand
  properties.
- Post-query filtering in `ModifyListProfilesEvent`, which one project fell
  back to. It breaks pagination and counts.

### Dispatch in both branches of `findByDemand()`

`applyDemandForQuery()` returns the constraint instead of applying it; that
applies to the `profileList` branch too. The event therefore narrows a manual
selection as well, which is what a consent listener needs. The uid lookups of
both repositories dispatch with a null demand.

### Decided: the context is the `academic_base` interface

Both events and the new repository parameters are typed against
`FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface`.
Until `ace-tbd-single-action-context-interface` lets the persons interface
extend it, the controller builds an `academic_base` context for these calls,
as `ace-tbd-generic-plugin-view-event` already does in the persons actions.

The base interface is the persons one plus `getContentObjectRenderer()`, and
the persons copy is deprecated for 4.0 by the single context interface change.
New API typed on the persons interface would need a breaking change in 4.0.

### The context on `findByDemand()` as an optional trailing parameter

`findByDemand(DemandInterface $demand, ?PluginControllerActionContextInterface $context = null)`.
The controller passes the context. It is the same class of change 3.0 made
with `$showHidden`, and callers are unaffected. It is **breaking** for
overriding subclasses and XCLASSes.

Rejected: a setter on the repository. That would be state on a shared service
(`docs/architecture/dependency-injection.md`).

### Decided: no signature change of the uid lookups

`findByUids(array $uids, bool $showHidden = false)` keeps its signature on
both repositories, and no other change claims its third parameter either. The
consent filter of `ace-tbd-public-display-consent` adds its condition through
these events instead of a parameter of its own.

The context still has to reach the uid lookups, because a listener such as the
consent filter reads the content element's settings. Both repositories
therefore get an additive context-aware finder next to `findByUids()`, for
example `findByUidsWithContext(array $uids, PluginControllerActionContextInterface $context, bool $showHidden = false)`;
the exact name is settled in the implementation. `findByUids()` delegates to
it without a context, and the controller calls the new finder.

Two changes claimed the same third parameter slot for different values, and
any change of it fatally breaks the repository XCLASSes that projects carry
over from 2.x.

### Event dispatcher for `ContractRepository`

The dispatcher comes through `injectEventDispatcher()`, as in `ProfileRepository`.
Extbase repositories are constructed by the persistence layer, and the
surrounding code already uses this style.

### Listener registration

Listeners register with TYPO3's `#[AsEventListener]`
(`TYPO3\CMS\Core\Attribute\AsEventListener`). The fixture extension does
exactly that, so the documented way is the tested way.

### Decided: a context-less backport to branch `2`

The two events are backported to branch `2` as an additive feature, released
with 2.4.0: the same class names and methods, without `getContext()`, and
without any repository signature change. The backport is a separate change on
branch `2`, derived from its own backport analysis.

Two analysed projects are held on 2.x by a controller copy and repository
XCLASSes, and one of them filters after the query, which breaks pagination.
With the backport both can move to listeners before the 3.0 upgrade, and a
listener that ignores the context runs unchanged on `main`.

## Risks / Trade-offs

- A listener adds an expensive join. → Its own responsibility; the
  documentation says the constraint runs on every list rendering.
- The Extbase query object becomes public API of the event. → It is the
  object listeners need to build a constraint, and core events expose query
  builders the same way.
- An XCLASS that overrides `findByUids()` still loads, but the plugins now
  call the context-aware finder, so its override no longer affects the
  selected-profiles and selected-contracts plugins. → The `Breaking-*.rst`
  names this and points to the events as the replacement.

## Migration Plan

Subclasses and XCLASSes of `ProfileRepository::findByDemand()` add the
trailing parameter. Overrides of `findByUids()` move to a listener. The two
projects that copied the controller replace their copies with listeners. The
`Breaking-*.rst` lists the changed signature and the calls that moved to the
context-aware finders.

## Open Questions

None.
