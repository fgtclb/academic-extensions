## 1. Prerequisite

- [ ] 1.1 Verify `ace-tbd-sync-lookups-ignore-time-window` is merged.

## 2. Tests first

- [ ] 2.1 Add functional tests in `academic-persons/Tests/Functional/Command/`
  for a disabled user (profile hidden, also its translation) and a user past
  its end time (hidden). Show they fail before the change (the command does
  not exist).
- [ ] 2.2 Add tests for a deleted user and for a missing `fe_users` row
  (profile and contracts deleted, history entry present). Add tests for a
  second active user, a `skip_sync` profile and a profile without a linked
  user (all unchanged).
- [ ] 2.3 Add tests for `--disabled=keep`, `--deleted=hide`,
  `--deleted=keep`, pid filtering and `--dry-run` (listing present, database
  unchanged). Break the dry-run guard on purpose and watch the test go red,
  then restore it.

## 3. Implementation

- [ ] 3.1 Add the stateless provider, the command and its `Services.yaml`
  registration. Verify 2.1 to 2.3 on v13 and v14.

## 4. Documentation

- [ ] 4.1 Document the command next to the documentation of
  `academic:createprofiles` and `academic:updateprofiles`, and add
  `academic-persons/Documentation/Changelog/3.0/Feature-ProfileCleanupCommand.rst`.
  Verify the rendering.
- [ ] 4.2 Extend `docs/architecture/frontend-user-contact-import.md` with the
  selection rule and the reason it is not part of the update. Verify
  `lintMarkdown -n`.

## 5. File the issue

- [ ] 5.1 After implementation, file or confirm the ACE issue in YouTrack
  (ACE-215 exists), rename the change to `ace-<NNN>-<slug>`, and commit in
  TYPO3 Core format as `[FEATURE] ACE-<NNN>: <subject>`.
- [ ] 5.2 Backport: a separate change on branch `2` after a backport analysis
  (`docs/workflow/backporting.md`). The DataHandler execution helper does not
  exist there and has to be replaced.

## 6. Definition of done

- [ ] 6.1 `lintPhp`, `cgl -n`, `phpstan`, `unit` and `functional` green for
  TYPO3 v13 and v14, each after its own `composerUpdate`.
- [ ] 6.2 `functional` also on PostgreSQL, MariaDB and MySQL for the command
  tests. They write and the selection query joins three tables.
- [ ] 6.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 6.4 `docs/` and the extension's `Documentation/` changelog updated in
  the same change.
- [ ] 6.5 Archive the change as the last commit of the pull request.
