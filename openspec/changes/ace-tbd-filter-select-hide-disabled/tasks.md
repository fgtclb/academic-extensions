## 1. Tests first

- [ ] 1.1 Extend `FilterSelectViewHelperTest` with a disabled, unselected
  category and `hideDisabledOptions` enabled; assert the option is absent.
  Record that it fails on the unchanged code: the argument is not declared,
  so the tag receives it as a plain attribute and the option stays.
- [ ] 1.2 Add a case where the disabled category is the selected value;
  assert it is rendered, disabled and selected.
- [ ] 1.3 Add a grouped case (disabled parent, enabled child) and assert the
  parent stays as a disabled option above the child; add a grouped case
  where parent and child are both disabled and assert both are absent.
- [ ] 1.4 Add a `renderOptions=false` case and assert the `options` variable
  carries the same filtered list.
- [ ] 1.5 Verify every existing test of the class stays green without the
  argument.

## 2. Implementation

- [ ] 2.1 Register `hideDisabledOptions` and filter the linearised options in
  `FilterSelectViewHelper::getOptions()`; verify tests 1.1 to 1.4 pass on
  v13 and v14.

## 3. Documentation

- [ ] 3.1 Add `Documentation/Changelog/3.0/Feature-FilterSelectCanHideOptionsWithoutResults.rst`
  to `category_types`; verify it renders.
- [ ] 3.2 Document the argument where the extension's `Documentation/`
  describes the filter select, and in `docs/` if the list filter is
  described there.

## 4. File the issue

- [ ] 4.1 After implementation, file the ACE issue in YouTrack, rename the
  change to `ace-<NNN>-filter-select-hide-disabled`, and commit in TYPO3 Core
  format, e.g. `[FEATURE] ACE-<NNN>: Hide empty filter options`.

## 5. Backport

- [ ] 5.1 Backport: separate change on branch `2` after a backport analysis
  (`docs/workflow/backporting.md`) that diffs the filter form field between
  the two lines; not a cherry-pick.

## 6. Definition of done

- [ ] 6.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v13, all green.
- [ ] 6.2 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v14, all green.
- [ ] 6.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 6.4 `docs/` and the extension's `Documentation/` changelog updated in the
  same change.
- [ ] 6.5 Archive the change as the last commit of the pull request.
