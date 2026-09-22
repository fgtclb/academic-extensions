## 1. Backport analysis

- [x] 1.1 Diff the three touched files between `main` at `211a68c53` and this
  branch, and record in `design.md` which differences touch this change.
- [x] 1.2 Check every API the ported code uses against TYPO3 v12 and v13, not
  only v13. Record what is inverted.

## 2. Tests first

- [x] 2.1 Add the fixture extension `test_profile_query_constraints`, with one
  listener per event registered through the `event.listener` tag in
  `Services.yaml` - `TYPO3\CMS\Core\Attribute\AsEventListener` does not exist
  on v12. The listeners are driven by public static properties, because
  without a context there are no settings to read.
- [x] 2.2 Add plugin tests: list, card, selected-profiles and selected-contracts
  render only the matching records, each with a control that the same fixture
  renders everything while the listener adds nothing.
- [x] 2.3 Add a paginated list test and a paginated manual selection test, each
  with its control, so that the pages really follow the constraint.
- [x] 2.4 Add the editor organisational unit filter combined with the listener,
  the ordering test, and the test that a condition set with `matching()` next
  to an editor filter is folded in rather than dropped or left standing.
- [x] 2.7 Added in review: the plain-list half of the `matching()` contract,
  which `main` has and the first port dropped, and a card plugin assertion that
  can fail - the first one matched a name the list plugin on the same page
  already rendered, so it would have passed with no card at all. The card is
  now asserted on its own markup, with a control that it renders three profiles
  while the listener adds nothing. This is the only card rendering coverage
  this branch has.
- [x] 2.5 Add unit tests of both events.
- [x] 2.6 Record that the new tests fail before the repositories dispatch.

## 3. Implementation

- [x] 3.1 Add both `final` events, without the plugin context.
- [x] 3.2 Rework `ProfileRepository`: `applyDemandForQuery()` becomes
  `resolveDemandForQuery()` and returns the constraint and the orderings;
  `applyQuery()` dispatches, folds in a constraint a listener set with
  `matching()`, and applies `matching()` and `setOrderings()` afterwards.
- [x] 3.3 Declare `$eventDispatcher` and `injectEventDispatcher()` on
  `ContractRepository` - v12's Extbase `Repository` has neither - and dispatch
  in its uid lookup.
- [x] 3.4 Leave `ProfileController` untouched, and verify no call site changed.

## 4. Documentation

- [x] 4.1 Add `Documentation/Changelog/2.4/Feature-ProfileAndContractQueryEvents.rst`,
  with the listener example registered the way this branch registers listeners,
  and with what this branch does not offer. No `Breaking-*.rst`.
- [x] 4.2 Add the section to `docs/architecture/database-queries.md`; verify
  with `lintMarkdown -n`.

## 5. Definition of done

- [x] 5.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 12`.
- [x] 5.2 `composerUpdate`, then the same suites green with `-t 13`.
- [x] 5.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 5.4 Anything left out is named in the pull request, with the reason.
- [ ] 5.5 Archive the change as the last commit of the pull request.
