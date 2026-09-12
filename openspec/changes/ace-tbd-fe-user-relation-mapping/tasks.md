## 1. Prerequisite

- [ ] 1.1 Verify `ace-tbd-settings-driven-fe-user-mapping` is merged.
- [ ] 1.2 Check whether `ace-tbd-fe-user-sync-data-events` is merged. The
  employee-type recipe of 4.3 needs its mapped-profile event; if it is not
  merged, 4.3 moves to that change.

## 2. Tests first

- [ ] 2.1 Add functional tests on a fixture mapping `company` to the
  organisational unit with creation on page 7: the unit is created on page 7
  and assigned, and a second run reuses it without a duplicate. Show they
  fail before the change (no relation is written).
- [ ] 2.2 Add tests for two matching function types (the lower uid wins,
  asserted), creation disabled (no record, empty relation), an emptied
  source (relation cleared), an unmapped relation (editor value kept) and an
  editor-assigned employee type that a synchronisation keeps.
- [ ] 2.3 Add a unit test that the normaliser rejects `create: true` without
  a storage page and a `matchBy` outside the listed fields.

## 3. Implementation

- [ ] 3.1 Extend the settings sub-graph and the normaliser. Verify 2.3.
- [ ] 3.2 Add the stateless relation resolver and use it from the mapper.
  Verify 2.1 and 2.2 on v13 and v14.

## 4. Documentation

- [ ] 4.1 Document the relation entries in the configuration chapter and add
  `academic-persons/Documentation/Changelog/3.0/Feature-FrontendUserRelationMapping.rst`.
  Verify the rendering.
- [ ] 4.2 Extend `docs/architecture/frontend-user-contact-import.md` with the
  lookup and creation rules. Verify `lintMarkdown -n`.
- [ ] 4.3 Document the employee-type recipe next to the relation entries: a
  stateless listener of `AfterProfileMappedFromFrontendUserEvent`, registered
  with TYPO3's `#[AsEventListener]`, reads the source value from the
  frontend-user data, resolves the category with its own ordered query (for
  example restricted to one category type when `category_types` is
  installed) and sets it on the profile's contracts. State that the
  synchronisation itself never sets the employee type. Verify the rendering.

## 5. File the issue

- [ ] 5.1 After implementation, file the ACE issue in YouTrack (related to
  ACE-278), rename the change to `ace-<NNN>-<slug>`, and commit in TYPO3 Core
  format as `[FEATURE] ACE-<NNN>: <subject>`.

## 6. Definition of done

- [ ] 6.1 `lintPhp`, `cgl -n`, `phpstan`, `unit` and `functional` green for
  TYPO3 v13 and v14, each after its own `composerUpdate`.
- [ ] 6.2 `functional` also on PostgreSQL, MariaDB and MySQL for the
  resolver tests. They write and depend on the ordering.
- [ ] 6.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 6.4 `docs/` and the extension's `Documentation/` changelog updated in
  the same change.
- [ ] 6.5 Archive the change as the last commit of the pull request.
