## 1. Decision and premises

- [ ] 1.1 Confirm decision 2 of design.md (who builds the items, option A
  recommended) and decision 3 (`showIfEmpty`) with the maintainer. For option
  B, drop the scenario "Editor without access to the folder of the person
  records" with `/opsx:update` before any code. Verified when the decision is
  recorded in design.md.
- [ ] 1.2 Re-check every premise of design.md, "Context", on HEAD for both
  cores (`.Build/vendor` after `composerUpdate -t 14`, `core-13/vendor`):
  the TCA lines, the handler query, `isReferenceField()`, the `RelationHandler`
  handling of `0` and `-1`, `castReferenceValue()`,
  `synchronizeDirectRelations()`, `processSelectFieldValue()`,
  `TcaColumnsRemoveEmptyRelations` on v14 only, and that the dedicated
  selection is still in no release tag. Verified when every premise is
  confirmed, or the change is updated with `/opsx:update` first.

## 2. Tests first, each shown red on the unchanged code

All new tests extend the shared functional base class and load
`typo3/cms-workspaces` where they need it (shape of
`ContactLocalizationPolicyWorkspaceTest`). Each runs on v13 and v14.

- [ ] 2.1 Form compile test in workspace 1: a contract with an e-mail address
  changed in workspace 1 and a phone number created in workspace 2. The
  e-mail items hold the live uid once, labelled with the changed value, and no
  version uid. The phone items hold no record of workspace 2. Red today: the
  version uid and the workspace 2 row are offered.
- [ ] 2.2 Form compile test in live for the same fixture: one item per
  address, live values, no version row, no workspace row. Red today.
- [ ] 2.3 Copy test through `DataHandler`: copy a contract with an e-mail
  address and a page contact selecting it (and one selecting `-1`). The copied
  contact selects the copied e-mail, the `-1` stays, the original contact is
  unchanged. A second case copies the profile. Red today: the copy keeps the
  source uid.
- [ ] 2.4 Reference index test: saving a contact that selects an e-mail writes
  a `sys_refindex` row for `email_address` pointing at it. Assert and document
  what is written for `-1` and `0`. Red today: no row.
- [ ] 2.5 Invalid selection test: compile the form of a contact whose e-mail
  selection points at an address of another contract. The row value is kept
  and an invalid item is shown, and saving the compiled row values of the
  three selects through `DataHandler`, as a form submit does, keeps the
  selection. Red today: the row value is dropped.
- [ ] 2.6 Empty contract test: compile the form of a contact whose contract has
  no phone number. The phone select is present with "display all" and
  "display none" on both cores. Red on v14 once `foreign_table` is set and
  before `showIfEmpty` is added (record that intermediate run).
- [ ] 2.7 Option A only: compile the form as a non-admin editor without
  `PAGE_SHOW` on the folder of the person records. The address items are
  offered. Shown red by letting core's items through (option B behaviour).
- [ ] 2.8 Publish test: in workspace 1 change an e-mail of a contract, select
  it on a page contact using the value the compiled form offers, publish the
  workspace. The live contact selects the live uid and the contacts list
  renders the changed address. This pins the corrected premise. It is green
  on a correct pick today, so its red proof is 2.1, and the pull request says
  so.
- [ ] 2.9 Keep `AddressRecordSelectionTest` green unchanged on both cores
  (`0`, `-1` and a uid survive a save), and update its class docblock, which
  says the fields have no `foreign_table`.
- [ ] 2.10 Versioning test, before any TCA change is kept: in workspace 1,
  save the contract (one datamap entry for the contract only) while a page
  contact of it selects an e-mail address, so the contract is versioned with
  its addresses and contacts in one command. Assert that the contact version
  still selects the live e-mail uid, then publish and assert that the live
  contact selects the live uid and the page renders the address. Run it once
  with `foreign_table` set and before any handler change. If the selection
  holds the address version uid, stop and take design.md, "Open Questions",
  to the maintainer.

## 3. Implementation

- [ ] 3.1 TCA: `foreign_table`, `foreign_table_where` (contract, language,
  `sorting` then `uid`, every column qualified with the address table because
  core joins `pages`, design decision 1) and `showIfEmpty` on the three
  columns. Tests 2.3, 2.4, 2.5 and 2.6 turn green.
- [ ] 3.2 Items handler per decision 2: workspace restriction and overlay,
  live uid as value, no delete placeholders, core's positive items replaced
  (option A). Tests 2.1, 2.2 and 2.7 turn green, the existing
  `AddressRecordItemsTest` stays green or is adjusted where it asserted the
  old query.
- [ ] 3.3 Run `seedManifest` for v13 and v14. The manifests must not change.
  If they do, stop and find out why before committing them.

## 4. Documentation

- [ ] 4.1 Add `academic-contact4pages/Documentation/Changelog/3.0/Important-AddressSelectionsAreRelations.rst`
  from `Build/Documentation/Templates/`: what an editor saw before, the
  copy, the reference index (`referenceindex:update` once), invalid
  selections now kept and shown, the v14 form. Render without a definition
  list (`grep -c "<dt>"` is 0).
- [ ] 4.2 Rewrite the note of
  `Important-ContactInlineParentIsWorkspaceAware.rst`: records keep their uid
  on publish, what was wrong and that this change fixes it, with a link.
- [ ] 4.3 `docs/architecture/backend-select-items.md`: the address selects are
  the handler served selects with a `foreign_table`, what that changes for a
  value not among the items, and `showIfEmpty` against v14's removal of empty
  relation selects. `lintMarkdown -n` green.

## 5. The issue and the commit

- [ ] 5.1 YouTrack ACE-481 (exists, do not file a new one) to `In Progress`
  at the start, `Testing PR` with the pull request, `Done` after the merge.
- [ ] 5.2 Commit as `[BUGFIX] ACE-481: Make address selections relations` in
  TYPO3 Core format, body at 72, footer `Resolves: ACE-481`, no attribution
  of any kind.

## 6. Definition of done

- [ ] 6.1 `composerUpdate -t 13`, then `lintPhp`, `cgl -n`, `phpstan`, `unit`,
  and `functional` on SQLite (`-j auto`), PostgreSQL (`-b docker -j 8`) and
  MariaDB (`-j 8`) for TYPO3 v13.
- [ ] 6.2 `composerUpdate -t 14`, then the same for TYPO3 v14.
- [ ] 6.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 6.4 `docs/` and the `Documentation/Changelog/3.0/` entries are part of
  the change. `README.md` and `CONTRIBUTING.md` still only summarize and link.
- [ ] 6.5 Archive the change as the last commit of the same pull request.
