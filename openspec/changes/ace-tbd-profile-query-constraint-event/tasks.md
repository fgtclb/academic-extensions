## 1. Prerequisites

- [ ] 1.1 Verify on `main` that
  `FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface`
  exists as described in `design.md`, and check how
  `ace-tbd-generic-plugin-view-event` (or its renamed successor) builds an
  `academic_base` context in the persons actions; build it the same way.

## 2. Tests first

- [ ] 2.1 Add a functional fixture extension, following
  `docs/testing/fixture-extensions.md`, with one listener per event registered
  through TYPO3's `#[AsEventListener]`. The profile listener adds "last name
  equals Achterberg" and the contract listener excludes one contract uid.
- [ ] 2.2 Add plugin tests that load the fixture extension: list, card,
  selected-profiles and selected-contracts render only the matching records.
  Record that they fail while the events are not dispatched.
- [ ] 2.3 Add a paginated list test: five profiles, two left by the listener,
  two per page. Assert one page and both profiles in last name order, and
  record its failure.
- [ ] 2.4 Add a test combining an editor organisational unit filter with the
  listener, a test that a listener which checks the plugin name leaves the
  card plugin unrestricted, and a test that a listener reads a content element
  setting in the selected-profiles plugin.
- [ ] 2.5 Add unit tests of both events: collected constraints, null demand
  for uid lookups, a context of the `academic_base` type.

## 3. Implementation

- [ ] 3.1 Add both `final` events and verify 2.5 passes.
- [ ] 3.2 Rework `ProfileRepository` so that `applyDemandForQuery()` returns
  the constraint, dispatch the event in `findByDemand()` and the uid lookup,
  and apply `matching()` and `setOrderings()` after the dispatch. Verify that
  the existing repository and plugin tests stay green.
- [ ] 3.3 Inject the dispatcher into `ContractRepository` and dispatch in its
  uid lookup.
- [ ] 3.4 Add the optional context parameter to `findByDemand()`, add the
  context-aware uid finders to both repositories with `findByUids()`
  delegating to them unchanged in signature, and pass the context from every
  call in `ProfileController`. Verify 2.2 to 2.4 pass.
- [ ] 3.5 Remove the listener registration on purpose and watch 2.2 go red;
  restore.

## 4. Documentation

- [ ] 4.1 Add `Documentation/Changelog/3.0/Feature-ModifyProfileQueryEvent.rst`
  (both events, a listener example with `#[AsEventListener]`) and
  `Breaking-OptionalContextParameterOnProfileDemandFinder.rst` (the changed
  `findByDemand()` signature and the plugin calls that moved to the
  context-aware uid finders), from `Build/Documentation/Templates/`.
- [ ] 4.2 Add the events to the developer chapter of `Documentation/`; verify
  with `checkRstRenderingAll`.
- [ ] 4.3 Add to `docs/architecture/database-queries.md` how listener
  constraints are combined and why orderings stay with the repository; verify
  with `lintMarkdown -n`.

## 5. Backport

- [ ] 5.1 Open the backport as a separate change on branch `2` after a
  backport analysis (`docs/workflow/backporting.md`): the same event classes
  and methods without `getContext()`, no repository signature change, a
  `Feature-*.rst` for 2.4.0. `TYPO3\CMS\Core\Attribute\AsEventListener` does
  not exist on v12, so the fixture registers its listeners in `Services.yaml`
  there.

## 6. File the issue

- [ ] 6.1 After implementation, file the ACE issue in YouTrack, verify its key
  and rename the change to `ace-<NNN>-profile-query-constraint-event`.
- [ ] 6.2 Commit as `[!!!][FEATURE] ACE-<NNN>: Add profile query constraint events`
  in TYPO3 Core format.

## 7. Definition of done

- [ ] 7.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 13`.
- [ ] 7.2 `composerUpdate`, then the same suites green with `-t 14`.
- [ ] 7.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 7.4 `docs/` and the `Documentation/` changelog updated as in group 4;
  `README.md` and `CONTRIBUTING.md` still only summarise.
- [ ] 7.5 Anything left out is named in the pull request, with the reason.
- [ ] 7.6 Archive the change as the last commit of the pull request.
