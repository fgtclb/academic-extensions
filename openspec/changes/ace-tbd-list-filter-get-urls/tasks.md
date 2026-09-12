## 1. Reverse filter normalisation in category_types

- [ ] 1.1 Add `CategoryFilterNormalizer::toFilterArgument()` returning one
  comma separated uid list in ascending order; add unit tests for no
  collection, one category, categories of several types and a round trip
  through `toUidList()`, and show the round-trip test fails while the method
  returns an empty string.

## 2. Redirect in the list actions

- [ ] 2.1 Partners: redirect a POST with a demand in `listAction()` and
  `mapAction()`; add a functional request test that POSTs one region and
  asserts 303, a Location carrying the uid and a cHash, then GETs the
  Location and asserts the filtered list. Show it fails on the unchanged
  controller (today the POST answers 200).
- [ ] 2.2 Partners: functional test that a foreign category uid and the form
  bookkeeping fields are absent from the Location; show it fails when the raw
  submitted array is forwarded instead of the normalised one.
- [ ] 2.3 Projects: the same for both list plugins, including `activeState`
  and an invalid active state falling back to the default; show the tests
  fail on the unchanged controller.
- [ ] 2.4 Programs: the same for the program list; show the tests fail on the
  unchanged controller.
- [ ] 2.5 Regression: a GET request with the redirect's arguments renders the
  same list as the former POST, and a GET without arguments renders the list
  as the content element presets it, for all three extensions.
- [ ] 2.6 Preselection: with a content element that preselects a category, a
  submission that clears it redirects to a URL carrying the sorting and no
  filter, and the list behind it is unfiltered. Show the test fails when the
  sorting is omitted for default values (the preset comes back).

## 3. Documentation

- [ ] 3.1 `docs/architecture/`: a section on the list demand URL shape
  (keys, omitted defaults, why the actions stay non-cacheable), linked from
  `docs/architecture/Index.md`.
- [ ] 3.2 `Documentation/Changelog/3.0/Important-FilterSubmissionsRedirectToGetUrls.rst`
  in `academic-partners`, `academic-projects` and `academic-programs`
  (303 instead of 200, `fetch()` note, subclass note), and
  `Feature-CategoryFilterArguments.rst` in `typo3-category-types`; verify
  each is picked up by its `Index.rst` glob.

## 4. File the issue

- [ ] 4.1 After implementation, file the ACE issue in YouTrack, rename the
  change to `ace-<NNN>-list-filter-get-urls`, and commit as
  `[FEATURE] ACE-<NNN>: Redirect list filters to GET URLs` in TYPO3 Core
  format.

## 5. Definition of done

- [ ] 5.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v13; record the results.
- [ ] 5.2 `composerUpdate`, then the same five suites for TYPO3 v14; record
  the results.
- [ ] 5.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 5.4 `docs/` and every affected extension's `Documentation/` changelog
  updated; `README.md` and `CONTRIBUTING.md` still only link.
- [ ] 5.5 Archive the change as the last commit of the pull request.
