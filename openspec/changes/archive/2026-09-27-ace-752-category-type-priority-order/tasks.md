## 1. Registry order

- [x] 1.1 Add unit tests to `CategoryTypeRegistryTest`: three types of one
  group with priorities `0`, `10` and `5` come back as `10`, `5`, `0` from
  `getCategoryTypesByGroup()`, `getCategoryTypeIdentifierByGroup()`,
  `getGroupedCategoryTypes()` and `getCategoryTypes()`; two types with equal
  priority keep their attachment order; a negative priority sorts last. Run
  them against the unchanged registry and record that the order assertions
  fail.
- [x] 1.2 Sort each group by priority, highest first and stable, in
  `attach()`, and rebuild the flat list in group order; verify the new tests
  and the unchanged `attachedTypesAreReturnedInAttachmentOrder` pass. Also
  `typeAttachedLaterIsSortedIntoItsGroup` and
  `flatListFollowsTheOrderOfTheGroups`, whose fixture tells a rebuild group by
  group from both the old attachment order and a sort across groups. The loop
  runs in `try`/`finally`, so a rejected duplicate leaves the flat list and
  the groups in agreement (`typesBeforeARejectedDuplicateStayAttached`, red
  without the `finally`).

## 2. Override and consumers

- [x] 2.1 Add a unit fixture package that overrides a type of `base_types`
  with `useExisting: true` and `priority: 100`, and a loader test asserting
  that the type moves to the front and keeps its title and icon, uncached and
  from the cache; show it fails without 1.2. Done with the fixture
  `priority_override` (`degree` at `100` ahead of `research_field` at `10`):
  `priorityOfAnOverrideMovesTheTypeToTheFront` through `load()`, and
  `typesRestoredFromTheCacheAreOrderedByPriority` with a cache entry in
  declaration order. Both red against the unchanged registry.
- [x] 2.2 Add a functional test in `academic_programs`: a fixture extension
  raises the priority of `location` above `degree`, and the program list
  plugin renders the location select before the degree select and the details
  plugin lists the location before the degree; show it fails without 1.2.
  `CategoryTypes/CategoryTypePriorityTest` with the fixture extension
  `test_programs_category_type_priority` (`location` at `10`). It also covers
  the page module summary, which the spec requires and no task named. All five
  tests red against the unchanged registry.
- [x] 2.3 Verify that the `sys_category` `type` select items follow the order
  with a functional TCA assertion, and show it fails without 1.2.
  `typeSelectOffersTheLocationFirst` in the same class.
- [x] 2.4 `ace-733-program-facts-field-list` has landed: extend its facts
  order test (its task 2.7,
  `academic-programs/Tests/Functional/Facts/ProgramFactsTest.php`, today
  pinning the registry order degree - standard period - location) to the
  priority order: the fixture raises `location` above `degree`, the program
  page and the details element with an empty facts setting show the location
  before the degree; show it fails without 1.2. If it has not landed, that
  change writes the test against this order itself. It has landed. A fixture
  extension is loaded per test class, so the priority cases live in
  `CategoryTypePriorityTest` (`programPageShowsTheLocationFirst`,
  `detailsElementShowsTheLocationFirst`) rather than in `ProgramFactsTest`,
  whose docblock now points there.

## 3. Documentation

- [x] 3.1 Rewrite the `priority` paragraph of
  `typo3-category-types/Documentation/Developers/CategoryTypes/Index.rst`,
  which states that nothing sorts by it (written by ACE-664), and the
  statements on the same page that the types keep their declaration order,
  that a changed type keeps its position and that `getCategoryTypes()` returns
  declaration order, plus the registration order statement of
  `Documentation/Developers/TCA/Index.rst`; rewrite them into the ordering
  rule (higher first, stable for equal values), with a `useExisting` override
  example. If the sorting happens in `CategoryTypeLoader::loadUncached()`,
  revisit the key order assertions of its unit tests as well. The sorting is
  in the registry, so the loader assertions stand. The page gains the section
  "The order of the types"; the page module summary page, the
  `filter.categoryTypes` rows of the partner and project configuration and the
  programs filter paragraph said "in the order the types are registered in"
  and now name the type order.
- [x] 3.2 Add
  `Documentation/Changelog/2.4/Feature-CategoryTypesAreOrderedByPriority.rst`
  and an `Important-` entry naming the reorder for packages that already set
  `priority`, in `typo3-category-types`, in the 2.4 folder because the feature
  ships with 2.4; verify both are listed by the changelog index. Before the
  `Important-` wording is final, read the complete `CategoryTypes.yaml` of the
  analysed project that redeclares eight program types and confirm it sets no
  `priority`. Read on 2026-09-27: it sets none, and neither does any other
  project `CategoryTypes.yaml` checked out locally.
- [x] 3.3 Add the ordering rule to the category types section of `docs/` and
  link it from the section `Index.md`. `docs/` had no category types section,
  so this is the new page `docs/architecture/category-type-order.md`, with a
  row and a line in the short version of the architecture index. The fixture
  extension is listed in `docs/testing/fixture-extensions.md`.

## 4. File the issue

- [x] 4.1 After implementation, file the ACE issue in YouTrack, rename the
  change to `ace-<NNN>-category-type-priority-order` and commit as `[FEATURE]
  ACE-<NNN>: Order category types by priority` in TYPO3 Core format. ACE-752,
  a Story with version 2.4.0 because of the backport, subtask of ACE-42,
  relates to two project issues, and depends on ACE-751.

## 5. Backport

- [ ] 5.1 Carried by the separate change on branch `2`: a backport analysis
  (`docs/workflow/backporting.md`), starting with a file-level diff of the
  registry and the loader between `main` and `2`; the analysis states the
  loader is identical and the registry differs by one line, which that diff
  confirms or corrects. The projects that need the order run 2.x.
- [ ] 5.2 Carried by the separate change on branch `2`, which carries the same
  two changelog files in `typo3-category-types/Documentation/Changelog/2.4/`,
  with identical file names and content, so both branches document the 2.4
  feature in one place.

## 6. Definition of done

- [x] 6.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v13; record the results. All green: 1231 unit tests,
  3288 functional tests listed and run.
- [x] 6.2 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v14; record the results. All green: 1226 unit tests,
  3304 functional tests listed and run.
- [x] 6.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 6.4 `docs/` and the `typo3-category-types`
  `Documentation/Changelog/2.4/` entries updated in the same change;
  `README.md` and `CONTRIBUTING.md` only link.
- [x] 6.5 Archive the change as the last commit of the pull request.
