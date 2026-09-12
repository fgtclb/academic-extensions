## 1. Tests first, on TYPO3 v13

- [ ] 1.1 Add a functional form data test: a page of type 20 on a site whose
  settings set `plugin.tx_academicprograms.categoryRootUids` to `7` has
  `treeConfig.startingPoints` `7` on `categories`, and a site without the
  setting leaves it empty; run it before the change and record that the key
  is absent.
- [ ] 1.2 Add the same pair for the FlexForm field `settings.categories` of a
  program list element on a page of that site.
- [ ] 1.3 Add a case with two uids and one with a non-numeric value, asserting
  `7,9` and an empty value respectively.
- [ ] 1.4 Add a case for a site that depends on the program list component
  set only and writes the setting to its `settings.yaml` without a
  definition; assert `treeConfig.startingPoints` `7`. Prove it can fail by
  writing the value under a misspelled key once.

## 2. Implementation

- [ ] 2.1 Add `Configuration/Sets/Full/settings.definitions.yaml` with the
  setting and its label; verify it appears in the site settings editor of the
  `core-13` instance.
- [ ] 2.2 Add the `columnsOverrides` for page type 20 in
  `Configuration/TCA/Overrides/pages.php` and the marker in
  `Configuration/FlexForms/ProgramListSettings.xml`; state `parentField`
  explicitly if the test of 1.2 shows it missing.
- [ ] 2.3 Determine with a form data test whether a category assigned outside
  the root survives in the processed record, and record the outcome for the
  changelog.
- [ ] 2.4 Run the tests of group 1 green on v13, then on v14.

## 3. Documentation

- [ ] 3.1 Add a section "Category tree root" to
  `academic-programs/Documentation/Configuration/Index.rst`, including the
  outcome of 2.3, and state that a site using component sets only writes the
  setting to its `settings.yaml`, because the settings editor does not offer
  it there.
- [ ] 3.2 Add `Documentation/Changelog/3.0/Feature-CategoryTreeRootFromSiteSetting.rst`.
- [ ] 3.3 Mention in `docs/architecture/typoscript-and-site-sets.md` that
  settings definitions of academic extensions live on the aggregate set, if
  the page does not say so yet; verify with `lintMarkdown -n`.

## 4. File the issue

- [ ] 4.1 After implementation, file the ACE issue in YouTrack and verify the
  key.
- [ ] 4.2 Rename the change to `ace-<NNN>-category-root-site-setting`.
- [ ] 4.3 Commit as `[FEATURE] ACE-<NNN>: Start program category trees at a
  site root` in TYPO3 Core format.

## 5. Definition of done

- [ ] 5.1 After `composerUpdate` for TYPO3 v13: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` green.
- [ ] 5.2 After `composerUpdate` for TYPO3 v14: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` green.
- [ ] 5.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 5.4 `docs/` and the `Documentation/` changelog entry are part of the
  change; `README.md` and `CONTRIBUTING.md` still only summarize.
- [ ] 5.5 Archive the change as the last commit of the pull request.
