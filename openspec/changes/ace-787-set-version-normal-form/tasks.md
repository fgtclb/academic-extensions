## 1. The test

- [x] 1.1 Add `packages-dev/monorepo-shared/Tests/Unit/SetVersionNormalFormTest.php`
  with the four checks of design.md. Verify it is red on the unchanged tree for
  the three checks that describe today's drift (comments, key order,
  `providesPackages`) and green for the `sort-packages` check.
- [x] 1.2 Show the `sort-packages` check red by moving one `require` entry of
  `core-13/composer.json` out of order, and restore it.

## 2. The files

- [x] 2.1 Remove the comments of `academic-base/ext_emconf.php` and of the four
  fixtures `academic_test_configuration`, `test_upgrade_check`,
  `test_upgrade_check_project`, `test_upgrade_check_shared`.
- [x] 2.2 With the tools on `PATH`, run `bin/set-version 3.0.0 post-release`
  in the worktree and keep what it writes: the constraint order of five
  `ext_emconf.php` and `[]` in twelve manifests, nothing else. Run it a second
  time and verify `git status` shows no further change, fixtures included.
- [x] 2.3 Verify the test of 1.1 is green now.

## 3. Documentation

- [x] 3.1 `docs/testing/fixture-extensions.md`: in "Adding one", why `version`
  and `Package.providesPackages: []` are required in the fixture manifest on
  TYPO3 v14, that `ext_emconf.php` carries no comments, and
  `pkw extemconf:normalize` for a new one.
- [x] 3.2 `docs/workflow/releasing.md`: `bin/set-version` with the current
  version leaves a clean tree, why (the tools write the files, and they are
  stored in that form), and a pre-release checklist item for that run.
- [x] 3.3 `docs/testing/unit-tests.md` (a section for the new test, the class
  count) and `AGENTS.md` (seven `monorepo-shared` tests).

## 4. Definition of done

- [x] 4.1 `lintPhp` green.
- [x] 4.2 After `-t 13 -s composerUpdate`: `cgl -n`, `phpstan`, `unit` and
  `functional -j auto` (SQLite, fixture manifests and `ext_emconf.php` change)
  green with `-t 13`.
- [x] 4.3 The same with `-t 14` after its own `composerUpdate`.
- [x] 4.4 `lintMarkdown -n` green.
- [x] 4.5 No changelog entry: nothing an installation observes changes.
- [x] 4.6 Commit `[BUGFIX] ACE-787: Store files in set-version form`, TYPO3
  Core format, no attribution of any tool.
- [ ] 4.7 Archive the change as the last commit of the pull request.
- [ ] 4.8 Backport: a change of its own on branch `2`, where the comments and
  `providesPackages` do not apply and the instance manifests need sorting.
