## Context

`ProfileRepository::findByDemand()` dispatches `ModifyProfileDemandEvent` with
the object it was given. `ProfileController::listAction()` passes its own
demand and keeps reading it after the query: it hands it to
`ModifyListProfilesEvent`, switches the pagination off for a letter, orders a
selection by hand, gives the paginator its page and assigns it to the view as
`demand`. See proposal.md for what that leaks.

On `main` the event is dispatched in three repository methods, two of which
already cloned. On this branch it is dispatched in `findByDemand()` only, so
the list query is the one dispatch of a rendering. The event is final and has
no `@api` tag here. Its signature stays as it is.

## Goals / Non-Goals

**Goals:**

- A listener works on a copy, and what it hands back reaches the list query
  and nothing else.
- The same rule and the same code as on `main`, so the shared changelog entry
  holds on both branches.

**Non-Goals:**

- A way for the controller to learn the effective demand of the query.
- `ModifyListProfilesEvent::setProfileDemand()`, which replaces the demand of
  the list after the query by design and is not a demand listener. What
  changes for that event is what its listeners receive: the demand of the
  list, never one a demand listener changed in place. Its listeners are the
  one place outside the template where this branch shows the leak to a
  third party, so the test asserts on it.

## Decisions

**Clone in the repository, not in the controller.** `findByDemand()`
dispatches the event with `clone $demand`, the line of `main`. Every caller is
covered, including the card action and any caller outside the plugins, and the
controller stays as it is. Cloning in `listAction()` was rejected, as on
`main`: it protects one caller only.

**The request decides, the controller does not ask back.** Maintainer
decision, the same on both branches. A listener that wants a selection
ordered by hand sets it in the content element or through `settings.demand`.

**A letter set by a demand listener no longer switches the pagination off.**
This is the consequence of the rule that only this branch can observe: the
check for a letter runs after the query, on the demand of the list. A letter
of the request still switches it off. The test pins the listener case.

**A shallow clone is a complete copy, no `__clone()`.** `ProfileDemand` holds
strings, integers, a boolean and two arrays of integers on this branch too.
The unit test of `main` is taken over with this branch's setters: every
property has a scalar or array type, and changing every property of a clone
leaves the original unchanged.

**The test listener is registered with the tag, and the template demand is
read from the list event.** `TYPO3\CMS\Core\Attribute\AsEventListener` does
not exist on TYPO3 v12, so both methods of the listener are registered with
`event.listener` tags in `Services.yaml`, as the two listeners of the fixture
extension already are. This branch has no `ModifyPluginViewEvent`, so the
listener records the demand `ModifyListProfilesEvent` carries, which the list
assigns to the view unchanged while no listener replaces it. Writing the
demand into the markup of `test_plugin_templates` was rejected: the template
is shared with every plugin test of the extension.

**The test of every call of one rendering is not taken over.** It asserts
three dispatches on `main`. Here it is reduced to one assertion in the page
test, that the listener was asked once with the page of the request.

## Risks / Trade-offs

- [A listener relied on the leak, to switch the order of a selection on or
  off, to move the page or to switch the pagination off with a letter] → The
  `Important` changelog entry names the effect. The query still follows the
  listener, so the profiles the list queries do not change.
- [A third party demand class holding objects] → Documented in the docblock
  of `findByDemand()`, the clone is shallow.
