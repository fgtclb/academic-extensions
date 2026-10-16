## 1. Tests first, red on the unchanged code

- [x] 1.1 Listener `ChangeProfileDemandInPlaceListener` in the fixture
  extension `test_profile_query_constraints`: changes the selection or the
  page of the demand it receives in place, records the page and selection it
  received per call and the `demand` the list hands to its view. Inert until
  a test sets it, reset in `setUp()` and `tearDown()`.
- [x] 1.2 `AcademicPersonsDemandListenerInPlaceTest` with the fixture
  `Plugins/Fixtures/AcademicPersonsDemandListenerInPlace/records.csv`, one
  test per scenario of the delta spec. Run on TYPO3 v13, then v14, against
  the code of `main`: every test red, by the order, the page and the page
  each call received (`.agent/tmp/ace-920/logs/red-core13.log`,
  `red-core14.log`). The template demand assertions were shown red on their
  own on v14 with the order and page assertions left out
  (`red-core14-template-demand.log`). The scenario of a cleared selection,
  added later, was red on v14 and v13 with the dispatch reverted
  (`red-core14-clear.log`, `red-core13-final.log`).
- [x] 1.3 Unit test of `ProfileDemand`: every property has a scalar or array
  type, and a clone changed through every setter leaves the original as it
  was. Both red with an object property added and the clone left out on
  purpose (`unit-demand-red-core13.log`).

## 2. Implementation

- [x] 2.1 `ProfileRepository::findByDemand()` dispatches the event with a
  clone of the demand, its docblock says so, verified by the tests of 1.2 on
  v13 and v14.
- [x] 2.2 The docblock of `ModifyProfileDemandEvent` names the three methods
  it is dispatched in and that each hands out a copy. The docblock of
  `findNextContractValidityChange()` already described the copy of all
  three and stays.

## 3. Documentation

- [x] 3.1 `Developers/Events/ModifyProfileDemandEvent.rst`: the pitfall
  "Hand back a copy" goes, the dispatch list says every call receives a copy
  of the demand the plugin built, the example changes the copy in place, and
  a rule names what a selection a listener sets does.
- [x] 3.2 `Changelog/2.4/Important-DemandListenersWorkOnACopy.rst`, written
  so branch `2` can take it byte for byte: no reference to the letters or the
  contract validity change, which branch `2` does not have, and no
  reference at all. Linked from the "Introduced in" section of the event
  page, from the upgrade guides from 2.3 to 2.4 and to 3.0, and from the
  3.0 entry of the removed view events, which now names the change in place.
- [x] 3.3 `docs/architecture/list-plugin-events.md` states that the persons
  demand event works on a copy, unlike the controller events of the other
  lists. `docs/testing/fixture-extensions.md` names the new listener.

## 4. Definition of done

- [x] 4.1 `lintPhp`, then per core version after its own `composerUpdate`:
  `cgl -n`, `phpstan`, `unit` and `functional -j auto` on SQLite, green.
- [x] 4.2 `lintMarkdown -n` and the rendering of the `academic_persons`
  manual, green.
- [x] 4.3 Commit message in TYPO3 Core format with `Resolves: ACE-920`, and
  the archive of this change with the spec sync as the last commit.
