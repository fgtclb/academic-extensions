## 1. Backport analysis

- [x] 1.1 Diff every file of the `main` change against this branch. The loader
  is identical, the registry differs by four docblock lines (no `@api`, no
  `inlineIcon` in the `toArray()` shape), and `CategoryType` has no
  `inlineIcon`. There is no filter types items provider and no program facts
  builder on `2`; the program page and the details plugin render
  `Program/Categories`.

## 2. Registry and tests

- [x] 2.1 Port the registry change unchanged: every group sorted by priority
  with `uasort()`, highest first, and the flat list rebuilt group by group in
  a `finally` block.
- [x] 2.2 Port the registry and loader unit tests and the fixture
  `priority_override`, without `inlineIcon`. All eight new tests fail against
  the unchanged registry on v12 and v13.
- [x] 2.3 Port `CategoryTypePriorityTest` and the fixture extension
  `test_programs_category_type_priority`, in the shape of the fixture
  extensions of this branch. The program page and the details plugin are
  asserted on the list of `Program/Categories`. All five tests fail against
  the unchanged registry on v12 and v13.

## 3. Documentation

- [x] 3.1 Port the section "The order of the types" and the rewritten order
  statements of the category types developer page, without the reference to
  the filter types select; the page module summary page and the filter
  settings of the partner, project and program lists name the type order.
- [x] 3.2 Add the two changelog files to
  `typo3-category-types/Documentation/Changelog/2.4/`, byte-identical to
  those on `main`.
- [x] 3.3 Add `docs/architecture/category-type-order.md` without the program
  facts, with a row and a line in the architecture index, and list the
  fixture extension in `docs/testing/fixture-extensions.md`.

## 4. Definition of done

- [x] 4.1 `lintPhp` green.
- [x] 4.2 After `-t 12 -s composerUpdate`: `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 12`: 823 unit tests, 1788 functional tests
  listed and run.
- [x] 4.3 After `-t 13 -s composerUpdate`: `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 13`: 823 unit tests, 1909 functional tests
  listed and run.
- [x] 4.4 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 4.5 Commit message in TYPO3 Core format with the verified ACE-752
  reference.
- [ ] 4.6 Archive the change as the last commit of the pull request.
