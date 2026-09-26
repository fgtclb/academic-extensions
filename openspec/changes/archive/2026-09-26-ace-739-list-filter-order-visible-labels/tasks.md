## 1. Analysis

- [x] 1.1 Diff every file of the `main` change against this branch, recorded in
  the backport analysis: resolver absent, controllers without events, program
  partial old, no settings blocks, five traits.

## 2. Tests first

- [x] 2.1 Bring the resolver unit tests and the three list test classes with
  the testing helper trait; the active filter tests post instead of following
  a redirect, the site set tests carry `not-core-12`. The resolver tests
  failed with "Class not found" before `FilterTypeResolver` existed here; the
  behaviour tests of order, visible count, disclosure and hidden options are
  those shown to fail on `main` against the unchanged partials, which this
  branch shares for partners and projects. The program partial differs here
  (the old loop): against it, 14 of the 18 program tests failed on v13 - all
  but the two markup pins, the covering count and the fallback, which describe
  unchanged output.
- [x] 2.2 Verify the markup pins against the unchanged partials of this
  branch on v13: they passed.
- [x] 2.3 Verify the label tests fail on v12 when a partial passes the
  underscored extension name: they did.
- [x] 2.4 Port the two program list tests of `main`'s ACE-736 test class this
  branch lacks: the fallback without `filterTypes` (it failed with the
  partial's `f:else` branch removed) and a submitted filter of a type the form
  does not offer.

## 3. Implementation

- [x] 3.1 Add `FilterTypeResolver`, `FilterTypes` (PHP 8.1 form) and the
  settings reader to `category_types`.
- [x] 3.2 Inject the resolver into the three list controllers by a `final`
  `inject*()` method and assign `filterTypes`.
- [x] 3.3 Replace the three `DemandCategories` partials by those of `main`,
  add the constants, the setup mapping, the site settings and the
  `filter.moreFilters` labels (en, de).

## 4. Documentation

- [x] 4.1 Add the `Feature-` and `Important-` entries to `Changelog/2.4/` of
  the three extensions, identical to `main`.
- [x] 4.2 Add the filter section to the three configuration chapters, with
  the note that site settings need TYPO3 v13.
- [x] 4.3 `docs/`: a new page `docs/architecture/list-filter-types.md`, linked
  from the section `Index.md`, reduced to this branch; the second legitimate
  case of method injection in `docs/architecture/class-design.md`; the trait in
  `docs/testing/testing-helper.md`, the counts in `AGENTS.md`,
  `docs/development/monorepo-layout.md` and `docs/workflow/backporting.md`.

## 5. Definition of done

- [x] 5.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v12 on PHP 8.1, all green.
- [x] 5.2 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v13, all green.
- [x] 5.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 5.4 Commit message in TYPO3 Core format with `Resolves: ACE-739`, no
  attribution.
- [x] 5.5 Archive the change as the last commit of the pull request.
