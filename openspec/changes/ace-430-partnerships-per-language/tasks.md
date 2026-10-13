## 1. Premises

- [ ] 1.1 Re-read the backport analysis on `origin/2` against TYPO3 12.4.45
  and 13.4.36: the two repositories, their tests, the templates, the v12
  data mapper and overlay. Stop and report if one differs.

## 2. Tests first

- [ ] 2.1 Port the partnership tests of `main` (repository and frontend),
  with the identity map pair as one test, and record them red on v12 and v13
  before the change.
- [ ] 2.2 Replace the four pinned contact tests with the language tests of
  `main` and record them red on v12 and v13 before the change.
- [ ] 2.3 Add a preview test for each repository, green before the change,
  and show it red with a pre-query that filters hidden records always.

## 3. Implementation

- [ ] 3.1 `PartnershipRepository`, `PartnershipRoles`, `PartnershipProcessor`,
  `PartnerController` and the two templates as on `main`, adjusted to this
  branch. Show the frontend test red with the old templates and with a role
  collector that ignores the language.
- [ ] 3.2 `ContactRepository::findByPid()` with the pre-query, its
  `$showHidden` handling and the preview.
- [ ] 3.3 Verify TCA, `ext_tables.sql` and the DataHandler hooks are not part
  of the diff.

## 4. Documentation

- [ ] 4.1 `Documentation/Changelog/2.4/Important-PartnershipsRenderOncePerLanguage.rst`
  of `academic_partners` and
  `Documentation/Changelog/2.4/Important-PageContactsRenderOncePerLanguage.rst`
  of `academic_contacts4pages`.
- [ ] 4.2 `docs/architecture/translation-synchronization.md`: the read side of
  both repositories on this branch.

## 5. Definition of done

- [ ] 5.1 After `composerUpdate` for TYPO3 v12: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` on SQLite (`-j auto`), PostgreSQL
  (`-b docker -j 8`) and MariaDB (`-j 8`) green.
- [ ] 5.2 After `composerUpdate` for TYPO3 v13: the same gates green.
- [ ] 5.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 5.4 Commit as `[BUGFIX] ACE-430: Show contacts and partnerships once` in
  TYPO3 Core format, footers `Resolves: ACE-430`, `Related: ACE-484` and
  `Related: ACE-874`, no attribution of any kind.
- [ ] 5.5 Archive the change as the last commit of the same pull request.
