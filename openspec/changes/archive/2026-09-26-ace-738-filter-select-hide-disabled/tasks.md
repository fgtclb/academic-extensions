## 1. Tests first

Carried over from `main`, where the tests were written first and every keep
rule was broken once; the test class is identical here, and the red run of
this branch is 2.1.

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
  `FilterSelectViewHelper::getOptions()`, add `isBoundValue()` to
  `AbstractSelectViewHelper`; verify the tests of group 1 fail without it on
  v12 (11 of them) and pass with it on v12 and v13.

## 3. Documentation

- [x] 3.1 Add `Documentation/Changelog/2.4/Feature-FilterSelectCanHideOptionsWithoutResults.rst`
  to `category_types`, the same entry as on `main`; verify it renders.
- [x] 3.2 `docs/`: no page of this branch describes the list filter, so the
  changelog entry is the reference; stated in the pull request.

## 4. Issue

- [x] 4.1 ACE-738, filed for `main` and this branch; commit in TYPO3 Core
  format: `[FEATURE] ACE-738: Hide empty filter select options`.

## 5. Definition of done

- [x] 5.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v12, all green.
- [x] 5.2 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v13, all green.
- [x] 5.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 5.4 The extension's `Documentation/` changelog updated in the same
  change; `docs/`: nothing to change (3.2).
- [x] 5.5 Commit message in TYPO3 Core format with `Resolves: ACE-738`, no
  attribution.
- [x] 5.6 Archive the change as the last commit of the pull request.
