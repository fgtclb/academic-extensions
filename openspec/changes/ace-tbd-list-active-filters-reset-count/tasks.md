## 1. Prerequisites

- [ ] 1.1 Confirm `ace-tbd-list-filter-get-urls` and the `listings-08` filter
  settings change are merged; adopt the final `settings.filter` namespace.

## 2. Filter arguments ViewHelper in category_types

- [ ] 2.1 Add `ct:filterArgument` returning the flat uid list with one
  category left out; rendering tests for no collection, one category, and
  two categories with one removed. Show the "one removed" test fails while
  the ViewHelper returns the full list.

## 3. Partials and settings per extension

- [ ] 3.1 Partners: `Partner/ActiveFilters.html` and `Partner/ResultCount.html`,
  the three settings (TypoScript setup, site set settings definitions), and
  English/German labels. Functional rendering test with two active filters
  and all settings on: two tags whose links each keep exactly the other
  filter, a reset link without filter arguments, and the count. Show it
  fails on the unchanged templates.
- [ ] 3.2 Partners regression: with the settings off the rendered list equals
  today's markup; show the test fails when a setting defaults to `1`.
- [ ] 3.3 Projects: the same partials and tests for both list plugins,
  including the active state tag; show they fail on the unchanged templates.
- [ ] 3.4 Programs: the same partials and tests; show they fail on the
  unchanged templates.
- [ ] 3.5 Translated fixture: tag labels follow the current language; show the
  test fails when the default-language title is rendered.

## 4. Documentation

- [ ] 4.1 `docs/architecture/`: extend the list demand URL section with the
  tag and reset links; link it from `docs/architecture/Index.md`.
- [ ] 4.2 `Documentation/Configuration/` of the three extensions: the three
  settings; `Documentation/Changelog/3.0/Feature-ActiveFiltersResetAndResultCount.rst`
  in each, and `Feature-FilterArgumentsViewHelper.rst` in
  `typo3-category-types`.

## 5. File the issue

- [ ] 5.1 After implementation, file the ACE issue in YouTrack, rename the
  change to `ace-<NNN>-list-active-filters-reset-count`, and commit as
  `[FEATURE] ACE-<NNN>: Offer active filter tags and a reset link` in TYPO3
  Core format.

## 6. Definition of done

- [ ] 6.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v13; record the results.
- [ ] 6.2 `composerUpdate`, then the same five suites for TYPO3 v14; record
  the results.
- [ ] 6.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 6.4 `docs/` and every affected extension's `Documentation/` changelog
  updated; `README.md` and `CONTRIBUTING.md` still only link.
- [ ] 6.5 Archive the change as the last commit of the pull request.
