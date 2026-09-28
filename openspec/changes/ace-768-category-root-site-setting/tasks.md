## 1. Tests first, on TYPO3 v13

- [x] 1.1 Add a functional form data test: a page of type 20 on a site whose
  settings set `plugin.tx_academicprograms.categoryRootUids` to `7` has
  `treeConfig.startingPoints` `7` on `categories`, and a site without the
  setting leaves it unset. Run it before the change and record that the key
  is absent.
- [x] 1.2 Add the same pair for the FlexForm field `settings.categories` of a
  program list element and `settings.preselectedCategories` of a program
  finder element on a page of that site.
- [x] 1.3 Add a case with two uids and one with a non-numeric value, asserting
  `7,9` and no starting points respectively.
- [x] 1.4 Add a case for a site that depends on the program list component
  set only and writes the setting to its settings without a definition, once
  as a tree (`7`) and once with a dotted key (whole tree).
- [x] 1.5 Load the tree items through `FormSelectTreeAjaxController`, as the
  form does, and assert the items and that the top node of the whole tree
  cannot be selected.
- [x] 1.6 Add a case for a list written as a tree on a site without the
  definition, and one for a new program page.

## 2. Implementation

- [x] 2.1 Add the setting to `Configuration/Sets/Full/settings.definitions.yaml`
  with its label.
- [x] 2.2 Add the `columnsOverrides` for page type 20 in
  `Configuration/TCA/Overrides/pages.php` and the marker in both FlexForms.
  `parentField` is added by core for every category field.
- [x] 2.3 Add the form data provider `CategoryTreeRoot` and register it in
  `tcaDatabaseRecord`, `flexFormSegment` and `tcaSelectTreeAjaxFieldData`.
- [x] 2.4 Determine with a form data test whether a category assigned outside
  the root survives in the processed record: it does.
- [x] 2.5 Pin that page TSconfig starting points still win.
- [x] 2.6 Accept a list, as core does, and declare the dependency on
  `TcaColumnsOverrides`.
- [x] 2.7 Run the tests of group 1 green on v13, then on v14.

## 3. Documentation

- [x] 3.1 Add a section "Category tree root" to
  `academic-programs/Documentation/Configuration/Index.rst`, including the
  outcome of 2.4 and 2.5, and the nested form for a site that uses component
  sets only.
- [x] 3.2 Add `Documentation/Changelog/3.0/Feature-CategoryTreeRootFromSiteSetting.rst`.
- [x] 3.3 Describe in `docs/architecture/typoscript-and-site-sets.md` which
  sets of the academic extensions declare their settings and why, and what a
  site without the declaring set can write. Describe the marker, the provider
  and how to test a tree in `docs/architecture/backend-select-items.md`.

## 4. File the issue

- [x] 4.1 After implementation, file the ACE issue in YouTrack and verify the
  key: ACE-768.
- [x] 4.2 Rename the change to `ace-768-category-root-site-setting`.
- [x] 4.3 Commit as `[FEATURE] ACE-768: Set category tree root per site` in
  TYPO3 Core format.

## 5. Definition of done

- [x] 5.1 After `composerUpdate` for TYPO3 v13: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` green.
- [x] 5.2 After `composerUpdate` for TYPO3 v14: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` green.
- [x] 5.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 5.4 `docs/` and the `Documentation/` changelog entry are part of the
  change, and `README.md` and `CONTRIBUTING.md` still only summarize.
- [ ] 5.5 Archive the change as the last commit of the pull request.
