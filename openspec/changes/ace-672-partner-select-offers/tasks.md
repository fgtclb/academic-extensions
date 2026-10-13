## 1. Premises and the decision

- [x] 1.1 Confirm the policy of `design.md` decision 1 with the maintainer.
  If it is not A, stop and update the delta spec and the design through
  `/opsx:update` before anything else. Record the answer in the pull request.
  The maintainer chose A on 2026-10-13.
- [x] 1.2 Re-read the premises of `design.md`, Context, on HEAD against both
  installed cores: the handler and its two `@todo` lines, the constructor of
  `Typo3QuerySettings`, the backend branch of `Typo3DbQueryParser` and the
  fields `BEenableFields()` covers. Stop and report if one has changed.
  All of them hold on `main` `61150d1c5` against 13.4.36 and 14.3.7.

## 2. Tests first

- [x] 2.1 Add a functional test that calls the item handler of the partner
  field directly, without any global request, on a fixture with a hidden
  partner, a partner whose start time lies in the future, a partner whose end
  time has passed and a deleted partner. Assert the first three among the
  items and the deleted one absent. Run it on v13 and v14 before the change
  and record that it fails for the three, and why.
  Red on v13 and v14 before the change: only the visible partner is offered,
  because without a request `Typo3QuerySettings` leaves the flag off and
  `BEenableFields()` applies. The test is `PartnerItemsTest`.
- [x] 2.2 Extend the compiled form test with the same fixture: the three are
  offered and the deleted one is not, and a partnership whose partner is
  hidden still holds that partner in the compiled row and after an unchanged
  save. Keep the ACE-669 order fixture untouched. These pass before the
  change. Show them red by forcing the flag off in the handler for one run,
  then restore from a backup and compare checksums.
  The cases are in `PartnerSelectVisibilityTest` with a fixture of its own.
  With the flag forced off all seven of its cases, the case of
  `PartnerItemsTest` and three cases of `PartnerSelectOrderTest` fail on v13
  and v14.
- [x] 2.3 Rename `hiddenPartnersAreOfferedAsBefore()` to `hiddenPartnersAreOffered()`
  and rewrite its docblock to name the decision.

## 3. Implementation

- [x] 3.1 In `packages/fgtclb/academic-partners/Classes/Backend/FormEngine/PartnerItems.php`,
  call `setIgnoreEnableFields(true)` on the settings the handler builds, and
  replace both `@todo` lines by a comment saying which partners are offered
  and why. Verify 2.1 is green on v13 and v14.
- [x] 3.2 Verify that `PartnerRepository` is not part of the diff, and that
  the `null` check around the loop of the handler is still there. Both belong
  to ACE-443 item 5 in `ace-434-coverage-round-leftovers`.

## 4. ACE-671

- [x] 4.1 In `PartnerRepositoryFindAllTest`, rename `hiddenPartnersAreNotReturned()`
  to `hiddenPartnersAreNotReturnedOutsideABackendRequest()`, and say in its
  docblock what it measures, where the select's guarantee is tested, and that
  it turns red if the flag moves into the repository. Show that by setting the
  flag in `findAll()` for one run.
  Red on v13 and v14 with the flag set in `findAll()`.

## 5. Documentation

- [x] 5.1 Add `packages/fgtclb/academic-partners/Documentation/Changelog/3.0/Important-PartnerSelectOffersHiddenPartners.rst`
  from `Build/Documentation/Templates/`: which partners the select offers,
  that nothing changes on v13 or v14, that installations coming from 2.x on
  TYPO3 v12 see hidden and timed partners for the first time, and what the
  frontend renders for a partnership whose partner is not visible (read it,
  do not change it). Check that the entry renders without a definition list.
- [x] 5.2 `docs/architecture/backend-select-items.md`: a short section that a
  handler querying through Extbase states its enable field handling itself,
  because the core default depends on the global request, with `PartnerItems`
  as the example.

## 6. Branch `2`

- [x] 6.1 Record the maintainer's answer to `design.md` decision 6 in the
  pull request. The work on `2` is a change of its own there, derived from a
  backport analysis, and is not ticked here.
  The maintainer chose to align v12 on 2026-10-13. Measured on 12.4.45: a
  partnership whose partner is hidden, starts in the future or has expired
  compiles with an empty partner, and an unchanged save writes `0`.

## 7. Definition of done

- [x] 7.1 After `composerUpdate` for TYPO3 v13: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` on SQLite (`-j auto`), PostgreSQL
  (`-b docker -j 8`) and MariaDB (`-j 8`) green.
  13.4.36: 2030 unit tests, 5611 functional tests on each database.
- [x] 7.2 After `composerUpdate` for TYPO3 v14: the same gates green.
  14.3.8: 2025 unit tests, 5631 functional tests on each database.
- [x] 7.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 7.4 `docs/` updated, `README.md` and `CONTRIBUTING.md` still only
  summarize and link.
- [x] 7.5 Commit as `[TASK] ACE-672: Decide which partners are offered`
  (a `[BUGFIX]` if decision 1 is not A) in TYPO3 Core format, body at 72
  columns, footer `Resolves: ACE-672` and `Resolves: ACE-671`, no
  attribution of any kind.
- [ ] 7.6 Archive the change as the last commit of the same pull request.
