## 1. Premises

- [x] 1.1 Re-read the backport analysis on `origin/2` against TYPO3 12.4.45
  and 13.4.36: the two repositories, their tests, the templates, the v12
  data mapper and overlay. Stop and report if one differs.

## 2. Tests first

- [x] 2.1 Port the partnership tests of `main` (repository and frontend),
  with the identity map pair as one test, and record them red on v12 and v13
  before the change.
- [x] 2.2 Replace the four pinned contact tests with the language tests of
  `main` and record them red on v12 and v13 before the change.
- [x] 2.3 Add a preview test for each repository, green before the change,
  and show it red with a pre-query that filters hidden records always. Add a
  visible record with a hidden translation for each repository and show it
  red with a pre-query that does not filter hidden records.

## 3. Implementation

- [x] 3.1 `PartnershipRepository`, `PartnershipRoles`, `PartnershipProcessor`,
  `PartnerController` and the two templates as on `main`, adjusted to this
  branch. Show the frontend test red with the old templates and with a role
  collector that ignores the language.
- [x] 3.2 `ContactRepository::findByPid()` with the pre-query, its
  `$showHidden` handling and the preview.
- [x] 3.3 Verify TCA, `ext_tables.sql` and the DataHandler hooks are not part
  of the diff.

## 4. Documentation

- [x] 4.1 `Documentation/Changelog/2.4/Important-PartnershipsRenderOncePerLanguage.rst`
  of `academic_partners` and
  `Documentation/Changelog/2.4/Important-PageContactsRenderOncePerLanguage.rst`
  of `academic_contacts4pages`.
- [x] 4.2 `docs/architecture/translation-synchronization.md`: the read side of
  both repositories on this branch.

## 5. Definition of done

- [x] 5.1 After `composerUpdate` for TYPO3 v12: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` on SQLite (`-j auto`), PostgreSQL
  (`-b docker -j 8`) and MariaDB (`-j 8`) green.
- [x] 5.2 After `composerUpdate` for TYPO3 v13: the same gates green.
- [x] 5.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 5.4 Commit as `[BUGFIX] ACE-430: Show translated records only once` in
  TYPO3 Core format, footers `Resolves: ACE-430`, `Related: ACE-484`,
  `Related: ACE-874` and `Related: ACE-905`, no attribution of any kind.
- [x] 5.5 Archive the change as the last commit of the same pull request.
