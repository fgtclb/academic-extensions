## Context

- `DispatchModifyPluginViewEventMethodTrait::dispatchModifyPluginViewEvent()`
  of `academic_base` takes the request, the settings, the view and the event
  dispatcher, and builds a new `PluginControllerActionContext` from the first
  two. `ace-750-generic-plugin-view-event` chose that on purpose: the persons
  list sets `paginationEnabled` to `0` under a letter filter after its query,
  and a context built before would carry the old value.
- `PartnerController` and `ProjectController` build a context in the middle
  of the action, after the filter redirect, and hand it to their demand and
  list events. The view event builds another one.
- The persons `ProfileController` builds a persons context per repository
  call (`pluginControllerActionContext()`), one more for the letter query,
  one for the page title provider, and the view event builds an
  `academic_base` one.
- The persons edit `ProfileController` builds a context for the form data and
  validation in `updateAction()` and `updateSkipSyncAction()`, and
  `dispatchBeforeWrite()` builds another one for the write event. It is called
  from 16 places, in 13 actions and the two helpers of the image actions, once
  per request.
- The other controllers dispatch only the view event.

## Decisions

### The action builds the context, the trait takes it

`dispatchModifyPluginViewEvent(PluginControllerActionContextInterface
$pluginControllerActionContext, FluidViewInterface|CoreViewInterface $view,
EventDispatcherInterface $eventDispatcher)`. The trait stays free of the host
class, as `docs/architecture/class-design.md` requires of every trait, and
the action is the one place that knows when its settings are settled.

Rejected: a trait method that builds and caches the context on the
controller. It would read and write a property of the host class, and a
cache survives a settings change unnoticed.

### Built before the first event

Each action builds its context before its first event, once its settings are
final. Settings changed in an `initialize…Action()` method are final by then.
The one change inside an action, the persons list switching off the
pagination under a letter filter, depends on the demand alone and moves
before the query. The plugin actions build it as their first statement, the
partner and project lists before the filter redirect, which reads nothing of
it. Two persons actions build it a little later: the list after the letter
switch, the detail after its return for a missing profile, which dispatches
nothing. The editor actions build it after the request is validated, where they
used it first: a rejected request dispatches no event, and the unit tests of
those rejections run the actions without settings.

Rejected: a second context after a settings change. It is the situation this
change removes, and the only case of it moves.

### The persons plugins keep building the persons context

The persons actions build the deprecated persons context, because the page
title placeholder event still declares its interface until 4.0 (ACE-747),
and hand the same object to the view event. The persons interface extends the
`academic_base` one the view event declares. The `@todo` for 4.0 stays where
it is.

### The profile editor

`dispatchBeforeWrite()` takes the context as an argument, and so do the two
helpers of the image actions that call it. Each action builds the context once
and hands it to the form data, the validation and its write event. The local
variable keeps the name `$pluginControllerActionContext`, because the
controller already has a property `$context`, the TYPO3 context.

No listener can observe this part. An editor request dispatches one write
event, and the form data factory the other context went to is not public API.
It needs no test of its own, and the existing editor tests pass unchanged.

### Tests

A recording listener in `test_plugin_view_event` keeps the context of the
view event, the partner and project demand and list events, the persons query
events and the page title event. `ModifyPluginViewEventTest` asserts the
sequence of events and that every event of one rendering got the same object,
for the persons list with and without a letter, the persons detail, card,
selected profiles and selected contracts, the partner list and map and the
project list. A second test asserts that under a letter every event sees the
pagination switched off. All ten were shown to fail before the change.

## Risks / Trade-offs

- [A listener of a persons query event relied on `paginationEnabled` being
  `1` under a letter] → The query ignores that setting, so only a listener
  that reads it notices. An `Important` changelog entry names it.
- [A persons demand listener changed the letter on the demand it was handed]
  → The repository hands the demand event the controller's own demand, so
  such a change used to move the pagination switch, which read the letter
  after the query. The switch now follows the letter the visitor chose. A
  listener that uses `setDemand()`, the documented way, sees no difference.
  The `Important` changelog entry says so.
- [A listener of the view event checks for the final `academic_base` class]
  → The event declares the interface, and the persons context implements it.
  The changelog says which object the persons plugins hand over.

## Open Questions

None.
