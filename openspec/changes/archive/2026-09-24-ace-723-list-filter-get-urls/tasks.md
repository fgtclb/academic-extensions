## 1. Reverse filter normalisation in category_types

- [x] 1.1 Add `CategoryFilterNormalizer::toFilterArgument()` returning one
  comma separated uid list in ascending order; add unit tests for no
  collection, one category, several categories attached out of order and a
  round trip through `toUidList()`, and show the round-trip test fails while
  the method returns an empty string.

## 2. Redirect in the list actions

- [x] 2.1 Partners: `DemandFactory::createDemandArguments()`, and a thrown
  redirect for a POST with a demand in `listAction()` and `mapAction()`; a
  functional request test submits the rendered form with one region and
  asserts 303, a Location carrying the uid and a cHash, then GETs the
  Location and asserts the filtered list. Shown to fail on the unchanged
  controller (the POST answers 200), and with a returned instead of a thrown
  redirect on v13.
- [x] 2.2 Partners: functional test that a foreign category uid, an unknown
  uid and the form bookkeeping fields are absent from the Location; shown to
  fail when the raw submitted array is forwarded instead of the normalised
  one. A POST without a demand is not redirected; shown to fail without the
  demand check.
- [x] 2.3 Projects: the same for both list plugins, including `activeState`
  and an invalid active state falling back to the default; shown to fail on
  the unchanged controller.
- [x] 2.4 Programs: the same for the program list; shown to fail on the
  unchanged controller.
- [x] 2.5 Regression: the GET of each redirect target renders the list the
  POST rendered before, and a GET without arguments renders the list as the
  content element presets it, for all three extensions. The existing program
  test that posted a demand follows the redirect now.
- [x] 2.6 Preselection: with a content element that preselects a category, a
  submission that clears it redirects to a URL carrying the sorting and no
  filter, and the list behind it is unfiltered. Shown to fail when the
  sorting is omitted (the preset comes back).
- [x] 2.7 Programs: drop `defaults` from `Configuration/Yaml/Routes.yaml`; the
  routing test asserts the default sorting stays in the path, a sorting
  submission redirects to the enhanced path, a filter stays in the query
  string without a cHash, and clearing a preselected degree does not end on the
  bare page. Shown to fail with the `defaults` restored.
- [x] 2.8 `FrontendPluginRenderingTrait`: `submitFrontendForm()`,
  `frontendPostRequest()` and `assertSeeOtherWithCacheHash()` for the tests
  above; `submitFrontendForm()` collects the fields of the controls these
  forms use and fails on a replaced field the form did not render.
- [x] 2.9 Read the demand of the redirect from the parsed body of the plugin's
  own namespace only (protected `redirectFilterSubmission()`,
  `ExtensionService` through the final
  `injectFilterRedirectExtensionService()`); tests that a POST to a filtered
  URL replaces its selection and that another plugin's POST to it is not
  redirected, shown to fail with the merged action argument.
- [x] 2.10 Exclude `^tx_<extension>_<plugin>[demand]` from the cache hash in
  each list extension's `ext_localconf.php`; tests that two selections share
  one cache hash, shown to fail without the exclusion, and that two filter
  URLs served from the real page cache (database backend) each show their own
  selection without a second cache entry, shown to fail with a cacheable
  list action and without the exclusion. The filter URL tests and the
  enhancer test run with `enforceValidation` on, as new installations do; a
  demand-only URL without a cHash renders filtered, shown to be a 404
  without the exclusion. The enhancer test
  asserts a filter needs no cache hash behind the route, that a link without
  sorting keeps its query string, and that a one segment path is a 404.
- [x] 2.11 A submission on a translated page redirects within its language,
  shows the translated category and carries the default language uid, which
  the translated list reads back (partners). It fails on the unchanged
  controller like the other redirect tests.

## 3. Documentation

- [x] 3.1 `docs/architecture/list-filter-urls.md`: the list demand URL
  shape (keys, what is always carried, why the actions stay non-cacheable,
  why the redirect is thrown, why an enhancer must not declare defaults),
  linked from `docs/architecture/Index.md`; the new trait methods in
  `docs/testing/testing-helper.md` and a form submission section in
  `docs/testing/functional-tests.md`.
- [x] 3.2 `Documentation/Changelog/3.0/Feature-FilterSelectionsHaveAUrl.rst`
  in `academic-partners`, `academic-projects` and `academic-programs`
  (303 instead of 200, `fetch()` note, subclass note, enhancer note),
  `Important-RouteEnhancerKeepsTheDefaultSorting.rst` in `academic-programs`
  with its route enhancer page and the ACE-454 entry of the same release,
  and `Feature-CategoryFilterArgument.rst` in `typo3-category-types`; all
  picked up by the `Feature-*`/`Important-*` globs of their `Index.rst`.
- [x] 3.3 `Important-ListDemandIsNotPartOfTheCacheHash.rst` in the three
  list extensions; the event documentation says the events no longer see the
  submission itself.

## 4. File the issue

- [x] 4.1 After implementation, file the ACE issue in YouTrack (relates to
  ACE-125): ACE-723, Story, Version 3.0.0, relates to ACE-125, ACE-612 and
  THB-614. Rename the change to `ace-723-list-filter-get-urls`, and commit as
  `[FEATURE] ACE-723: Redirect list filters to GET URLs` in TYPO3 Core
  format.

## 5. Definition of done

- [x] 5.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v13: green; unit 996 tests, functional 2577 tests
  (SQLite, 16 chunks), the list plugin and program tests on PostgreSQL.
- [x] 5.2 `composerUpdate`, then the same five suites for TYPO3 v14: green;
  unit 991 tests, functional 2592 tests (SQLite, 16 chunks), the list plugin
  and program tests on PostgreSQL.
- [x] 5.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 5.4 `docs/` and every affected extension's `Documentation/` changelog
  updated; `README.md` and `CONTRIBUTING.md` still only link.
- [x] 5.5 Archive the change as the last commit of the pull request.
