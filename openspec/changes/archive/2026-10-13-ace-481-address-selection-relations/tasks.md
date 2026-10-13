## 1. Premises

- [x] 1.1 Re-check the context of design.md on v12 and v13, in particular that
  the `0` and `-1` of `AddressRecordSelectionTest` survive a save with
  `foreign_table` set, and the text comparison on PostgreSQL.

## 2. Tests first, each shown red on the unchanged code

- [x] 2.1 Form compile tests: the items of a contract, as an editor without
  access to the person records, a selection of another contract kept through
  a submit of the compiled values, a contact whose contract is gone compiled
  without a flash message. Every compile asserts an empty flash queue.
- [x] 2.2 Copy tests through `DataHandler`: a contract with a translated page
  contact, a profile.
- [x] 2.3 Translation test: localizing a contract keeps the contact
  translation on the default language addresses.
- [x] 2.4 Reference index tests: a saved selection, `-1` documented, and the
  selection of a copy.
- [x] 2.5 Keep `AddressRecordSelectionTest` green and update its docblock.

## 3. Implementation

- [x] 3.1 TCA per decision 1.
- [x] 3.2 Items handler per decision 3.
- [x] 3.3 Copy hook per decision 2, registered in `ext_localconf.php`, public
  in `Services.yaml`.
- [x] 3.4 Prove each part by removing it alone and watching its tests fail,
  restoring from a backup.

## 4. Documentation

- [x] 4.1 Add `Important-AddressSelectionsAreRelations.rst` to
  `Changelog/2.4/` of `academic_contacts4pages`. Render without a definition
  list.
- [x] 4.2 `docs/architecture/backend-select-items.md` of this branch, if it
  has the section on a value that is not among the items.

## 5. Definition of done

- [x] 5.1 `composerUpdate -t 12`, then `lintPhp`, `cgl -n`, `phpstan`, `unit`
  and `functional` on SQLite (`-j auto`), PostgreSQL (`-b docker -j 8`) and
  MariaDB (`-j 8`) for TYPO3 v12.
- [x] 5.2 `composerUpdate -t 13`, then the same for TYPO3 v13.
- [x] 5.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 5.4 Commit as `[BUGFIX] ACE-481: Make address selections relations` in
  TYPO3 Core format, no attribution of any kind.
- [x] 5.5 Archive the change as the last commit of the same pull request.
