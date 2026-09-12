## 1. Tests first

- [ ] 1.1 Add a functional test for `academic:updateprofiles`: a profile with
  an end time in the past, linked to a frontend user whose last name changed,
  gets the new last name and keeps its end time. Show it fails on the
  unchanged code (the profile row is untouched).
- [ ] 1.2 Add the same for a start time in the future and for a profile
  restricted to a frontend user group. Show both fail before the change.
- [ ] 1.3 Add a functional test for `academic:createprofiles`: a frontend
  user with an end time in the past gets a profile. Show it fails before the
  change (no profile created). Add a deleted frontend user that stays
  unselected.
- [ ] 1.4 Add a test for the enable field list: with a TCA override that
  removes `fe_group` from the profile's enable columns, the ignored list of
  the synchronisation lookup is exactly `disabled`, `starttime` and
  `endtime`. Show it fails against a hard-coded list of all four. In the
  same setup, a profile whose end time has passed is still updated; that
  assertion guards the behaviour and passes with either list.
- [ ] 1.5 Add or extend functional tests proving that the "show hidden
  records" listing, the selected-profiles plugin and the detail view still
  exclude a profile whose end time has passed. Verify they pass before and
  after the change. They guard the shared helper, which must not change.

## 2. Implementation

- [ ] 2.1 Add the sync-only helper and use it in `findByFrontendUser()` for
  `$showHidden = true`, leaving the shared helper untouched, as decided in
  `design.md`. Derive its list from the TCA schema of the profile table,
  after checking the four restriction capabilities exist on v14 as on v13.
  Verify 1.1, 1.2, 1.4 and 1.5.
- [ ] 2.2 Remove the start and end time restrictions in both frontend-user
  queries. Verify 1.3.

## 3. Documentation

- [ ] 3.1 Add
  `academic-persons/Documentation/Changelog/3.0/Important-SynchronizationIgnoresTheVisibilityWindow.rst`,
  referring to the 2.4 entry about hidden profiles. Verify it renders.
- [ ] 3.2 Extend `docs/architecture/frontend-user-contact-import.md` with the
  lookup rule (which enable fields the sync ignores, and that it never writes
  them). Verify `lintMarkdown -n`.

## 4. File the issue

- [ ] 4.1 After implementation, file the ACE issue in YouTrack as a
  follow-up of ACE-242, rename the change to `ace-<NNN>-<slug>`, and commit
  in TYPO3 Core format as `[BUGFIX] ACE-<NNN>: <subject>`.
- [ ] 4.2 Backport: a separate change on branch `2` after a backport analysis
  (`docs/workflow/backporting.md`). The same code is there, and a project on
  branch `2` needs a 2.4.0 release carrying ACE-242 and this change.

## 5. Definition of done

- [ ] 5.1 `lintPhp`, `cgl -n`, `phpstan`, `unit` and `functional` green for
  TYPO3 v13 and v14, each after its own `composerUpdate`.
- [ ] 5.2 `functional` also on PostgreSQL for the new sync tests, because
  they write.
- [ ] 5.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 5.4 `docs/` and the extension's `Documentation/` changelog updated in
  the same change.
- [ ] 5.5 Archive the change as the last commit of the pull request.
