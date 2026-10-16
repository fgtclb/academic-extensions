## Context

`ProfileRepository` dispatches `ModifyProfileDemandEvent` in three methods.
`findAlphabetFilterLetters()` and `findNextContractValidityChange()` hand the
listeners a clone. `findByDemand()` hands them the object it was given, and
`ProfileController::listAction()` keeps reading that object after the query:
for the next contract validity change, the manual order, the page of the
paginator, the letters and the `demand` view variable. See proposal.md for
what that leaks.

The event is `@api`, final, and carries a `DemandInterface`. Its signature
stays as it is.

## Goals / Non-Goals

**Goals:**

- One rule for all three dispatches: a listener works on a copy, and what it
  hands back reaches the query of that call and nothing else.
- The manual promises the same for every listener, with no "write it this
  way" pitfall left.

**Non-Goals:**

- A way for the controller to learn the effective demand of the query.
- The demand events of the partner, program and project lists, which are
  dispatched by their controllers and hand the replaced demand to the view
  by design.

## Decisions

**Clone in the repository, not in the controller.** `findByDemand()`
dispatches the event with `clone $demand`, as `findNextContractValidityChange()`
does. Every caller is covered by the one line, including the card action and
any caller outside the plugins, and the controller stays as it is. Cloning in
`listAction()` before the call was rejected: it protects one caller, and the
repository would still hand the next caller's object to the listeners.

**The request decides, the controller does not ask back.** Maintainer
decision. The alternative, a repository method returning the effective demand
so the controller orders a selection a listener made, was rejected: it makes
the manual order, the page and the template depend on listeners again, which
is the coupling this change removes, and it would widen the API for one case.
A listener that wants a selection ordered by hand sets it in the content
element or through `settings.demand`, not in the demand event.

**A shallow clone is a complete copy, no `__clone()`.** `ProfileDemand` holds
strings, integers, booleans and two arrays of integers. PHP copies arrays by
value, so nothing a listener reaches through the clone is shared with the
original. A `__clone()` would have nothing to do. A unit test pins it: it
asserts that every property of `ProfileDemand` has a scalar or array type and
that changing every property of a clone through its setters leaves the
original unchanged. A property holding an object added later fails the first
assertion and has to bring a `__clone()` with it. A demand class of another
implementation, which only a caller of the repository outside the plugins or
a listener's own replacement can bring in, is cloned the same way and is
responsible for its own `__clone()`.

**Nothing else in `findAlphabetFilterLetters()` and
`findNextContractValidityChange()` changes.** Both clone before the dispatch
already. With `findByDemand()` no longer leaking, they receive the request's
demand, so the listener runs once per call on the request rather than a
second time on top of its own change.

**The test listener changes in place.** A fixture listener in
`test_profile_query_constraints` changes the demand it receives without
calling `setDemand()`, records what it received on each call, and records the
`demand` the list hands to its view through `ModifyPluginViewEvent`. A
listener that replaced the demand would pass on the unchanged code, so it
would prove nothing here, which is why the existing
`ReplaceProfileDemandListener` is not reused.

## Risks / Trade-offs

- [A listener relied on the leak, to switch the order of a selection on or
  off or to move the page] → The `Important` changelog entry names the
  effect and the alternative. The query still follows the listener, so the
  profiles the list queries do not change, only their order and the page
  shown.
- [A third party demand class holding objects] → Documented in the docblock
  of `findByDemand()`, the clone is shallow.
