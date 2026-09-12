## 1. Prerequisite

- [ ] 1.1 Confirm `ace-tbd-filter-select-hide-disabled` is merged or stacked
  below this change, and verify the filter form field accepts
  `hideDisabledOptions`.

## 2. Tests first

- [ ] 2.1 Add a unit test for the resolver: empty list gives all types with
  categories in registry order; `sdg,region` gives that order; unknown and
  empty types are dropped; a visible count splits into `visible` and `more`.
  Record that it fails before the class exists.
- [ ] 2.2 Add a regression test per list plugin (`AcademicPartnersPluginTest`,
  `AcademicProjectsProjectListPluginTest`, `AcademicProgramsPluginTest`) that
  captures today's filter markup without settings, and verify it passes
  before and after the change.
- [ ] 2.3 Add a test per list plugin with `settings.filter.categoryTypes` in
  reverse registry order and `visibleCount = 1`: assert the order, the second
  filter inside `<details>`, and a per-type "all" label defined through
  `_LOCAL_LANG`. Record that it fails on the unchanged code (registry order,
  no disclosure, generic label).
- [ ] 2.4 Add a test with an active value in a filter behind the disclosure
  and assert `<details open>`; add a test with
  `settings.filter.hideDisabledOptions = 1` asserting an option without
  results is absent.
- [ ] 2.5 Cover the partner map action with the order test as well.

## 3. Implementation

- [ ] 3.1 Add `FilterTypeResolver` and `FilterTypes` to `category_types` and
  verify test 2.1 passes.
- [ ] 3.2 Add the constants, the setup mapping and the
  `settings.definitions.yaml` with identical defaults to each extension, and
  verify with the site set delivery tests that both mechanisms deliver the
  same values.
- [ ] 3.3 Inject the resolver into the partner, project and program
  controllers through an `inject*()` method and assign `filterTypes`; verify
  no constructor signature changed.
- [ ] 3.4 Rewrite the three `DemandCategories` partials and add the
  `filter.moreFilters` labels in English and German; verify tests 2.2 to
  2.5 pass on v13 and v14.

## 4. Documentation

- [ ] 4.1 Add `Documentation/Changelog/3.0/Feature-ConfigurableCategoryFilters.rst`
  to `academic_partners`, `academic_projects` and `academic_programs`,
  documenting the three settings and the per-type label key; verify they
  render.
- [ ] 4.2 Document the settings in each extension's configuration chapter,
  and add the `settings.filter` namespace and the resolver decision to
  `docs/`, linked from the section `Index.md`.

## 5. File the issue

- [ ] 5.1 After implementation, file the ACE issue in YouTrack, rename the
  change to `ace-<NNN>-list-filter-order-visible-labels`, and commit in
  TYPO3 Core format, e.g. `[FEATURE] ACE-<NNN>: Configure list category
  filters`.

## 6. Definition of done

- [ ] 6.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v13, all green.
- [ ] 6.2 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v14, all green.
- [ ] 6.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 6.4 `docs/` and the three extensions' `Documentation/` changelogs updated
  in the same change.
- [ ] 6.5 Archive the change as the last commit of the pull request.
