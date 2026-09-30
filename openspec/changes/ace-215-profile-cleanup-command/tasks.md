## 1. Backport analysis

- [x] 1.1 Diff the touched files against `main`, grep the APIs the change
  uses, and check the test harness. `ace-667` is merged here, the provider of
  the synchronisation is the same, `DataHandlerExecutionContext` and
  `ProfileWriteCorrelation` do not exist, the profile table has no workspace
  columns, and `Tests/Functional/Command/` does not exist yet.

## 2. Tests first

- [x] 2.1 Take over the functional test and its fixture from `main`, without
  the workspace version of the fixture. Show they fail before the change (the
  command does not exist).
- [x] 2.2 Break every rule the tests cover on purpose on v12, watch the tests
  go red, restore.

## 3. Implementation

- [x] 3.1 Add `DataHandlerExecutionContext` with `runAsLiveBackendUser()`, the
  provider without the workspace condition, the command without the
  correlation mark, and the `Services.yaml` registration. PHP 8.1 syntax.
  Verify group 2 on v12 and v13.
- [x] 3.2 Take over the changes the review of `main` brought: the cleanup
  limited to profiles the synchronisation manages, profiles of all languages,
  malformed page lists refused, and the list and detail cache tags flushed on
  delete and undelete in `DataHandlerHooks`.
- [x] 3.3 Take over the tests the second review of `main` brought: a profile
  the DataHandler refuses (exit code 1), and a translation deleted on its own
  and a restored profile in the hook. The announcement test of `main` has no
  counterpart, since a DataHandler save is not announced here.

## 4. Documentation

- [x] 4.1 `Documentation/Configuration/ProfileCleanup/Index.rst` and
  `Documentation/Changelog/2.4/Feature-ProfileCleanupCommand.rst`. Verify the
  rendering.
- [x] 4.2 Extend `docs/architecture/frontend-user-contact-import.md` with the
  selection rule, the reason it is not part of the update and the execution
  context of this branch. Verify `lintMarkdown -n`.

## 5. Commit

- [x] 5.1 Commit as `[FEATURE] ACE-215: Add a profile cleanup command` in
  TYPO3 Core format, with `Resolves: ACE-215`.

## 6. Definition of done

- [x] 6.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v12 and v13, and `functional` on PostgreSQL with
  `-j 8` for both, MariaDB and MySQL for the command test.
- [x] 6.2 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 6.3 `docs/` and the `academic-persons` `Documentation/` changelog
  updated, `README.md` and `CONTRIBUTING.md` still only summarize and link.
- [ ] 6.4 Archive the change as the last commit of the pull request.
