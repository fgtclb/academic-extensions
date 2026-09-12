## 1. Registry order

- [ ] 1.1 Add unit tests to `CategoryTypeRegistryTest`: three types of one
  group with priorities `0`, `10` and `5` come back as `10`, `5`, `0` from
  `getCategoryTypesByGroup()`, `getCategoryTypeIdentifierByGroup()`,
  `getGroupedCategoryTypes()` and `getCategoryTypes()`; two types with equal
  priority keep their attachment order; a negative priority sorts last. Run
  them against the unchanged registry and record that the order assertions
  fail.
- [ ] 1.2 Sort each group by priority, highest first and stable, in
  `attach()`, and rebuild the flat list in group order; verify the new tests
  and the unchanged `attachedTypesAreReturnedInAttachmentOrder` pass.

## 2. Override and consumers

- [ ] 2.1 Add a unit fixture package that overrides a type of `base_types`
  with `useExisting: true` and `priority: 100`, and a loader test asserting
  that the type moves to the front and keeps its title and icon, uncached and
  from the cache; show it fails without 1.2.
- [ ] 2.2 Add a functional test in `academic_programs`: a fixture extension
  raises the priority of `location` above `degree`, and the program list
  plugin renders the location select before the degree select and the details
  plugin lists the location before the degree; show it fails without 1.2.
- [ ] 2.3 Verify that the `sys_category` `type` select items follow the order
  with a functional TCA assertion, and show it fails without 1.2.
- [ ] 2.4 If `ace-tbd-program-facts-field-list` has landed, extend its facts
  order test (its task 2.7) to the priority order: the fixture raises
  `location` above `degree`, the program page and the details element with
  an empty facts setting show the location before the degree; show it fails
  without 1.2. If it has not landed, that change writes the test against
  this order itself.

## 3. Documentation

- [ ] 3.1 Rewrite the `priority` paragraph of the `CategoryTypes.yaml` page
  that `ace-tbd-category-types-yaml-docs` added, which states "no effect",
  into the ordering rule (higher first, stable for equal values), with a
  `useExisting` override example.
- [ ] 3.2 Add `Documentation/Changelog/2.4/Feature-CategoryTypesAreOrderedByPriority.rst`
  and an `Important-` entry naming the reorder for packages that already set
  `priority`, in `typo3-category-types`, in the 2.4 folder because the
  feature ships with 2.4; verify both are listed by the changelog index.
  Before the `Important-` wording is final, read the complete
  `CategoryTypes.yaml` of the analysed project that redeclares eight program
  types and confirm it sets no `priority`.
- [ ] 3.3 Add the ordering rule to the category types section of `docs/` and
  link it from the section `Index.md`.

## 4. File the issue

- [ ] 4.1 After implementation, file the ACE issue in YouTrack, rename the
  change to `ace-<NNN>-category-type-priority-order` and commit as
  `[FEATURE] ACE-<NNN>: Order category types by priority` in TYPO3 Core
  format.

## 5. Backport

- [ ] 5.1 Backport: separate change on branch `2` after a backport analysis
  (`docs/workflow/backporting.md`), starting with a file-level diff of the
  registry and the loader between `main` and `2`; the analysis states the
  loader is identical and the registry differs by one line, which that diff
  confirms or corrects. The projects that need the order run 2.x.
- [ ] 5.2 The branch `2` change carries the same two changelog files in
  `typo3-category-types/Documentation/Changelog/2.4/`, with identical file
  names and content, so both branches document the 2.4 feature in one
  place.

## 6. Definition of done

- [ ] 6.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v13; record the results.
- [ ] 6.2 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v14; record the results.
- [ ] 6.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 6.4 `docs/` and the `typo3-category-types` `Documentation/Changelog/2.4/`
  entries updated in the same change; `README.md` and `CONTRIBUTING.md` only
  link.
- [ ] 6.5 Archive the change as the last commit of the pull request.
