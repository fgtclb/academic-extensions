## Context

See `proposal.md` for the motivation. Neither `academic_partners` nor
`academic_projects` has a `Classes/Event/` directory. `PartnerController`
(`listAction()`, `mapAction()`) and `ProjectController` (`listAction()`)
build the demand through their `DemandFactory`, query, compute the
applicable categories with `CategoryRepository::findAllApplicable()` (which
returns a `CategoryCollection`) and assign. Both controllers are not final
and use promoted constructor properties (protected in partners, protected
readonly in projects).

Extbase's `ActionController` provides the dispatcher as the protected
property `$eventDispatcher`, set through `injectEventDispatcher()` (v13.4.35
`ActionController.php:113`, v14.3.6 `:106`). `academic_persons` dispatches
`ModifyListProfilesEvent` from its controller the same way.

`academic_base` ships `PluginControllerActionContextInterface` and its final
implementation `PluginControllerActionContext(ServerRequestInterface, array
$settings)`, used by `academic_jobs`; the interface already exposes
`getActionName()`, `getPluginName()`, the content object renderer and the
settings. Both extensions require `fgtclb/academic-base`.

## Goals / Non-Goals

**Goals:**

- Typed extension points shaped like the persons pair, without constructor
  changes.

**Non-Goals:**

- Replacing the controllers, or marking them `final` in this change; that is
  `ace-tbd-final-partner-project-controllers`, see the decision below.

## Decisions

### Two final events per extension

- `FGTCLB\AcademicPartners\Event\ModifyPartnerDemandEvent`:
  `getDemand()`/`setDemand(PartnerDemand)`,
  `getPluginControllerActionContext()`.
- `FGTCLB\AcademicPartners\Event\ModifyPartnerListEvent`:
  `getPartners()`/`setPartners(QueryResultInterface)`,
  `getCategories()`/`setCategories(CategoryCollection)`, `getDemand()`,
  `getView()` (`FluidViewInterface|CoreViewInterface`, as persons),
  `getPluginControllerActionContext()`.
- `FGTCLB\AcademicProjects\Event\ModifyProjectDemandEvent` and
  `ModifyProjectListEvent` with the same shape for projects.

The analysis proposed an additional `getActionName()` on the demand event;
it is dropped because the context already answers it
(`getPluginControllerActionContext()->getActionName()`).

Rejected: one generic event in `academic_base` with a mixed payload. It loses
the typed demand, and every listener has to check which extension fired it.

### Dispatch through the inherited dispatcher

`$this->eventDispatcher->dispatch(...)` in the actions, with
`new PluginControllerActionContext($this->request, $this->settings)`. No
constructor change, so ace-demo's subclass keeps compiling until it is
removed.

### The demand event fires before the map's drawable restriction

In `mapAction()` the demand event is dispatched before
`setDrawableOnly(true)`, so a listener that replaces the demand cannot bring
back partners at 0/0 (ACE-562).

### Categories are not recomputed

When a list listener replaces the result, the categories stay as computed
from the original result unless the listener sets them too. Recomputing
after the event would run `findAllApplicable()` twice for every request
without a listener.

### Decided: the controllers become final in 3.0.0, in their own change

`PartnerController` and `ProjectController` become `final` directly in
3.0.0, as `ProfileController` and `JobController` already are. That is a
breaking change and is planned as the separate change
`ace-tbd-final-partner-project-controllers`, which lands after this one, so
the same release that closes the classes ships the events that replace a
subclass. This change keeps the controllers open and changes no constructor.

Rejected: closing them in this change, which would mix a breaking change
into an additive one. Rejected: a deprecation period and a deferral to 4.0;
3.0.0 is the major version in which the break is allowed.

### Decided: OR within a category type stays project code

No upstream demand flag switches the category filter to OR within one
category type. A project that needs it implements it behind
`ModifyPartnerDemandEvent` / `ModifyProjectDemandEvent`.

Both repositories combine every selected category with AND
(`PartnerRepository.php:79`, `ProjectRepository.php:53`), and the shipped
filter submits one value per category type, so OR within a type only
matters to a multi-select filter a single project built. The demand event
is the extension point for exactly that.

## Risks / Trade-offs

- [A listener returns a result of another type] → the setters are typed to
  `QueryResultInterface` and `CategoryCollection`.
- [Listeners and a controller subclass both active] → documented: remove the
  subclass when moving to listeners. From 3.0.0 on the subclass no longer
  loads at all (`ace-tbd-final-partner-project-controllers`).

## Open Questions

None.
