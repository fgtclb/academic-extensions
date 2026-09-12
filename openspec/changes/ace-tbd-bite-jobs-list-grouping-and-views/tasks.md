## 1. Tests first

- [ ] 1.1 Extend `AcademicBiteJobsListPluginTest`: the HTTP stub returns two
  job postings, the content element has header layout 1 and no subheader;
  assert each job title is an `<h2>`. Record that it fails on the unchanged
  template (the titles are `<h3>`).
- [ ] 1.2 Add fixtures with the view values `ListView`, `CardView` and an
  empty value; assert the list, card and list markup. Record that they fail
  on the unchanged code (missing partial).
- [ ] 1.3 Add a test with `plugin.tx_academicbitejobs.settings.jobs.groupBy`
  naming a field the stub sets with two values; assert two group headings.
- [ ] 1.4 Add a unit test for the view value enum: `List`, `Card`, `Table`
  map to themselves, `ListView`, `CardView`, `TableView` to their view, an
  empty and an unknown value to `List`.
- [ ] 1.5 Add `Tests/Functional/Upgrades/ListViewFlexFormUpgradeWizardTest`
  with a CSV fixture: job list elements storing `ListView`, `CardView`,
  `TableView`, an empty value, an unknown value and `Table`, a hidden one
  storing `CardView`, one without `pi_flexform`, and another CType storing
  `ListView`. Assert `updateNecessary()` before and after, the rewritten
  values, the untouched rows and an unchanged second FlexForm field. Record
  that it fails before the wizard exists, and break the value check once to
  watch the untouched-row assertion go red.

## 2. Implementation

- [ ] 2.1 Change the grouping condition and the `groupBy` argument in
  `Templates/BiteJobs/List.html`; verify tests 1.1 and 1.3 pass.
- [ ] 2.2 Add the enum and the view normalisation in
  `BiteJobsController::initializeListAction()`; verify tests 1.2 and 1.4
  pass on v13 and v14, which also shows the normalised `settings` reach the
  view on both.
- [ ] 2.3 Add `Classes/Upgrades/ListViewFlexFormUpgradeWizard.php` as
  designed, using the enum; verify test 1.5 on SQLite and PostgreSQL, on v13
  and v14.
- [ ] 2.4 Update the v15 blocker counts: the table and the upgrade wizard
  sentence of `AGENTS.md`, `docs/architecture/core-version-aware-code.md`
  and `docs/architecture/dependency-injection.md` (11 wizards in 6
  extensions become 12 in 7), and add the wizard to the call site list of
  ACE-294 in YouTrack.

## 3. Documentation

- [ ] 3.1 Add `Documentation/Changelog/3.0/Important-BiteJobsListGroupsOnlyOnRequest.rst`
  to `academic_bite_jobs`, naming the heading level change, the accepted
  old view values, the upgrade wizard and when to run it, and the
  TypoScript grouping setting; verify it renders.
- [ ] 3.2 Document `settings.jobs.groupBy` in the configuration chapter of the
  extension's `Documentation/`, and fill the `Migration` section of the 2.1
  breaking entry that still says `[TODO]`.
- [ ] 3.3 Check `docs/` for statements about bite jobs views or grouping and
  update them; state in the pull request when nothing needed a change.

## 4. File the issue

- [ ] 4.1 After implementation, file the ACE issue in YouTrack, rename the
  change to `ace-<NNN>-bite-jobs-list-grouping-and-views`, and commit in
  TYPO3 Core format, e.g. `[BUGFIX] ACE-<NNN>: Repair bite jobs list grouping`.

## 5. Backport

- [ ] 5.1 Backport: separate change on branch `2` after a backport analysis
  (`docs/workflow/backporting.md`), reading that branch's own rules for
  upgrade wizards before deciding whether the wizard is part of it.

## 6. Definition of done

- [ ] 6.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v13, all green.
- [ ] 6.2 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v14, all green.
- [ ] 6.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 6.4 `docs/` and the extension's `Documentation/` changelog updated in the
  same change.
- [ ] 6.5 Archive the change as the last commit of the pull request.
