## 1. Items provider in category_types

- [ ] 1.1 Add a unit test for the provider: group `programs` of a fixture
  registry yields one item per type with title, identifier and icon in the
  registry order; an empty and an unknown group yield no items and no
  exception. Run it before the class exists and record the failure.
- [ ] 1.2 Implement the `final readonly` provider with constructor injection
  and `#[Autoconfigure(public: true)]`; verify 1.1 passes and that
  `GeneralUtility::makeInstance()` returns it from the container in a
  functional test.

## 2. Program list field

- [ ] 2.1 Add `settings.filter.categoryTypes` to `ProgramListSettings.xml`
  with its English and German labels, and list it in
  `plugin.tx_academicprograms.ignoreFlexFormSettingsIfEmpty`; verify with a
  functional FormEngine data test that the field of a list plugin offers the
  programs types and not a type removed by a fixture package.
- [ ] 2.2 Add `AcademicProgramsPluginTest` cases: the field set to
  `location,degree` renders exactly those two selects in that order, also
  when the site-wide setting names `degree`; an empty field with the
  site-wide setting `degree` renders only the degree select; an empty field
  without a site-wide value renders the same selects as before; a chosen
  type without categories and an unknown identifier render nothing; a
  submitted `location` filter still applies when only `degree` is offered.
  Record that the ordering, subset and fallback cases fail before 2.3.
- [ ] 2.3 Resolve the effective value in `listAction()` through the
  `FilterTypeResolver` of `ace-tbd-list-filter-order-visible-labels` (added
  in that shape if this change is applied first) and loop the result in
  `DemandCategories.html`; verify 2.2 passes.
- [ ] 2.4 Remove the `ignoreFlexFormSettingsIfEmpty` entry, watch the
  fallback case of 2.2 go red, restore it.

## 3. Documentation

- [ ] 3.1 Document the field as the per-element override of the site-wide
  filter type setting in `academic-programs/Documentation/`, and the
  provider with a TCA and a FlexForm example in
  `typo3-category-types/Documentation/Developers/`.
- [ ] 3.2 Add `Documentation/Changelog/3.0/Feature-ListFilterTypesAreSelectable.rst`
  in `academic-programs` and
  `Documentation/Changelog/3.0/Feature-CategoryTypeItemsProvider.rst` in
  `typo3-category-types`; verify both render in the changelog index.
- [ ] 3.3 Mention the provider in the category types section of `docs/` and
  link it from the section `Index.md`.

## 4. File the issue

- [ ] 4.1 After implementation, file the ACE issue in YouTrack, rename the
  change to `ace-<NNN>-program-list-filter-types` and commit as
  `[FEATURE] ACE-<NNN>: Choose program list filter types` in TYPO3 Core
  format.

## 5. Definition of done

- [ ] 5.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v13; record the results.
- [ ] 5.2 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v14; record the results.
- [ ] 5.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 5.4 `docs/` and the `Documentation/` changelog of both extensions
  updated in the same change; `README.md` and `CONTRIBUTING.md` only link.
- [ ] 5.5 Archive the change as the last commit of the pull request.
