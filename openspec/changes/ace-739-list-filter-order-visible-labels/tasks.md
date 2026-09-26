## 1. Prerequisite

- [x] 1.1 Confirm `ace-738-filter-select-hide-disabled` is merged and verify
  the filter form field accepts `hideDisabledOptions`: merged in #753
  (`648dda3c7`), the argument is registered on `FilterSelectViewHelper`.
- [x] 1.2 Re-check the premises against `main` HEAD (`4541ae3df`): the
  resolver, the program list's filter types and their site setting exist
  since `ace-736-program-list-filter-types`; `academic_partners` and
  `academic_programs` already have settings blocks and site settings; the
  `_LOCAL_LANG` of TypoScript does not reach the filter labels. Recorded in
  `design.md`.

## 2. Tests first

- [x] 2.1 Add unit tests for reading the plugin settings to
  `FilterTypeResolverTest` (missing, TypoScript strings, an integer set in
  PHP, values of the wrong type). They failed with "Call to undefined
  method `resolveFromSettings()`" before the method existed.
- [x] 2.2 Add `CategoryFilterFormAssertionTrait` to `packages-dev/testing-helper`:
  the filters of a form by their field name, split into shown and behind the
  disclosure, their options, and the cell markup for a regression pin.
- [x] 2.3 Add `AcademicPartnersListFilterTest` (list and map),
  `AcademicProjectsListFilterTest` and `AcademicProgramsListFilterTest`: a pin
  of today's filter cells without settings, which passed before and after the
  change; the configured order; a visible count of 1 with the other filters in
  a closed disclosure; the disclosure open for an active filter inside it,
  closed for one outside or a cleared one; no disclosure for a count covering
  every filter; `hideDisabledOptions`; the site settings; the site set
  defaults rendering the pinned markup. Every behaviour test failed on the
  unchanged code (registry order, no disclosure, disabled options kept, site
  settings ignored).
- [x] 2.4 Add a per-type "all" label test to each list test class and to
  `AcademicProgramsFinderTest`, set through `_LOCAL_LANG` of the extension and
  of the plugin. They failed against the unchanged templates with the generic
  label, and on TYPO3 v13 again when a template passed the underscored
  extension name.
- [x] 2.5 Add `AcademicProgramsFinderTest::anOptionNoProgramInStorageCarriesIsLeftOutOnDemand`;
  it failed with the Diploma and the Doctorate still offered.
  Add `theFinderShowsEverySelectWhateverTheVisibleCountOfTheList`; it failed when
  the finder action was made to apply the visible count of the list.
- [x] 2.6 Prove the site set defaults test catches a drifting default: with
  `hideDisabledOptions` declared `true` in the partner set, and with
  `visibleCount` declared `1` in the partner and the project set, it failed.

## 3. Implementation

- [x] 3.1 Add `FilterTypeResolver::resolveFromSettings()` and verify 2.1
  passes.
- [x] 3.2 Add the constants, the setup mapping and the site settings with
  identical defaults: new `Sets/Full/settings.definitions.yaml` in
  `academic_partners` and `academic_projects`, two more entries in the one of
  `academic_programs`; update `SiteSetDeliveryTest` of `academic_programs`,
  which pins the declared defaults.
- [x] 3.3 Inject the resolver into `PartnerController` and `ProjectController`
  through a `final` `inject*()` method, resolve the categories of the list
  event, and switch `ProgramController::listAction()` to the settings; no
  constructor signature changed.
- [x] 3.4 Rewrite the partner and project `DemandCategories` partials in the
  shape of the program one, add the disclosure, the per-type label and
  `hideDisabledOptions` to all three and the label and `hideDisabledOptions`
  to `Finder.html`; add `filter.moreFilters` in English and German.
- [x] 3.5 Pass the extension name in UpperCamelCase in the three partials and
  `Finder.html`, so `_LOCAL_LANG` is read from `plugin.tx_academic<group>` on
  TYPO3 v13 as on v14. Tests 2.3 to 2.5 pass on v13 and v14.

## 4. Documentation

- [x] 4.1 Add `Documentation/Changelog/2.4/Feature-ConfigurableCategoryFilters.rst`
  to `academic_partners`, `academic_projects` and `academic_programs`: the
  change is backported, so the entries go to 2.4 on both branches. Add
  `Important-FilterLabelOverridesUseThePluginPath.rst` next to each for the
  `_LOCAL_LANG` path that changes on v12 and v13, and say in the 3.0 entry of
  the program filter types that the site setting exists since 2.4.
- [x] 4.2 Document the settings in each extension's configuration chapter, and
  extend `docs/architecture/list-filter-types.md` with the settings namespace,
  the disclosure, the "all" label and how v13 and v14 read `_LOCAL_LANG`; add
  the trait to `docs/testing/testing-helper.md` and the counts that name it.
- [x] 4.3 Verify the label overrides and the settings in the rendered frontend
  of both development instances, site set tree and `/legacy/` tree, English
  and German, list and finder.

## 5. The issue

- [x] 5.1 File ACE-739 (Story, `[3.x][2.x]`, Version 2.4.0, subtask of ACE-10,
  related to ACE-571, ACE-511 and two project issues) and rename the change to
  `ace-739-list-filter-order-visible-labels`, references included.

## 6. Definition of done

- [x] 6.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v13, all green.
- [x] 6.2 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v14, all green.
- [x] 6.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 6.4 Commit as `[FEATURE] ACE-739: …` in TYPO3 Core format, and archive
  the change as the last commit of the pull request. In the archive commit,
  extend the Purpose of
  `openspec/specs/academic-programs/program-list-filter-types/spec.md` by
  hand: a delta cannot change it.
