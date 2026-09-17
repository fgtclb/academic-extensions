## 1. Tests first

- [x] 1.1 Write the first `Tests/Functional/Plugins/` tree of this extension:
  the fixture extension `test_bitejobs_stub` answering two job postings with
  different `department` values, the page fixture and the rendering
  TypoScript.
- [x] 1.2 Assert the job titles of an ungrouped list with header layout 1 are
  `<h2>`, and that no group heading is rendered. Record that it fails on the
  unchanged template.
- [x] 1.3 Assert the stored view values `List`, `Card`, `Table`, `ListView`,
  `CardView`, `TableView`, an empty value, an unknown value and a missing
  field each render their view. Record that the last six fail today.
- [x] 1.4 Assert that a list grouped by a TypoScript field renders one heading
  per value.
- [x] 1.5 Add a unit test for the view value enum.
- [x] 1.6 Add `Tests/Functional/Upgrades/ListViewFlexFormUpgradeWizardTest`
  with a CSV fixture covering the old values, an empty and an unknown one, a
  current one, a hidden row, a row without a FlexForm, another CType, a row
  without the view field and XML that does not parse.

## 2. Implementation

- [x] 2.1 Change the grouping condition and the `groupBy` argument in
  `Templates/BiteJobs/List.html`.
- [x] 2.2 Add the enum and the view normalisation in
  `BiteJobsController::initializeListAction()`, verified on v12 and v13.
- [x] 2.3 Add `Classes/Upgrades/ListViewFlexFormUpgradeWizard.php`, and declare
  `typo3/cms-install` in `composer.json` and `ext_emconf.php`; verify the test
  on SQLite and PostgreSQL, on v12 and v13.

## 3. Documentation

- [x] 3.1 Add `Documentation/Changelog/2.4/Important-BiteJobsListGroupsOnlyOnRequest.rst`.
- [x] 3.2 Document `settings.jobs.groupBy` and the view values in the
  configuration chapter, and fill the `Migration` section of the 2.1 breaking
  entry.
- [x] 3.3 Update `docs/`: the fixture extension page, the plugin test tree
  list of `docs/workflow/backporting.md`, and the counts of
  `docs/architecture/class-design.md`, `database-queries.md`,
  `core-version-aware-code.md` and `functional-tests.md`.

## 4. Definition of done

- [x] 4.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v12 (PHP 8.1), all green.
- [x] 4.2 The same for TYPO3 v13 (PHP 8.2).
- [x] 4.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 4.4 Archive the change as the last commit of the pull request.
