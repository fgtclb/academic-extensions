## 1. Descendant lookup in category_types

- [x] 1.1 Add a functional `CategoryRepository` test with a fixture tree
  (parent, child, grandchild, hidden child, deleted child, a translated child,
  a child of a type outside the group, and two categories that reference each
  other): the descendants of the parent are the visible children and the
  grandchild of the group, ordered by `uid`, and the loop terminates. Run it
  before the method exists and record the failure.
- [x] 1.2 Implement `findDescendantUids()` following the database query rules
  of `AGENTS.md` (quoted integer list, `ORDER BY uid`, one query builder per
  statement) and verify 1.1 passes on SQLite and PostgreSQL.
- [x] 1.3 Add functional cases for `findAllApplicableWithSubcategories()` on
  the same fixture tree: a carried child enables its parent and grandparent,
  a carried category behind a hidden one or one of a foreign type enables
  nothing above it, a loop ends, and `findAllApplicable()` stays unchanged.
  Record the failure before the method exists, then implement it.

## 2. Program list option

- [x] 2.1 Add `settings.filter.includeSubcategories` to
  `ProgramListSettings.xml` with its English and German labels, and map it
  onto `ProgramDemand` in `DemandFactory`; cover the mapping in the
  `DemandFactory` test and show it fails without the mapping.
- [x] 2.2 Add functional repository cases, in
  `ProgramRepositorySubcategoriesTest` next to `ProgramRepositoryTest`. A
  program carrying only the child is found by the parent with the flag on and
  not with it off, a grandchild matches, a sibling does not, and two
  selections still both have to match. Record that the flag-on cases fail
  while the repository ignores the flag.
- [x] 2.3 Widen each selection with `logicalOr()` in `findByDemand()` and
  verify 2.2 passes.
- [x] 2.4 Add plugin cases, in `AcademicProgramsSubcategoryFilterTest` next to
  `AcademicProgramsPluginTest`, for a preselected parent in
  `settings.categories` and for a submitted parent filter, asserting the
  listed programs and that the parent option renders selected, and show that
  they fail with 2.3 reverted.
- [x] 2.5 Use `findAllApplicableWithSubcategories()` in the list action when
  the option is on, and add plugin cases: the parent is a selectable option
  when only its child is carried, a disabled one with the option off, and kept
  with `hideDisabledOptions`. Show they fail with the old lookup.
- [x] 2.6 Add `settings.filter.includeSubcategories` to
  `ProgramFinderSettings.xml`, use the same lookup in the finder action when
  it is on, and add finder cases for the option on and off. Show they fail
  without the change.

## 3. Documentation

- [x] 3.1 Document the option in `academic-programs/Documentation/` for the
  list and the finder, with the "assign only the specific category"
  recommendation and the advice to switch it on in both.
- [x] 3.2 Add `Documentation/Changelog/3.0/Feature-ListFilterIncludesSubcategories.rst`
  in `academic-programs`, and document the descendant lookup and the
  applicable lookup with subcategories in the `typo3-category-types`
  `Documentation/Developers/` section. The repository is not on the extension
  points page, so the two methods are not public API and `category_types` gets
  no changelog entry: an installation notices nothing there.
- [x] 3.3 Describe the subtree matching in `docs/` and link it from the section
  `Index.md`.

## 4. File the issue

- [x] 4.1 File the ACE issue in YouTrack, relating it to ACE-620: ACE-765.
- [x] 4.2 Rename the change to `ace-765-filter-match-subcategories`.
- [x] 4.3 Commit as `[FEATURE] ACE-765: Filter programs by subcategories` in
  TYPO3 Core format. The planned subject, "Match subcategories in program
  filter", is 56 characters with the prefix, over the limit of 52.

## 5. Definition of done

- [x] 5.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v13, and `functional` with `-d postgres`; record the
  results.
- [x] 5.2 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v14, and `functional` with `-d postgres`; record the
  results.
- [x] 5.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 5.4 `docs/`, the `Documentation/` of both extensions and the changelog
  of `academic-programs` updated in the same change. `README.md` and
  `CONTRIBUTING.md` only link.
- [ ] 5.5 Archive the change as the last commit of the pull request.
