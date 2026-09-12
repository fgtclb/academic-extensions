## Context

- Rendering actions on main, all returning `htmlResponse()`:
  - persons `ProfileController`: `list`, `card`, `detail`, `selectedProfiles`
    and `selectedContracts`, the last two with an early return for an empty
    selection;
  - jobs `JobController`: `list`, `show` (early return without a job), `new`;
  - `PartnerController`: `list`, `map`, `partnershipsList`,
    `partnershipsTeaser`;
  - programs `ProgramController::list` and `DetailsController::show`;
  - `ProjectController::list`, `ContactsController::list`,
    `BiteJobsController::list`.
- `FGTCLB\AcademicJobs\Event\ModifyJobControllerNewActionViewEvent` is the
  model: `final`, constructed with the `academic_base`
  `PluginControllerActionContextInterface` and the view typed
  `FluidViewInterface|CoreViewInterface`, dispatched through the
  `$eventDispatcher` the Extbase `ActionController` holds. `newAction()`
  assigns `validations` after the dispatch, on purpose.
- The persons list, detail, selected profiles and selected contracts events
  already carry the view; the card action dispatches nothing. Besides the
  view they carry data with setters: `setProfiles()` and `setProfileDemand()`
  on the list event, read back after the query; `setProfile()` and the two
  page title formats on the detail event, whose resolved format feeds the
  page title; `setProfiles()` and `setContracts()` on the selection events.
- The detail title format is also configurable through the plugin setting
  `pageTitleFormat`, and `ModifyProfileDemandEvent` changes the profile
  demand inside the repository before the query.
- The `academic_base` context is built from the request and the settings, and
  resolves the content object from the request.
- `academic_base` already ships controller traits
  (`GetCurrentContentRecordMethodTrait`).

## Goals / Non-Goals

**Goals:**

- One event class, one way to dispatch it, the same on v13 and v14.
- A missing dispatch is caught by a test, not by a project.

**Non-Goals:**

- Changing controller finality; the non-final controllers stay as they are.

## Decisions

### One generic event instead of one event per action

`FGTCLB\AcademicBase\Event\ModifyPluginViewEvent` with
`getPluginControllerActionContext()` and `getView()`. Rejected: seventeen
`Modify<Plugin><Action>ViewEvent` classes, each to be documented, tested and
kept in step. A listener that needs one action checks the action name on the
context.

The getter is named like the one of the jobs event. Rejected:
`getContext()` as the analysis proposed; two names for the same thing in one
code base cost more than they save.

### Explicit dispatch through a trait method

A trait in `academic-base/Classes/Controller/` provides one protected method
that builds the `academic_base` context from `$this->request` and
`$this->settings` and dispatches the event with `$this->view`. Every rendering
path calls it once, after the action's own assignments, and before any
assignment that must stay protected.

Rejected: overriding `htmlResponse()` in the trait so no path can be missed.
It would fire after the jobs `validations` assignment and make it
replaceable, and a trait method silently shadows an override a project
controller might add. The coverage risk is handled by the functional test
enumerating every rendering path instead.

### The context is the academic_base one, for persons as well

The event is typed against the `academic_base` context interface. The persons
actions pass their persons context, which satisfies that interface once
`ace-tbd-single-action-context-interface` has landed.

### Decided: lands last, after the context and the policy change

The landing order is `ace-tbd-single-action-context-interface`, then
`ace-tbd-extension-point-policy`, then this change. The context change is
small and does not break listeners, and this change already depends on it;
the policy change then provides the extension point page this change adds its
event to. The event class carries an `@api` docblock tag, the marker
convention the policy change sets for every class it lists.

### Decided: the specific view events are removed in 3.0

`ModifyJobControllerNewActionViewEvent` and the persons list, detail,
selected profiles and selected contracts events are removed in 3.0 as a
breaking change, not deprecated. 3.0.0 is unreleased, and keeping two events
per action would leave listeners to choose between them for the whole 3.x
cycle. The generic event is dispatched where they were.

The removal does not fatal existing listeners. TYPO3 registers a listener's
event as a class name string, read from the parameter type or the `event`
attribute argument, without loading the class, and PHP checks a parameter
type only when the method is called; a listener of a removed event is simply
never called. Static analysis reports it as referring to an unknown class.
Task 3.4 proves this with a fixture listener before the `Breaking-` entries
claim it.

The `Breaking-` entries follow the style of a TYPO3 core hook-to-event
transition: Description, Impact ("the event is no longer dispatched"),
Affected installations, and a Migration with a listener of the generic event
that checks the action name on the context, linking
`Feature-ModifyPluginViewEvent.rst`. They also list what the generic event
does not carry, with its replacement:

- the list demand: `ModifyProfileDemandEvent`, before the query;
- the detail page title format: the plugin setting `pageTitleFormat`;
- replacing the profiles, contracts or the detail profile after the query:
  reassigning the view variable in a listener of the generic event; the
  page title and the pagination keep using the queried result.

Rejected: deprecating only the jobs event and keeping the persons events, as
first proposed, because it keeps the per-action events the generic event is
meant to replace. Rejected: moving the persons data setters onto the generic
event, which would make a view event carry domain data of one extension.

### No core version switch

The view is typed `FluidViewInterface|CoreViewInterface` exactly like the jobs
event, which already runs on both versions. Nothing else differs.

## Risks / Trade-offs

- [A listener overrides a variable the controller assigned] → documented as
  unsupported beyond adding variables; the protected ones stay out of reach.
- [A new action forgets the dispatch] → the functional test lists every
  rendering path; the contributor rule of `ace-tbd-extension-point-policy`
  requires the call for new actions.
- [A project relies on a removed event to change data after the query] →
  the `Breaking-` entry names each setter and its replacement; filtering
  belongs into the demand event.
- [A leftover listener stays unnoticed at runtime] → it is never called and
  raises nothing; the `Breaking-` entry says to search for the five class
  names, and phpstan reports them.

## Migration Plan

- Listeners of a removed event: register a listener of `ModifyPluginViewEvent`
  and check the plugin and action names on its context.
- Demand changes: move them to `ModifyProfileDemandEvent`.
- Detail title formats: set the plugin setting `pageTitleFormat`.

## Open Questions

None.
