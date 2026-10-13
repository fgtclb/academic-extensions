## 1. Before the change

- [ ] 1.1 Re-measure the premises of `design.md`, *Context*, on `2` HEAD for
  v12 and v13. Done when each is confirmed or the change is updated first.
- [ ] 1.2 Work in a git worktree below `.agent/tmp/wt/`, on a branch from `2`.

## 2. Tests first

- [ ] 2.1 `PartnerItemsTest` with a fixture of a visible, a hidden, a future,
  an expired and a deleted partner, the handler called without a request.
- [ ] 2.2 `PartnerSelectVisibilityTest` with a fixture of its own: the four
  partners that are not deleted offered, and a partnership with each partner
  that is not visible shown as chosen and kept on an unchanged save.
- [ ] 2.3 `PartnerSelectOrderTest`: one shared test per version split pair,
  the class docblock without the paragraph about the split.
- [ ] 2.4 `PartnerRepositoryFindAllTest`: the rename and docblock of `main`.
- [ ] 2.5 Run 2.1 to 2.3 on unchanged code for v12 and v13 and record the
  results here.

## 3. Implementation

- [ ] 3.1 `PartnerItems`: `setIgnoreEnableFields(true)` and the comment of
  `main`, the item icon of this branch kept. 2.1 to 2.4 pass on v12 and v13.
- [ ] 3.2 Show 2.2 red on v13 with the flag forced off in the handler, and 2.4
  red on v12 and v13 with the flag set inside `findAll()`. Restore from a
  backup and compare checksums.
- [ ] 3.3 `PartnerRepository` is not part of the diff.

## 4. Documentation

- [ ] 4.1 `Documentation/Changelog/2.4/Important-PartnerSelectOffersHiddenPartners.rst`.
- [ ] 4.2 `docs/architecture/backend-select-items.md`: the section of `main`
  adjusted to v12 and v13, and `MISSING LABEL` in the table.

## 5. Definition of done

- [ ] 5.1 After `composerUpdate` for TYPO3 v12: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` on SQLite (`-j auto`), PostgreSQL
  (`-b docker -j 8`) and MariaDB (`-j 8`) green.
- [ ] 5.2 After `composerUpdate` for TYPO3 v13: the same gates green.
- [ ] 5.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 5.4 Commit `[BUGFIX] ACE-672: Offer every partner on TYPO3 v12`, footers
  `Resolves: ACE-672` and `Resolves: ACE-671`, no attribution of any kind.
- [ ] 5.5 Archive the change as the last commit of the same pull request.
