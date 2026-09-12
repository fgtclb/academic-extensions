## 1. Descendant lookup in category_types

- [ ] 1.1 Add a functional `CategoryRepository` test with a fixture tree
  (parent, child, grandchild, hidden child, deleted child, a translated child,
  a child of a type outside the group, and two categories that reference each
  other): the descendants of the parent are the visible children and the
  grandchild of the group, ordered by `uid`, and the loop terminates. Run it
  before the method exists and record the failure.
- [ ] 1.2 Implement `findDescendantUids()` following the database query rules
  of `AGENTS.md` (quoted integer list, `ORDER BY uid`, one query builder per
  statement) and verify 1.1 passes on SQLite and PostgreSQL.

## 2. Program list option

- [ ] 2.1 Add `settings.filter.includeSubcategories` to
  `ProgramListSettings.xml` with its English and German labels, and map it
  onto `ProgramDemand` in `DemandFactory`; cover the mapping in the
  `DemandFactory` test and show it fails without the mapping.
- [ ] 2.2 Add functional `ProgramRepositoryTest` cases: a program carrying only
  the child is found by the parent with the flag on and not with it off; a
  grandchild matches; a sibling does not; two selections still both have to
  match. Record that the flag-on cases fail while the repository ignores the
  flag.
- [ ] 2.3 Widen each selection with `logicalOr()` in `findByDemand()` and
  verify 2.2 passes.
- [ ] 2.4 Add `AcademicProgramsPluginTest` cases for a preselected parent in
  `settings.categories` and for a submitted parent filter, asserting the
  listed programs and that the parent option renders selected; show they fail
  with 2.3 reverted.

## 3. Documentation

- [ ] 3.1 Document the option in `academic-programs/Documentation/`, with the
  "assign only the specific category" recommendation.
- [ ] 3.2 Add `Documentation/Changelog/3.0/Feature-ListFilterIncludesSubcategories.rst`
  in `academic-programs`, and document the descendant lookup in the
  `typo3-category-types` `Documentation/Developers/` section.
- [ ] 3.3 Describe the subtree matching in `docs/` and link it from the section
  `Index.md`.

## 4. File the issue

- [ ] 4.1 After implementation, file the ACE issue in YouTrack (relating it to
  ACE-620), rename the change to `ace-<NNN>-filter-match-subcategories` and
  commit as `[FEATURE] ACE-<NNN>: Match subcategories in program filter` in
  TYPO3 Core format.

## 5. Definition of done

- [ ] 5.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v13, and `functional` with `-d postgres`; record the
  results.
- [ ] 5.2 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v14, and `functional` with `-d postgres`; record the
  results.
- [ ] 5.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 5.4 `docs/` and the `Documentation/` changelog of both extensions
  updated in the same change; `README.md` and `CONTRIBUTING.md` only link.
- [ ] 5.5 Archive the change as the last commit of the pull request.
