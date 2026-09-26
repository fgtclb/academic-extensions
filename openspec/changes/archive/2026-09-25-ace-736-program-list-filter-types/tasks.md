## 1. Items provider in category_types

- [x] 1.1 Add a unit test for the provider: group `programs` of a fixture
  registry yields one item per type with title, identifier and icon in the
  registry order; an empty and an unknown group yield no items and no
  exception. Run it before the class exists and record the failure.
- [x] 1.2 Implement the `final readonly` provider with constructor injection
  and `#[Autoconfigure(public: true)]`; verify 1.1 passes and that
  `GeneralUtility::makeInstance()` returns it from the container in a
  functional test. (In `Backend\FormEngine`, see design.md; without the
  attribute both functional tests fail with an `ArgumentCountError`.)

## 2. Program list field

- [x] 2.1 Add `settings.filter.categoryTypes` to `ProgramListSettings.xml`
  with its English and German labels, and list it in
  `plugin.tx_academicprograms.ignoreFlexFormSettingsIfEmpty`; verify with a
  functional FormEngine data test that the field of a list plugin offers the
  programs types and not a type removed by a fixture package.
- [x] 2.2 Add `AcademicProgramsPluginTest` cases: the field set to
  `location,degree` renders exactly those two selects in that order, also
  when the site-wide setting names `degree`; an empty field with the
  site-wide setting `degree` renders only the degree select; an empty field
  without a site-wide value renders the same selects as before; a chosen
  type without categories and an unknown identifier render nothing; a
  submitted `location` filter still applies when only `degree` is offered.
  Record that the ordering, subset and fallback cases fail before 2.3.
  (A dedicated class `AcademicProgramsListFilterTypesTest`, plus a site set
  case and a type whose categories are on no listed program. Against `main`
  16 of the 18 new functional tests fail; the two that pass are the
  regression pins for an element without the field.)
- [x] 2.3 Resolve the effective value in `listAction()` through the
  `FilterTypeResolver` of `ace-739-list-filter-order-visible-labels` (added
  in that shape if this change is applied first) and loop the result in
  `DemandCategories.html`; verify 2.2 passes.
- [x] 2.4 Remove the `ignoreFlexFormSettingsIfEmpty` entry, watch the
  fallback case of 2.2 go red, restore it. (Red: the "saved with the field
  empty" case of the site-wide fallback.)
- [x] 2.5 Add the site-wide setting of the program list, which the listings
  change is not there yet to provide: the constant
  `plugin.tx_academicprograms.filter.categoryTypes`, its mapping in
  `setup.typoscript` and its site setting declaration with the same
  default; extend `SiteSetDeliveryTest`.
- [x] 2.6 Fall back to today's loop in `DemandCategories.html` when
  `filterTypes` does not reach it (a controller subclass, a template passing
  its own arguments), with a test through a partial override that passes
  explicit arguments; shown red without the fallback. Cover the order a
  DataHandler save stores.

## 3. Documentation

- [x] 3.1 Document the field as the per-element override of the site-wide
  filter type setting in `academic-programs/Documentation/`, and the
  provider with a TCA and a FlexForm example in
  `typo3-category-types/Documentation/Developers/`.
- [x] 3.2 Add `Documentation/Changelog/3.0/Feature-ListFilterTypesAreSelectable.rst`
  in `academic-programs` and
  `Documentation/Changelog/3.0/Feature-CategoryTypeItemsProvider.rst` in
  `typo3-category-types`; verify both render in the changelog index.
- [x] 3.3 Mention the provider in the category types section of `docs/` and
  link it from the section `Index.md`. (`docs/` has no category types
  section: a new page `docs/architecture/list-filter-types.md`, and the
  provider added to `docs/architecture/backend-select-items.md`.)

## 4. File the issue

- [x] 4.1 After implementation, file the ACE issue in YouTrack, rename the
  change to `ace-<NNN>-program-list-filter-types` and commit as
  `[FEATURE] ACE-<NNN>: Choose program list filter types` in TYPO3 Core
  format.

## 5. Definition of done

- [x] 5.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v13; record the results. (v13.4.35: unit 1117,
  functional 2799 in 16 chunks after the review, all green.)
- [x] 5.2 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v14; record the results. (v14.3.7: unit 1112,
  functional 2815 in 16 chunks after the review, all green; PostgreSQL for both touched
  extensions, 390 tests, green.)
- [x] 5.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 5.4 `docs/` and the `Documentation/` changelog of both extensions
  updated in the same change; `README.md` and `CONTRIBUTING.md` only link.
- [x] 5.5 Archive the change as the last commit of the pull request.
