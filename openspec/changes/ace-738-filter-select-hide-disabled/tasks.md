## 1. Tests first

- [x] 1.1 Extend `FilterSelectViewHelperTest` with a disabled, unselected
  category and `hideDisabledOptions` enabled; assert the option is absent.
  Record that it fails on the unchanged code: the argument is not declared,
  so the tag receives it as a plain attribute and the option stays.
- [x] 1.2 Add a case where the disabled category is the selected value;
  assert it is rendered, disabled and selected.
- [x] 1.3 Add a grouped case (disabled parent, enabled child) and assert the
  parent stays as a disabled option above the child; add a grouped case
  where parent and child are both disabled and assert both are absent.
- [x] 1.4 Add a `renderOptions=false` case and assert the `options` variable
  carries the same filtered list.
- [x] 1.5 Verify every existing test of the class stays green without the
  argument.
- [x] 1.6 Pin the rules of the apply: an option selected only by
  `selectAllByDefault` is left out; without grouping a disabled parent is
  left out even when its child is shown; the selection is compared the way
  the selected marker compares it; the call the list partials make
  (`optionValueField`, an object value, a prepended option). Break each keep
  rule once (the selection, the descendant, the level boundary, the bound
  value, a strict comparison) and watch its tests go red.

## 2. Implementation

- [x] 2.1 Register `hideDisabledOptions` and filter the linearised options in
  `FilterSelectViewHelper::getOptions()`; verify the tests of group 1 pass
  (the whole class, on v13 and v14; the full gates are 6.1 and 6.2).

## 3. Documentation

- [x] 3.1 Add `Documentation/Changelog/2.4/Feature-FilterSelectCanHideOptionsWithoutResults.rst`
  to `category_types` - `2.4/` on both branches, because 2.4.0 ships it
  first; verify it renders.
- [x] 3.2 Document the argument where the extension's `Documentation/`
  describes the filter select, and in `docs/` if the list filter is
  described there. The extension's `Documentation/` has no chapter on the view
  helpers, so the changelog entry is its reference; `docs/` got a paragraph in
  `architecture/list-filter-types.md`.

## 4. File the issue

- [x] 4.1 After implementation, file the ACE issue in YouTrack (ACE-738),
  rename the change to `ace-738-filter-select-hide-disabled`, and commit in
  TYPO3 Core format: `[FEATURE] ACE-738: Hide empty filter select options`.

## 5. Backport

- [ ] 5.1 Backport: separate change on branch `2` after a backport analysis
  (`docs/workflow/backporting.md`) that diffs the filter form field between
  the two lines; not a cherry-pick.

## 6. Definition of done

- [x] 6.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v13, all green.
- [x] 6.2 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v14, all green.
- [x] 6.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 6.4 `docs/` and the extension's `Documentation/` changelog updated in the
  same change.
- [ ] 6.5 Archive the change as the last commit of the pull request.
