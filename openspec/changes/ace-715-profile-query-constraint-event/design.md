## Context

The backport analysis, per `docs/workflow/backporting.md`. Measured against
`main` at `211a68c53` (the state ACE-715 was written on) and this branch at
`c15bb2c7e`.

**The three touched files, diffed between the branches:**

| File                                               | Differs between the branches | Does the difference touch this change? |
|----------------------------------------------------|------------------------------|----------------------------------------|
| `Classes/Domain/Repository/ProfileRepository.php`  | yes, in one method           | no                                     |
| `Classes/Domain/Repository/ContractRepository.php` | yes, by one method           | no                                     |
| `Classes/Controller/ProfileController.php`         | yes, in several places       | not touched at all here                |

- `ProfileRepository` differs **only** in the enable field mechanism of the
  synchronisation: `includeRestrictedRecordsForSynchronization()`, the
  `SYNCHRONIZATION_IGNORED_ENABLE_FIELDS` constant it reads, and the
  `$tcaSchemaFactory` property with its inject method that `main` added for it.
  The columns are read from `$GLOBALS['TCA']` here and through `TcaSchemaFactory`
  there, because the TCA schema API does not exist on v12. This change touches
  none of the four. `findByDemand()`, `applyDemandForQuery()`, `setFilters()`,
  `matchSelectedUidsAcrossLanguages()` and `findByUids()` are byte-identical
  on the two branches.
- `ContractRepository` differs by `findByProfileIncludingHidden()`, which
  exists only on `main` (the frontend editor of 3.0 needs it). `findByUids()`
  is byte-identical.
- `ProfileController` differs in several places, and **none of them matter**:
  the context-less form of this change does not touch the controller.

So the repository rework ports unchanged, and the part that does not port is
the part that needed the controller.

**The API check**, measured against TYPO3 v12.4.45 and v13.4.34.
`TYPO3\CMS\Extbase\Persistence\QueryInterface` declares everything the ported
code needs on both: `matching()` (`:159`), `logicalAnd()` (`:165`),
`logicalNot()` (`:179`), `equals()` (`:193`), `in()` (`:229`) and
`getConstraint()` (`:335`) on v12. `Query::logicalAnd()` pads a one element
list with an always true `uid > 0` on v12 as well, so the unwrapping of a
single constraint is right here too.

**One API is inverted between the branches, and it is the one `main`'s review
told me to delete.** On `main`, adding `$eventDispatcher` and
`injectEventDispatcher()` to `ContractRepository` was dead code, because
Extbase's own `Repository` declares both (v13 `Repository.php:37`/`:64`, v14
`:36`/`:63`). **TYPO3 v12's `Repository` has neither** - it declares only
`$persistenceManager`, `$objectType`, `$defaultOrderings`,
`$defaultQuerySettings` and `injectPersistenceManager()`, and the string
"dispatch" does not occur in the file. So on this branch `ContractRepository`
**must** declare them, or every contract query is a fatal error on v12 while
passing on v13. That is also why `ProfileRepository` carries them here - and
why it still carries them on `main`, where they became redundant when v12
support was dropped and nobody removed them.

`TYPO3\CMS\Core\Attribute\AsEventListener` does **not** exist on v12, so the
documented and tested way to register a listener here is the `event.listener`
tag in `Services.yaml`, as `AGENTS.md` of this branch states and as the three
listeners of this repository already do.

**The harness check.** The fixture extension mechanism, `test_plugin_templates`
and the `FrontendPluginRenderingTrait` all exist here. The three existing
fixture extensions of `academic_persons` are the same shape.

## Goals / Non-Goals

**Goals:**

- A listener can add constraints. A constraint cannot remove an upstream
  constraint, and a listener cannot change the ordering.
- The guarantee stops there, and saying so is part of the goal: the event hands
  out the live query object, whose query settings, limit and offset are read
  after the event and are not guarded.
- Nothing breaking. A 2.x installation updates to 2.4.0 and notices nothing.

**Non-Goals:**

- The plugin context, and every signature change that follows from it.
- Exposing the repository helpers.

## Decisions

### The events are the `main` ones minus the context

`FGTCLB\AcademicPersons\Event\ModifyProfileQueryEvent` and
`ModifyContractQueryEvent`, `final`, with the same class names and the same
constraint API as on `main`:

- `getQuery(): QueryInterface`, to build expressions only;
- `getDemand(): ?DemandInterface` on the profile event, null for the uid
  lookup; the contract event has no demand, as on `main`;
- `addConstraint(ConstraintInterface)` and `getConstraints(): list<ConstraintInterface>`.

`getPluginControllerActionContext()` is **absent**. A listener written here
therefore compiles and runs unchanged on `main`; one written on `main` that
reads the context does not run here, which is the right direction for a
maintenance line.

Rejected: shipping the accessor returning `null`. It would let a listener
compile here and silently do nothing, which is worse than not offering it.

### The repository rework is the `main` one

`applyDemandForQuery()` is renamed to `resolveDemandForQuery()` and returns the
constraint and the orderings instead of applying them; `applyQuery()` dispatches
and then calls `matching()` and `setOrderings()`. A constraint a listener set
with `matching()` on the query is folded into the same AND rather than dropped -
see the `main` design for why that is not "overwritten", which was the first
answer and does not survive the case where the repository has no constraint of
its own.

### `ContractRepository` declares the dispatcher

Because v12 does not provide it - see the API check above. `ProfileRepository`
already declares it on this branch and is left as it is.

### Listeners register with the `event.listener` tag

Not with `#[AsEventListener]`, which does not exist on v12. The fixture
extension does the same, so the documented way is the tested way.

### The fixture listeners are driven by static properties

On `main` they read the plugin settings through the context. Without a context
there is nothing to read, so the fixture listeners carry public static
properties that the test sets and `tearDown()` resets. Functional tests and the
frontend sub-request they render run in the same PHP process, so a static set
in the test method is visible to the listener.

This also fixes what can be tested: the "acts for one plugin only" and "reads a
content element setting" scenarios of `main` have no counterpart here, and the
delta spec below does not claim them.

## Risks / Trade-offs

- A listener narrows every plugin of the extension at once, with no way to
  exempt one. → Stated in the `Feature-*.rst` warning, which is where an
  integrator meets it on this branch - the `Documentation/Developers/` chapter
  `main` puts it in does not exist here. A project that needs per-plugin
  behaviour waits for 3.0, or reads the request itself.
- Two listeners that both call `matching()` - only the last survives. → Same
  as on `main`; `addConstraint()` is documented as the way that composes.

## Migration Plan

None. Nothing changes for an installation without a listener, and no signature
moved.

## Open Questions

None.
