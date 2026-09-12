## 1. Prerequisite

- [ ] 1.1 Confirm `ace-tbd-list-filter-get-urls` is merged; the pagination
  links build on its GET shape.

## 2. Configuration

- [ ] 2.1 Split the FlexForm: `MapSettings.xml` with today's fields for the
  map, a "Pagination" sheet with `settings.paginationEnabled` and
  `settings.pagination.resultsPerPage` in `ListSettings.xml`; add a
  functional TCA test that the list exposes the pagination fields, the map
  does not, and both expose the same filter fields. Show it fails while both
  elements still point at one file.
- [ ] 2.2 TypoScript `settings.pagination.numberOfLinks` fed by the site
  setting `plugin.tx_academicpartners.pagination.numberOfLinks`; English and
  German labels.

## 3. Pagination in the list

- [ ] 3.1 `currentPage` on `PartnerDemand`, read by `DemandFactory`; unit or
  functional factory test for a missing, a valid and a negative value, shown
  to fail before the property exists.
- [ ] 3.2 Paginator and pagination in `listAction()`, `Partner/Pagination.html`
  rendered by `ItemList.html` when a paginator with more than one page
  exists. Functional rendering test: five partners, two per page, page two
  shows exactly partners three and four and a link to page three that keeps
  an active filter. Show it fails on the unchanged controller (all five
  render).
- [ ] 3.3 Regression: pagination off renders all five partners and no
  navigation; the map renders every drawable partner with pagination
  enabled on the list. Show the map test fails if the map action paginates.
- [ ] 3.4 Both pagination classes: one test with `numbered_pagination`
  loaded, one without; show the second fails when `NumberedPagination` is
  used unconditionally.

## 4. Documentation

- [ ] 4.1 `docs/architecture/`: the list demand URL section names
  `currentPage`; linked from `docs/architecture/Index.md`.
- [ ] 4.2 `academic-partners` `Documentation/Configuration/` for the three
  settings and
  `Documentation/Changelog/3.0/Feature-PartnerListPagination.rst`, including
  the note that a controller subclass paginating on its own must be removed.

## 5. File the issue

- [ ] 5.1 After implementation, file the ACE issue in YouTrack, rename the
  change to `ace-<NNN>-partner-list-pagination`, and commit as
  `[FEATURE] ACE-<NNN>: Paginate the partner list` in TYPO3 Core format.

## 6. Definition of done

- [ ] 6.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v13; record the results.
- [ ] 6.2 `composerUpdate`, then the same five suites for TYPO3 v14; record
  the results.
- [ ] 6.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 6.4 `docs/` and the `academic-partners` `Documentation/` changelog
  updated; `README.md` and `CONTRIBUTING.md` still only link.
- [ ] 6.5 Archive the change as the last commit of the pull request.
