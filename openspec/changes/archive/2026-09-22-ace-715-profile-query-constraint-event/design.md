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

- A listener can add constraints. A constraint cannot remove an upstream
  constraint, and a listener cannot change the ordering.
- The guarantee stops there, and saying so is part of the goal. The event hands
  out the live query object, because that is what a listener builds an
  expression on; its query settings, limit and offset are read after the event
  and are not guarded. Documenting "a listener cannot widen the result" would
  be false.
- One dispatch point per finder, after the upstream constraints are known.

**Non-Goals:**

- Exposing the repository helpers.

## Decisions

### Two final events that collect constraints

`FGTCLB\AcademicPersons\Event\ModifyProfileQueryEvent` and
`ModifyContractQueryEvent` are `final` and have the same shape:

- `getQuery(): QueryInterface`, to build expressions only;
- `getDemand(): ?DemandInterface`, which is null for uid lookups;
- `getPluginControllerActionContext(): ?PluginControllerActionContextInterface`,
  typed against the `academic_base` interface (see below), null when a caller
  outside the plugins passes none;
- `addConstraint(ConstraintInterface)` and `getConstraints(): list<ConstraintInterface>`.

**Amended during implementation: the accessor is
`getPluginControllerActionContext()`, not `getContext()`.** The short name was
chosen on the premise that every event spelling it the long way returns the
**`academic_persons`** interface, so that one name would have covered two
return types. That premise is false: `academic_jobs`'
`ModifyJobControllerNewActionViewEvent` spells it
`getPluginControllerActionContext()` and returns the **`academic_base`**
interface (`Classes/Event/ModifyJobControllerNewActionViewEvent.php:7`, `:22`) -
exactly the combination these two events need. The monorepo convention is
therefore "the long name, returning whichever context interface the extension
uses", and the six persons events are the ones that will change type when
`ace-tbd-single-action-context-interface` retires their interface. Diverging
here would have left a short name behind forever for a distinction that is
temporary.

`ace-tbd-generic-plugin-view-event` had already rejected `getContext()` on the
same evidence, in its own `design.md`: "named like the one of the jobs event ...
two names for the same thing in one code base cost more than they save". Two
changes re-derived this independently and one of them got it wrong first; the
cross-reference is here so a third does not have to.

**Amended during implementation: `ModifyContractQueryEvent` has no
`getDemand()`.** The only demand this extension has is `ProfileDemand` behind
`DemandInterface`, which describes a profile list. A contract query is only
ever the uid lookup of the selected-contracts plugin, so the method would
return null on every dispatch for the rest of its life, and its type would
describe profiles on a contract event. "The same shape" was about how a
listener collects constraints, and that is unchanged.

The repository dispatches after it has computed its own constraints. It then
calls `matching()` once with the `logicalAnd()` of its own constraints and the
collected ones, and calls `setOrderings()` afterwards, so an ordering a
listener set is overwritten.

**Amended during implementation: a constraint a listener set with `matching()`
is folded in, not dropped.** "It is overwritten too" was the plan and it does
not hold up. `matching()` is only called when there is something to apply, so a
guard leaves the listener's constraint standing wherever this repository has
none of its own - the plain list, the most common query the plugin makes.
Dropping it needs `matching(null)`, which works at runtime on both versions but
is typed `ConstraintInterface` in core's docblock, so phpstan refuses it and a
core that adds the type would break it. So the repository reads
`$query->getConstraint()` after the dispatch - `QueryInterface` declares it on
both versions, and nothing else has written to it yet - and appends it. The
narrowing guarantee is unchanged, and `addConstraint()` stays the documented
way because `matching()` replaces while `addConstraint()` collects.

Rejected:

- Un-finalising `ProfileController` and making the helpers `protected`.
  Projects copy them again, as one analysed project already did, and regress
  the ACE-341 and ACE-482 fixes.
- `ModifyProfileDemandEvent` alone. The repository ignores unknown demand
  properties.
- Post-query filtering in `ModifyListProfilesEvent`, which one project fell
  back to. It breaks pagination and counts.

### Dispatch in both branches of `findByDemand()`

`applyDemandForQuery()` returns the constraint instead of applying it - and is
renamed to `resolveDemandForQuery()` for it, since it applies nothing any more;
that applies to the `profileList` branch too. The event therefore narrows a manual
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
therefore get an additive context-aware finder next to `findByUids()`.
`findByUids()` delegates to it without a context, and the controller calls the
new finder.

**Settled during implementation:**
`findByUidsWithContext(array $uids, ?PluginControllerActionContextInterface $context, bool $showHidden = false)`
on both repositories. The context parameter is nullable and has no default:
`findByUids()` has to be able to pass none, and a caller of the new finder has
to decide rather than inherit a default that makes the two identical.

Two changes claimed the same third parameter slot for different values, and
any change of it fatally breaks the repository XCLASSes that projects carry
over from 2.x.

### Event dispatcher for `ContractRepository`

**Corrected during implementation: nothing had to be added.** Extbase's own
`Repository` already declares `protected EventDispatcherInterface $eventDispatcher`
and `injectEventDispatcher()`, and uses them itself (v13 `Repository.php:37`,
`:64`, `:370`; v14 `:36`, `:63`, `:317`). `ProfileRepository` redeclares both on
`main`, which is where the assumption came from; that redundancy is pre-existing
and left alone. `ContractRepository` uses the inherited property and adds
nothing.

### Listener registration

Listeners register with TYPO3's `#[AsEventListener]`
(`TYPO3\CMS\Core\Attribute\AsEventListener`). The fixture extension does
exactly that, so the documented way is the tested way.

### Decided: a context-less backport to branch `2`

The two events are backported to branch `2` as an additive feature, released
with 2.4.0: the same class names and methods, without
`getPluginControllerActionContext()`, and
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

## Measured, not recalled

Every statement of *Context* above was re-checked against `main` at
`211a68c53` before anything was written. They all hold; only the line numbers
had moved, `ProfileRepository` having grown to 392 lines. What else was
measured, before and during the implementation:

- The card plugin reaches the profiles through `findByDemand()` with a
  `profileList` demand, not through `findByUids()` — so the profile event
  covers it through the demand finder, as the proposal says, and no separate
  dispatch is needed.
- `Query::logicalAnd()` pads a one-element list with an always-true
  `uid > 0` comparison on both v13 and v14. A single collected constraint is
  therefore passed to `matching()` unwrapped.
- **A listener's own `matching()` is not overwritten by a guarded call**, which
  is what sent the contract above to be re-decided. The test written for the
  documented behaviour is what found it; nothing else would have.
- The branch that separates the three candidate implementations is a listener
  using `matching()` **while the content element carries a filter of its own**.
  On the organisational unit fixture they render three different lists -
  folded in gives Anna, dropping it gives Anna and Max, leaving the listener's
  call standing gives Anna and Clara - and the test asserts the first, so all
  three are distinguishable. A plain list cannot tell dropping from folding
  apart, because there is nothing to combine with.
- `QueryInterface::getConstraint()` is declared on both versions, at
  `QueryInterface.php:386` in each, which is what makes folding in a typed
  option rather than a cast.

## Open Questions

None.
