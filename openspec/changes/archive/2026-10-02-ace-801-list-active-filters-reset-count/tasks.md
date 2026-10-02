## 1. Prerequisites

- [x] 1.1 Confirm `ace-723-list-filter-get-urls` and the `listings-08` filter
  settings change are merged; adopt the final `settings.filter` namespace.

## 2. Filter arguments ViewHelper in category_types

- [x] 2.1 Add `ct:filterArgument` returning the flat uid list with one
  category left out; rendering tests for no collection, one category, and
  two categories with one removed. Show the "one removed" test fails while
  the ViewHelper returns the full list.

## 3. Partials and settings per extension

- [x] 3.1 Partners: `Partner/ActiveFilters.html` and `Partner/ResultCount.html`,
  the three settings (TypoScript setup, site set settings definitions), and
  English/German labels. Functional rendering test with two active filters
  and all settings on: two tags whose links each keep exactly the other
  filter, a reset link without filter arguments, and the count. Show it
  fails on the unchanged templates.
- [x] 3.2 Partners regression: with the settings off the rendered list shows
  none of the new markup, only the surrounding whitespace changes. Show the
  test fails when a setting defaults to `1`.
- [x] 3.3 Projects: the same partials and tests for both list plugins,
  including the active state tag; show they fail on the unchanged templates.
- [x] 3.4 Programs: the same partials and tests; show they fail on the
  unchanged templates.
- [x] 3.5 Translated fixture: tag labels follow the current language; show the
  test fails when the default-language title is rendered.

## 4. Documentation

- [x] 4.1 `docs/architecture/`: extend the list demand URL section with the
  tag and reset links; link it from `docs/architecture/Index.md`.
- [x] 4.2 `Documentation/Configuration/` of the three extensions: the three
  settings; `Documentation/Changelog/3.0/Feature-ActiveFiltersResetAndResultCount.rst`
  in each, and `Feature-FilterArgumentViewHelper.rst` in
  `typo3-category-types`.

## 5. File the issue

- [x] 5.1 After implementation, file the ACE issue in YouTrack, rename the
  change to `ace-<NNN>-list-active-filters-reset-count`, and commit as
  `[FEATURE] ACE-801: Show active filters and a count` in TYPO3
  Core format.

## 6. Definition of done

- [x] 6.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v13; record the results.
- [x] 6.2 `composerUpdate`, then the same five suites for TYPO3 v14; record
  the results.
- [x] 6.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 6.4 `docs/` and every affected extension's `Documentation/` changelog
  updated; `README.md` and `CONTRIBUTING.md` still only link.
- [x] 6.5 Archive the change as the last commit of the pull request.
