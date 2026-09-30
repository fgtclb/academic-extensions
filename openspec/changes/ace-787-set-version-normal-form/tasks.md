## 1. The test and the files

- [x] 1.1 Add `SetVersionNormalFormTest.php` byte-identical to `main`. It is red
  on the unchanged tree for exactly the three `ext_emconf.php` and the two
  instance manifests.
- [x] 1.2 Run `bin/set-version 2.4.0 post-release` and keep what it writes,
  nothing but those five files. A second run changes nothing, fixtures
  included, and the test is green.

## 2. Documentation

- [x] 2.1 `docs/testing/fixture-extensions.md`, `docs/workflow/releasing.md`
  (the no-op run, the checklist item), `docs/testing/unit-tests.md` (section,
  class count), `AGENTS.md` (five `monorepo-shared` tests).

## 3. Definition of done

- [x] 3.1 `lintPhp` green.
- [x] 3.2 After `-t 12 -s composerUpdate`: `cgl -n`, `phpstan`, `unit` and
  `functional -j auto` (SQLite) green with `-t 12`.
- [x] 3.3 The same with `-t 13` after its own `composerUpdate`.
- [x] 3.4 `lintMarkdown -n` green.
- [x] 3.5 No changelog entry: nothing an installation observes changes.
- [x] 3.6 Commit `[BUGFIX] ACE-787: Store files in set-version form`, TYPO3
  Core format, no attribution of any tool.
- [ ] 3.7 Archive the change as the last commit of the pull request.
