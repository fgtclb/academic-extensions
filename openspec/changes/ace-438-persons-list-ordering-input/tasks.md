## 1. Before the first commit

- [ ] 1.1 Confirm with Stefan decision 5 of `design.md` (an unknown sort
  field keeps the uid order, recommended) and the split into two commits
  (decision 1). If either changes, `/opsx:update` the change before writing
  code. Done when his answer is recorded in `design.md`.
- [ ] 1.2 Re-measure every premise of `design.md`, *Context*, on `main` HEAD,
  for v13 and v14: the parser lines and the `'' => ''` result, the warning for
  `firstName=` and `=First`, the strict allow list and the `+` of the uid
  fallback in `ProfileRepository`, the unguarded `get()`, the unchecked setter,
  the setter path of Extbase property mapping, the manual default and the
  `class-design.md` line references. Done when each is confirmed or the change
  is updated first.
- [ ] 1.3 Work in a git worktree below `.agent/tmp/wt/`, on a branch from
  `main`. Verify with `git -C <worktree> log -1` that it starts at `main`.

## 2. ACE-438: tests first

- [ ] 2.1 `Tests/Unit/DemandValues/AbstractDemandValuesTest.php`: change the
  three pinned cases (empty string, trailing comma, gap) to expect no empty
  item, rewrite the docblock that names the defect, and add the cases
  `firstName=` (labelled `firstName`), `=First` (dropped) and `=` (dropped).
  Red on unchanged code: the three pinned cases fail on `assertSame`, the new
  ones fail on the "Undefined array key 1" warning (`failOnWarning`).
- [ ] 2.2 `Tests/Unit/Types/AbstractTypesTest.php`: the same changes for the
  type parser. Red on unchanged code in the same way.
- [ ] 2.3 Add unit tests that `SortByValues` returns an empty list when
  `get()` throws `ExtensionConfigurationExtensionNotConfiguredException`, when
  it throws `ExtensionConfigurationPathDoesNotExistException`, and when it
  returns an array. Red on unchanged code: the exception escapes, and the array
  raises a `TypeError` in `trimExplode()`.
- [ ] 2.4 Add a functional test in
  `Tests/Functional/Domain/Repository/` that configures
  `demand/allowedSortByValues` and `demand/allowedGroupByValues` with a
  trailing comma, runs `findByDemand()` with `sortBy` and `groupBy` set to
  `''` under a frontend request, and asserts the uids in creation order.
- [ ] 2.5 Run 2.4 on unchanged code on SQLite, PostgreSQL (`-b docker`) and
  MariaDB, for v13 and v14, and record per DBMS whether it is red and with
  which database error. Done when the result is written into this task and
  into the pull request. If one DBMS accepts the empty identifier, say so,
  the unit tests remain the red proof there.

## 3. ACE-438: implementation

- [ ] 3.1 `AbstractDemandValues` and `AbstractTypes`: split the list with
  `removeEmptyValues`, split each entry into value and label without dropping
  an empty side, skip an empty value, label an empty label with the value
  (`design.md`, decision 2). 2.1, 2.2 and 2.4 pass.
- [ ] 3.2 `AbstractDemandValues`: catch the two exceptions of `get()` and treat
  a non-string as empty, with the comment `AbstractTypes` carries
  (decision 3). 2.3 passes.
- [ ] 3.3 Open a persons list element and an email, phone number and address
  record in the backend of a development instance of the worktree, with a
  trailing comma in each configured list. Done when every select offers no
  unlabelled item and the list renders, on v13 and v14.

## 4. ACE-438: documentation and commit

- [ ] 4.1 Add `Documentation/Changelog/3.0/Important-EmptyConfiguredValuesAreIgnored.rst`
  from `Build/Documentation/Templates/Changelog-Important.rst`, label
  `important-ace-438-academic-persons`: what an integrator with an empty entry
  saw before (a database error on every list on "none", an unlabelled item in
  the selects, a PHP warning for an entry with an empty side), what happens
  now. Done when `checkRstRenderingSingle academic-persons` is green.
- [ ] 4.2 `Documentation/Configuration/General/Index.rst`: correct the
  default of `demand.allowedSortByValues`, which repeats the group values, and
  state for both confvals and for the type lists that empty entries are
  ignored and an entry without a label is labelled with its value.
- [ ] 4.3 `docs/architecture/class-design.md`: correct the line references of
  both abstract classes to their class declaration after the change. Done when
  `lintMarkdown -n` is green.
- [ ] 4.4 Run `lintPhp`, `cgl -n`, `phpstan`, `unit` and the functional tests
  of `packages/fgtclb/academic-persons` on SQLite for v13 and v14 on this
  commit alone, so it is green without the next one.
- [ ] 4.5 Commit `[BUGFIX] ACE-438: Drop empty configured list values` in
  TYPO3 Core format, subject at most 52 characters, body at 72, footers
  `Resolves: ACE-438` and `Related: ACE-434` (its item 3, see `design.md`,
  decision 2), no attribution of any kind.

## 5. ACE-439: tests first

- [ ] 5.1 `Tests/Unit/Domain/Model/Dto/ProfileDemandTest.php`: `DESC` reads
  back `desc`, ` Asc ` reads back `asc`, `down` on a fresh demand reads back
  `asc`, `desc` followed by `reverse` reads back `desc`, and `''` keeps the
  previous value. Correct the docblock of `aFreshDemandSortsByLastNameAscending()`,
  which says a list without a valid ordering has no `ORDER BY`, the uid
  fallback exists since ACE-482. Red on unchanged code: the setter stores the
  raw string.
- [ ] 5.2 Add a functional repository test: profiles whose last names are not
  in creation order, `sortBy` `lastName`, direction set to `DESC` through the
  setter, assert the last names from Z to A. Red on unchanged code: the uids
  come back in creation order.
- [ ] 5.3 Add a functional list plugin test: a list element that stores no
  sort direction of its own, the TypoScript constant
  `plugin.tx_academicpersons.demand.sortByDirection` set to `DESC`, sort by
  last name, assert the rendered order from Z to A. It proves that property
  mapping writes through the setter. Red on unchanged code.

## 6. ACE-439: implementation

- [ ] 6.1 `ProfileDemand::setSortByDirection()`: lowercase and trim, store
  `asc` or `desc`, keep the previous value otherwise, and say so in its
  docblock (`design.md`, decision 4). The repository stays unchanged. 5.1 to
  5.3 pass.
- [ ] 6.2 Only if 1.1 chose option (b) or (c) of decision 5: implement it
  with its own test shown red first, after the change was updated.

## 7. ACE-439: documentation and commit

- [ ] 7.1 Add `Documentation/Changelog/3.0/Important-SortDirectionIsNormalised.rst`,
  label `important-ace-439-academic-persons`: the direction is read in any
  letter case, an unknown one keeps the previous direction, a listener reads
  back the lowercase form, and the program list keeps unknown input the same
  way. Done when `checkRstRenderingSingle academic-persons` is green.
- [ ] 7.2 No `docs/` page and no manual page documents the sort direction
  setting today. Check again, and state the result in the pull request.
- [ ] 7.3 Commit `[BUGFIX] ACE-439: Normalise persons sort direction` in
  TYPO3 Core format, footer `Resolves: ACE-439`, no attribution of any
  kind.

## 8. Definition of done

- [ ] 8.1 For `-t 13` and `-t 14`, each after its own `composerUpdate`:
  `lintPhp`, `cgl -n`, `phpstan`, `unit`, and `functional` on SQLite
  (`-j auto`), PostgreSQL (`-b docker -j 8`) and MariaDB (`-j 8`). Record the
  counts in this task.
- [ ] 8.2 `lintMarkdown -n` and `checkRstRenderingAll` green, and no rendered
  changelog entry contains a definition list.
- [ ] 8.3 Every behaviour change has a test that was shown red without it:
  2.1 to 2.5, 5.1 to 5.3.
- [ ] 8.4 `docs/` (4.3), the manual (4.2) and two `Important` entries in
  `Changelog/3.0/` (4.1, 7.1) are part of the two commits.
- [ ] 8.5 A dedicated review of the branch before the push, until the
  reviewer has no open finding.
- [ ] 8.6 Archive the change as the last commit of the same pull request,
  `[TASK] ACE-438: Archive the persons ordering fix`, footer
  `Related: ACE-438` and `Related: ACE-439`.

## 9. Backport

- [ ] 9.1 Backport to branch `2`: a change of its own on that branch after a
  backport analysis (`docs/workflow/backporting.md`), read against its own
  `AGENTS.md`. Not part of this pull request.
