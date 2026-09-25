## 1. Tests first, on TYPO3 v13

- [x] 1.1 Add a functional plugin test: a finder on page 1 with
  `settings.listPid = 2` renders a form targeting page 2 in the namespace
  `tx_academicprograms_programlist`, with the preselected option selected and
  an option without programs disabled. Run it before the change and record
  that the content type is unknown.
  `Plugins/AcademicProgramsFinderTest` (finder on `/home`, list on
  `/programs`); before the change 9 of its 11 tests failed on "The page
  renders no single finder form" - the content type had no rendering
  definition. The two that passed are guards: 1.2's shape pin, and "no form
  without a target page", which can only fail once there is a finder.
- [x] 1.2 Add a test submitting `demand[filterCollection][degree]` to the
  list page and expecting only matching programs, pinning the shape the
  finder relies on. Twice: posted directly to the list page, and submitted
  through the rendered finder form (`submitFrontendForm()`), following the
  `303` to the list, which shows the matching programs and the selection in
  its own filter.
- [x] 1.3 ~~Add a TCA test that saving a finder without `settings.listPid`
  fails validation.~~ The premise was false: the DataHandler does not check
  `required` on a relation field on v13 or v14, FormEngine enforces it in the
  browser. `Backend/FormEngine/ProgramFinderFieldsTest` pins the compiled
  field (group on `pages`, required, one page) and what a save stores (the
  bare page uid from `pages_3`, the type order, the preselection uid list);
  the plugin test covers the frontend safety net, no form without a target.

## 2. Registration

- [x] 2.1 Register the type with `TcaManipulator::addContentElementPlugin()`
  and `addContentElementPluginFlexForm()`, add `pages`/`recursive` to it, and
  configure the plugin uncached in `ext_localconf.php`.
- [x] 2.2 Add `Configuration/FlexForms/ProgramFinderSettings.xml` and the
  English and German labels (one line per `source`/`target`, two-space
  indentation), with the types under `settings.filter.categoryTypes`; make
  sure `ignoreFlexFormSettingsIfEmpty` covers the finder's field; extend
  `PluginFlexFormTest` for the new data structure on v13 and v14.
  No new `ignoreFlexFormSettingsIfEmpty` entry: Extbase reads it from
  `plugin.tx_academicprograms`, so ACE-736's entry covers the finder, and
  the site-wide fallback test proves it.

## 3. Rendering

- [x] 3.1 Add `ProgramController::finderAction()` and
  `Templates/Program/Finder.html`; run group 1 green on v13, then on v14.
- [x] 3.2 Cover the default types (degree, then topic), a configured order,
  and an empty field with site-wide filter types, which the finder offers
  instead of the default, in the plugin test; show the order and fallback
  assertions fail when the setting is ignored. Shown red on v13: ignoring the
  setting fails the site-wide and the order test, dropping the default fails
  the default test, allowing a disabled preselection and letting the last
  preselection of a type win each fail the preselection test, and removing
  the target page guard fails its test.

## 4. Sets and static registration

- [x] 4.1 Add `Configuration/Sets/ProgramFinder/`, its page TSconfig and its
  TypoScript folder, the static registrations, the aggregate dependency, and
  hide the type in `Configuration/page.tsconfig`.
- [x] 4.2 Extend `SiteSetDeliveryTest`, `InstallationWideRegistrationTest` and
  `StaticRegistrationTest` so the finder is offered with the set and hidden
  without it; show each new assertion fails before 4.1. A finder row in the
  component data providers of `SiteSetDeliveryTest` and
  `StaticRegistrationTest` and in `PluginFlexFormTest`; with the
  configuration of 2.x and 4.1 removed, 8 site set, 5 TCA and 3 field tests
  fail. `InstallationWideRegistrationTest` needs no row: it pins the page type
  and the backend layout, and its glob over the component page TSconfig
  files picks up the finder's file by itself.

## 5. Documentation

- [x] 5.1 Add a finder section to `academic-programs/Documentation/`
  (settings, sets, storage advice, sketch of the output) and list the new set
  in "What the sets contain".
- [x] 5.2 Add `Documentation/Changelog/3.0/Feature-ProgramFinderContentElement.rst`
  and `Documentation/Changelog/3.0/Breaking-ProgramFinderRegisteredUpstream.rst`
  for projects with their own registration, naming what they delete (TCA
  item, plugin configuration, FlexForm, controller code and TSconfig). The
  entry describes what a project registration loaded after this one does -
  it replaces or duplicates the type item, depending on how it was added
  (see design.md).
- [x] 5.3 Update any enumeration of component sets in
  `docs/architecture/typoscript-and-site-sets.md`; verify with
  `lintMarkdown -n`. Also `list-filter-types.md` (a finder section),
  `list-filter-urls.md` (the finder posts into the list's namespace),
  `backend-select-items.md` (23 fields, seven compiling test classes) and the
  architecture index.

## 6. File the issue

- [x] 6.1 After implementation, decide with the maintainer whether ACE-91
  carries the change; otherwise file a new ACE issue in YouTrack and verify the
  key. ACE-91 carries it, as decided in the processing plan; verified (Open,
  "Academic Programs -> Studyfinder"). It asks for the finder and for options
  narrowed without a reload, so this change implements part of it and the
  commit says `Related: ACE-91`; the narrowing is
  `ace-tbd-finder-client-side-narrowing`. No new issue.
- [x] 6.2 Rename the change to `ace-91-program-finder-element`, and its
  references in four other changes.
- [x] 6.3 Commit as `[!!!][FEATURE] ACE-91: Add a program finder element` in
  TYPO3 Core format - breaking, and the subject shortened to 51 characters.

## 7. Review and the existing pull request

- [x] 7.1 A dedicated review (round 1) found no code defect on v13 or v14 and
  sent back: the Breaking entry (`addTcaSelectItem()` duplicates the type
  item, a subclass or XCLASS `finderAction()` overrides this one, a project
  `Program/Finder.html` is picked up, a project `configurePlugin()` drops the
  uncached actions); a target list that lost its sorting on every finder
  submission; an unlinkable target page that gave a form with an empty
  `action`; the tab name (the list's "tab Filter" of ACE-736 was wrong the
  same way); "first preselected" meaning tree order; stale wording in
  `design.md` and `typoscript-and-site-sets.md`. All fixed:
  - `DemandFactory` applies the element's sorting to a demand without one,
    with an `Important` entry and a delta spec for `program-list-sorting`;
    tests for the finder and for a list with a hidden sorting select.
  - `finderAction()` builds the list URI; no form for an empty URI or for
    no offered type; tests for a hidden target page and for no type.
  - New tests: label/id association and button, the uncached registration
    (`AcademicProgramsFinderCachingTest`, a real page cache - a NullBackend
    cannot show it), the compiled preselection field, the site-wide order.
  - Shown red on v14: no sorting fallback fails both sorting tests, no URI
    guard both target tests, no type guard its test, a wrong `for` the label
    test, a cacheable registration the caching test.
- [x] 7.2 An existing pull request, #516 by another author, implemented a
  finder for ACE-91 as `academicprograms_studyfinder` with AJAX reloads. As
  Stefan decided, this change goes into #516 (rebased, squashed, authored by
  Stefan with the original author as `Co-authored-by`). #516's option narrowing
  by server round trip is not taken over: `ace-tbd-finder-client-side-narrowing`
  narrows combined filters in the browser from a program-to-category map. Its
  AJAX re-rendering of the list results is left to a change of its own.

- [x] 7.3 Round 2 was happy after two artifact fixes and six nits, all taken:
  the proposal names `program-list-sorting`, task 5.2 no longer claims the
  registration never duplicates, and `applySortingFromSettings()` ignores a
  sorting setting without a direction instead of raising a warning
  (`DemandFactorySortingTest`, shown red without the guard).
- [x] 7.4 The development seed carries the finder (Stefan): page 253
  `/programs/finder` (German `/studiengaenge/finder`) with content element 64,
  targeting the list page 251, preselecting the Bachelor of Engineering;
  `generateLegacyScenario.php` maps `settings.listPid` into the `/legacy/`
  mirror; both manifests regenerated, both instances rebuilt from nothing and
  both snapshots taken. There is no acceptance (e2e) suite in the repository;
  the seed tests cover the delivery instead.
- [x] 7.5 Verified by hand with Playwright in Chrome and Firefox on both
  instances, in English and German and in the `/` and `/legacy/` trees: the
  finder renders its selects with the preselection, submitting opens the
  filtered list with the selection in its filter, and on v14 the backend form
  shows the fields in the Configuration tab and refuses to save without a
  target page. This found a pre-existing seed defect: the 28 German category
  variants declared no `type`, so they were stored as `default`, and the
  overlay of the category repository gave every German category that type -
  no German program, partner or project list offered a category filter,
  German program pages showed no category facts, and the German finder
  rendered no form. The seed now declares it (Stefan: fixed in this change),
  with a comment and `CategoryVariantTypeTest` against its return. Round 3
  also added the finder to the content types of the seeded editor group and
  moved the uid to the end of the finder's element ids, the form the legacy
  delivery comparison masks, as the other elements have it.

## 8. Definition of done

- [x] 8.1 After `composerUpdate` for TYPO3 v13: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` green (unit 1118, functional 2836, the
  seed manifest regenerated).
- [x] 8.2 After `composerUpdate` for TYPO3 v14: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` green (unit 1113, functional 2852;
  PostgreSQL for `academic-programs`, `typo3-category-types` and
  `packages-dev/dev-site`, 436; the seed manifest regenerated).
- [x] 8.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 8.4 `docs/` and the `Documentation/` changelog entries are part of the
  change; `README.md` and `CONTRIBUTING.md` still only summarize.
- [ ] 8.5 Archive the change as the last commit of the pull request.
