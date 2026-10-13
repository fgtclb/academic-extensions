## 1. Tests first

- [ ] 1.1 Shared trait in `packages-dev/testing-helper`: collect the inline
  relations of the loaded TCA whose child table has a given prefix, and assert
  that every pointer typed `select` with `foreign_table`, or `group`, lists the
  parent table in `dontRemapTablesOnCopy`. Named in
  `docs/testing/testing-helper.md` and in the trait count of `AGENTS.md`.
- [ ] 1.2 A TCA test using it in `academic_persons`, `academic_contacts4pages`,
  `academic_partners` and `academic_study_plan`, naming the relations each
  extension is expected to have. Red on the unchanged TCA for the first three,
  recorded per core.
- [ ] 1.3 `academic_persons`: a workspace edit and publish per relation (the
  contract of the three address tables, the profile and the organisational
  unit of a contract, the profile of the profile information), published from
  the workspace module and from live, a copy per parent, a translation of a
  profile and of a contract, and a detail page render after a profile and a
  contract were edited in a workspace and published from both places. Red on
  the unchanged TCA for every publish case, recorded per core: published from
  live the child loses its parent, from the module the version of the child
  points at the parent's version row. The copy and translation cases pass
  before and after, they pin what must not change.
- [ ] 1.4 `academic_contacts4pages`: the same for the page and the role of a
  page contact, plus the page and the role edited in the same workspace, which
  loses the page from the workspace module too, with a contacts list render.
  The third test of `AddressSelectionPublishTest` asserts the rendered page as
  well, and the test uses the publishing trait.
- [ ] 1.5 `academic_partners`: the same for the page and the role of a
  partnership, with a render of the partnerships.

## 2. The fix

- [ ] 2.1 `dontRemapTablesOnCopy` with the parent table on the ten pointer
  columns, with a comment on each that says why. Every test of group 1 green
  on both cores.
- [ ] 2.2 `seedManifest` for v13 and v14: the manifests do not change.

## 3. Documentation

- [ ] 3.1 `Important-` entries in `Documentation/Changelog/3.0/` of
  `academic_persons`, `academic_contacts4pages` and `academic_partners` from
  the template in `Build/Documentation/Templates/`: what an editor saw, that
  it is fixed, and the queries that find children whose parent no longer
  exists. The ACE-481 entry is cross linked.
- [ ] 3.2 `docs/architecture/backend-select-items.md`: a section next to the
  one on the address selects, on parent pointers and `dontRemapTablesOnCopy`.
  `lintMarkdown -n` green.

## 4. The issue and the commit

- [ ] 4.1 YouTrack ACE-907 (exists, do not file a new one): `In Progress` at
  the start, `Testing PR` with the pull request, `Done` after the merge.
- [ ] 4.2 The proposal as the first commit, the fix as
  `[BUGFIX] ACE-907: Keep inline children on publish` in TYPO3 Core format,
  body at 72, footer `Resolves: ACE-907`, no attribution of any kind.

## 5. Definition of done

- [ ] 5.1 `composerUpdate -t 13`, then `lintPhp`, `cgl -n`, `phpstan`, `unit`,
  and `functional` on SQLite (`-j auto`), PostgreSQL (`-b docker -j 8`) and
  MariaDB (`-j 8`) for TYPO3 v13.
- [ ] 5.2 `composerUpdate -t 14`, then the same for TYPO3 v14.
- [ ] 5.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 5.4 `docs/` and the `Documentation/Changelog/3.0/` entries are part of
  the change. `README.md` and `CONTRIBUTING.md` still only summarize and link.
- [ ] 5.5 Archive the change as the last commit of the same pull request.
