## 1. Selection service

- [ ] 1.1 Add `ContractDisplay`, `ContractSelection` and `ContractSelector`.
  Add unit tests for: all, first, the unit filter, the function type filter,
  validity with open start and open end, the order of filter before first, and
  a simulated date through the `Context` date aspect. Record that the "first"
  and validity tests fail against a selector that returns the contracts
  unchanged.
- [ ] 1.2 Register the selector and the `p:contracts` ViewHelper, the
  ViewHelper as a public service, and verify it is resolved by rendering a
  fixture template in a functional test.

## 2. Plugin settings

- [ ] 2.1 Add the three FlexForm fields to `Core13/List.xml`,
  `Core14/List.xml` and `SelectedProfiles.xml`, with English and German labels
  (one line, two-space indentation). Verify in a backend FlexForm test on v13
  and v14 that the data structure resolves.
- [ ] 2.2 Add `profile.details.position.contracts`,
  `profile.details.contact.contracts` and the two `onlyValid` keys next to
  them to `Settings.yaml` and to the settings normaliser, with unit tests
  that a missing `contracts` defaults to `all`, a missing `onlyValid` to
  false, and that an unknown value is rejected the way other invalid values
  are.

## 3. Templates and rendering tests

- [ ] 3.1 Add a list fixture: one profile with an expired and a current
  contract, and one with two units. Add list and card tests for first,
  matchFilter and onlyValid. Record that they fail while `Contract/Item.html`
  still loops over `profile.contracts`.
- [ ] 3.2 Switch `Contract/Item.html` to the selection; verify 3.1 passes and
  every existing plugin test stays green.
- [ ] 3.3 Add detail tests for the contact block with `first`, the position
  block with `all` and the position block with `onlyValid`, record their
  failure, then switch `Position.html` and `Contact.html` to the selection
  and verify they pass.
- [ ] 3.4 Add a selected-contracts test with an expired contract and
  `onlyValid` configured, proving the chosen contract is still shown, and an
  `academic_contacts4pages` rendering test proving its output is unchanged.

## 4. Page cache lifetime

- [ ] 4.1 Add unit tests for the next boundary: a shown contract ending on a
  day yields midnight after that day, a contract left out because it starts
  on a day yields midnight of that day, the earlier of several wins, and
  open-ended contracts or a selection without a validity option yield none.
  Record that they fail against a selector without the boundary.
- [ ] 4.2 Add a functional list test with `onlyValid`, a contract ending
  today and one starting tomorrow, asserting that the page cache entry
  expires at the next midnight; add one without `onlyValid` asserting the
  configured lifetime. Record that the first fails without the restriction.
- [ ] 4.3 Restrict the lifetime in the ViewHelper through the
  `frontend.cache.collector` request attribute; verify 4.1 and 4.2 pass on v13
  and v14, remove the call on purpose and watch 4.2 go red; restore.
- [ ] 4.4 Add a test that rendering without the request attribute (a fixture
  template rendered outside a frontend request) does not fail.

## 5. Documentation

- [ ] 5.1 Add `Documentation/Changelog/3.0/Feature-ContractDisplayPolicy.rst`
  from `Build/Documentation/Templates/`, naming the page cache restriction.
- [ ] 5.2 Document the FlexForm fields, the four `Settings.yaml` keys, the
  open-ended empty end date and the page cache restriction in the
  configuration chapter of `Documentation/`; verify with
  `checkRstRenderingAll`.
- [ ] 5.3 Update the measured counts in
  `docs/architecture/dependency-injection.md` for the new service and
  ViewHelper, and describe the request-scoped cache lifetime restriction as
  the stateless alternative to a collecting listener in the same page; verify
  with `lintMarkdown -n`.

## 6. File the issue

- [ ] 6.1 After implementation, file the ACE issue in YouTrack (relating it
  to ACE-59 and ACE-51), verify its key and rename the change to
  `ace-<NNN>-contract-display-policy`.
- [ ] 6.2 Commit as `[FEATURE] ACE-<NNN>: Add a contract display policy` in
  TYPO3 Core format.

## 7. Definition of done

- [ ] 7.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 13`.
- [ ] 7.2 `composerUpdate`, then the same suites green with `-t 14`.
- [ ] 7.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 7.4 `docs/` and the `Documentation/` changelog updated as in group 5;
  `README.md` and `CONTRIBUTING.md` still only summarise.
- [ ] 7.5 No backport: a new feature, and the detail view on branch `2` has a
  different structure. State it in the pull request, together with anything
  else left out.
- [ ] 7.6 Archive the change as the last commit of the pull request.
