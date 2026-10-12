## 1. Decisions and premises

- [ ] 1.1 Confirm decision 1 (how the ordering reaches the database) and
  decision 2 (which time stands in for a missing date) of `design.md` with the
  maintainer before any code is written. If either differs from the
  recommendation, update this change first: the scenarios for decision 2, the
  design and these tasks for decision 1. Verify by the maintainer's answer
  recorded in the pull request description.
- [ ] 1.2 Re-measure every statement of `design.md`, *Context*, on `main` HEAD
  for TYPO3 v13 and v14: the three list queries, the core TCA of
  `lastUpdated`, the statement and count path of the Extbase backend and
  parser, the event dispatch, the overlay listener of `academic_base`, and the
  development seed. Verify by reading the files. If one is false, stop and
  update this change first.

## 2. Tests first

- [ ] 2.1 Add a fixture with three program pages, A (`lastUpdated`
  2026-01-01, older `tstamp`), B (`lastUpdated` 0, `tstamp` 2026-06-01) and C
  (`lastUpdated` 2025-01-01, older `tstamp`), plus two undated pages with the
  same `tstamp`, and cases to
  `packages/fgtclb/academic-programs/Tests/Functional/Domain/Repository/ProgramRepositoryOrderingTest.php`
  for "last updated" descending (B, A, C), ascending (C, A, B) and the tie
  (uid order). Record that the first two fail on the unchanged query, which
  returns A, C, B and B, C, A.
- [ ] 2.2 The same for partners in
  `academic-partners/Tests/Functional/Domain/Repository/PartnerRepositoryFindByDemandTest.php`,
  and add both "last updated" options to its `knownSortingOptions()` provider,
  which leaves them out today. Record the red run.
- [ ] 2.3 The same for projects in
  `academic-projects/Tests/Functional/Domain/Repository/ProjectRepositoryTest.php`,
  plus a case that combines "last updated" with the "Active" filter and
  asserts that the filter still applies. Record the red run of the ordering
  cases. `ace-433-projects-without-end-date` changes the "Active" constraint
  of the same query and test: when it landed first, the combined case
  includes a project without an end date and expects it listed.
- [ ] 2.4 Add a paginated partner list case to the plugin tests of
  `academic_partners` (two per page, five partners, "last updated"
  descending): the second page shows the third and fourth partner of the
  ordering and the pagination counts three pages. Record that it fails on the
  unchanged query, and that it fails again when the implementation is broken
  on purpose so that the count is taken from a query without the statement
  (restore from a backup, not with `git checkout`). Add a repository level
  case that fetches page 2 through `QueryResultPaginator` first and counts
  the whole result afterwards, and record what it reports: the statement
  builder is shared between the clone and the original query (design.md,
  "Two limits of the statement path").
- [ ] 2.5 Add a "last updated" case to the hidden records tests of one
  extension (`ProgramRepositoryShowHiddenRecordsTest` or the partner one) with
  the option "Show hidden records" on a translated page. It must pass on v13
  and v14 after the change. Record that it fails when the statement is built
  before the hidden records settings are applied (deliberate break, restored
  from a backup).

## 3. Implementation

- [ ] 3.1 Add the shared ordering service to `academic_base` as decided in
  1.1: `final readonly`, stateless, `@internal`, configured like the
  surrounding services of `academic_base`. Under decision 1 a) it creates a
  parser per call, carries over a suggested `DISTINCT`, quotes every
  identifier with `quoteIdentifier()` and adds the expression as an aliased
  select literal it orders by. Its docblock names the `@internal` core API it
  relies on. Verify with `get_file_problems` and `phpstan` on v13 and v14, no
  new baseline entry.
- [ ] 3.2 Call it from `findByDemand()` of `ProgramRepository`,
  `PartnerRepository` and `ProjectRepository` for the `lastUpdated` field
  only, keeping `setOrderings()` with the `uid` tiebreaker for every other
  option. Verify that the tests of group 2 pass on v13 and v14.
- [ ] 3.3 Run the tests of group 2 on PostgreSQL (`-b docker`) and MariaDB for
  v13 and v14, and record the result. PostgreSQL is where an unquoted
  `lastUpdated` fails.

## 4. Documentation

- [ ] 4.1 Add `Documentation/Changelog/3.0/Important-LastUpdatedSortingFallsBackToTheSaveTime.rst`
  to `academic_programs`, `academic_partners` and `academic_projects` from the
  template in `Build/Documentation/Templates/`: what a list sorted by "last
  updated" showed before, what it shows now, that paginated lists change, and
  that a list event listener narrowing the query of the returned result has to
  build a query of its own for this option. Render them and check that none
  produces a definition list.
- [ ] 4.2 Describe what "last updated" orders by in each extension's manual,
  next to the description of the sorting options of the list (the route
  enhancer tables at `Documentation/Configuration/RouteEnhancers/Index.rst`
  name the option only). Verify with `checkRstRenderingAll`.
- [ ] 4.3 Adjust the sentence on sorting by `tstamp` through a query of one's
  own in `academic-partners/Documentation/Changelog/3.0/Breaking-PartnerControllerIsFinal.rst`
  and any equal sentence of the program and project entries, pointing to the
  new `Important` entry. Verify with `grep -rn tstamp` in the three
  `Documentation/` folders.
- [ ] 4.4 Add a section to `docs/architecture/database-queries.md` under rule 3
  on an ordering Extbase cannot express: the mechanism chosen in 1.1, why the
  identifiers are quoted, how count and pagination follow the statement. Add a
  sentence to `docs/architecture/list-plugin-events.md` on the query of a
  returned result sorted this way.

## 5. Definition of done

- [ ] 5.1 `Build/Scripts/runTests.sh -t 13 -s composerUpdate`, then `lintPhp`,
  `cgl -n`, `phpstan`, `unit` and `functional` with `-t 13` green, functional
  on SQLite (`-j auto`), PostgreSQL (`-b docker -j 8`) and MariaDB (`-j 8`).
- [ ] 5.2 The same for `-t 14` after its own `composerUpdate`.
- [ ] 5.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 5.4 `docs/` and the three `Documentation/` changelog entries are part of
  the change, and `README.md` and `CONTRIBUTING.md` still only summarize and
  link.
- [ ] 5.5 Every test of group 2 was shown red as recorded there.
- [ ] 5.6 Commit in TYPO3 Core format as
  `[TASK] ACE-624: Order undated pages by save time`, body wrapped at 72,
  footer `Resolves: ACE-624`, no attribution of any kind.
- [ ] 5.7 Archive the change as the last commit of the same pull request and
  verify that `openspec/specs/academic-programs/program-list-sorting/spec.md`
  gained the requirement and the two new specs
  `academic-partners/list-sorting` and `academic-projects/list-sorting` exist.
