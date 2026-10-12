## 1. Premises and the decision

- [ ] 1.1 Confirm the policy of `design.md` decision 1 with the maintainer.
  If it is not A, stop and update the delta spec and the design through
  `/opsx:update` before anything else. Record the answer in the pull request.
- [ ] 1.2 Re-read the premises of `design.md`, Context, on HEAD against both
  installed cores: the handler and its two `@todo` lines, the constructor of
  `Typo3QuerySettings`, the backend branch of `Typo3DbQueryParser` and the
  fields `BEenableFields()` covers. Stop and report if one has changed.

## 2. Tests first

- [ ] 2.1 Add a functional test that calls the item handler of the partner
  field directly, without any global request, on a fixture with a hidden
  partner, a partner whose start time lies in the future, a partner whose end
  time has passed and a deleted partner. Assert the first three among the
  items and the deleted one absent. Run it on v13 and v14 before the change
  and record that it fails for the three, and why.
- [ ] 2.2 Extend the compiled form test with the same fixture: the three are
  offered and the deleted one is not, and a partnership whose partner is
  hidden still holds that partner in the compiled row and after an unchanged
  save. Keep the ACE-669 order fixture untouched. These pass before the
  change. Show them red by forcing the flag off in the handler for one run,
  then restore from a backup and compare checksums.
- [ ] 2.3 Rename `hiddenPartnersAreOfferedAsBefore()` to `hiddenPartnersAreOffered()`
  and rewrite its docblock to name the decision.

## 3. Implementation

- [ ] 3.1 In `packages/fgtclb/academic-partners/Classes/Backend/FormEngine/PartnerItems.php`,
  call `setIgnoreEnableFields(true)` on the settings the handler builds, and
  replace both `@todo` lines by a comment saying which partners are offered
  and why. Verify 2.1 is green on v13 and v14.
- [ ] 3.2 Verify that `PartnerRepository` is not part of the diff, and that
  the `null` check around the loop of the handler is still there. Both belong
  to ACE-443 item 5 in `ace-434-coverage-round-leftovers`.

## 4. ACE-671

- [ ] 4.1 In `PartnerRepositoryFindAllTest`, rename `hiddenPartnersAreNotReturned()`
  to `hiddenPartnersAreNotReturnedOutsideABackendRequest()`, and say in its
  docblock what it measures, where the select's guarantee is tested, and that
  it turns red if the flag moves into the repository. Show that by setting the
  flag in `findAll()` for one run.

## 5. Documentation

- [ ] 5.1 Add `packages/fgtclb/academic-partners/Documentation/Changelog/3.0/Important-PartnerSelectOffersHiddenPartners.rst`
  from `Build/Documentation/Templates/`: which partners the select offers,
  that nothing changes on v13 or v14, that installations coming from 2.x on
  TYPO3 v12 see hidden and timed partners for the first time, and what the
  frontend renders for a partnership whose partner is not visible (read it,
  do not change it). Check that the entry renders without a definition list.
- [ ] 5.2 `docs/architecture/backend-select-items.md`: a short section that a
  handler querying through Extbase states its enable field handling itself,
  because the core default depends on the global request, with `PartnerItems`
  as the example.

## 6. Branch `2`

- [ ] 6.1 Record the maintainer's answer to `design.md` decision 6 in the
  pull request. The work on `2` is a change of its own there, derived from a
  backport analysis, and is not ticked here.

## 7. Definition of done

- [ ] 7.1 After `composerUpdate` for TYPO3 v13: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` on SQLite (`-j auto`), PostgreSQL
  (`-b docker -j 8`) and MariaDB (`-j 8`) green.
- [ ] 7.2 After `composerUpdate` for TYPO3 v14: the same gates green.
- [ ] 7.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 7.4 `docs/` updated, `README.md` and `CONTRIBUTING.md` still only
  summarize and link.
- [ ] 7.5 Commit as `[TASK] ACE-672: Decide which partners are offered`
  (a `[BUGFIX]` if decision 1 is not A) in TYPO3 Core format, body at 72
  columns, footer `Resolves: ACE-672` and `Resolves: ACE-671`, no
  attribution of any kind.
- [ ] 7.6 Archive the change as the last commit of the same pull request.
