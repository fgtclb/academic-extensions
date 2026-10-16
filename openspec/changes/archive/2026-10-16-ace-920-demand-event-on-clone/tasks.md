## 1. Tests first, red on the unchanged code

- [x] 1.1 Listener `ChangeProfileDemandInPlaceListener` in the fixture
  extension `test_profile_query_constraints`, taken from `main` with a letter
  added: changes the selection, the page or the letter of the demand it
  receives in place, records what it received per call and the demand a
  listener of `ModifyListProfilesEvent` receives, which the list assigns to
  its view. Registered with two
  `event.listener` tags in `Services.yaml`. Inert until a test sets it, reset
  in `setUp()` and `tearDown()`.
- [x] 1.2 `AcademicPersonsDemandListenerInPlaceTest` with the fixture
  `Plugins/Fixtures/AcademicPersonsDemandListenerInPlace/records.csv` of
  `main`, one test per scenario of the delta spec. Run on TYPO3 v12, then
  v13, against the code of `2`: all five red, by the order, the page and the
  missing pagination (`.agent/tmp/ace-920-2/logs/red-core12.log`,
  `red-core13.log`). The assertions on the demand of the list event were
  shown red on their own on v12 and v13, with the order and pagination
  assertions left out (`red-core12-list-demand-only.log`,
  `red-core13-list-demand-only.log`).
- [x] 1.3 Unit test of `ProfileDemand` from `main` with the setters of this
  branch: every property has a scalar or array type, and a clone changed
  through every setter leaves the original as it was. Both red on v12 with an
  object property added on purpose (`unit-demand-red-core12.log`).

## 2. Implementation

- [x] 2.1 `ProfileRepository::findByDemand()` dispatches the event with a
  clone of the demand, its docblock says so in the words of `main`, verified
  by the tests of 1.2 on v12 and v13 (`green-core12.log`).
- [x] 2.2 The docblock of `ModifyProfileDemandEvent` closes its `@see` tag
  and says that the listeners receive a copy.

## 3. Documentation

- [x] 3.1 `Changelog/2.4/Important-DemandListenersWorkOnACopy.rst` and
  `Upgrade/From23To24.rst` copied from `main` byte for byte, identical by
  `cmp` to `da0d969af` there, and every reference of both resolving in the
  rendering of the `academic_persons` manual. The statements of the entry
  hold on this branch: the list event is the later event that can replace
  the demand, its listeners receive the list's own demand and a letter the
  listener sets leaves the pagination on by the tests of 1.2, and the card
  plugin is the only other caller of the list query in the extension.
- [x] 3.2 `docs/testing/fixture-extensions.md` names the listener changing
  the demand in its row of `test_profile_query_constraints` and in the
  paragraph on its tag registration.

## 4. Definition of done

- [x] 4.1 `lintPhp`, then per core version after its own `composerUpdate`:
  `cgl -n`, `phpstan`, `unit` and `functional -j auto` on SQLite, green.
- [x] 4.2 `lintMarkdown -n` and the rendering of the `academic_persons`
  manual, green.
- [x] 4.3 Commit message in TYPO3 Core format with `Resolves: ACE-920`, and
  the archive of this change with the spec sync as the last commit.
