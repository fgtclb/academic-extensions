## 1. Reproduce

- [ ] 1.1 Add functional tests starting from a language-1 editing page: create
  a vita entry and assert the stored row language and that no synchronisation
  ran; change the website and assert it lands on the translation. Record the
  result on the unchanged code; if the defect does not reproduce, stop and
  report instead of fixing.

## 2. Step 1: announce the default-language profile

- [ ] 2.1 Dispatch `AfterProfileUpdateEvent` for the default-language profile
  after a write on a translation; functional test with a pending
  default-language contract asserts its language-1 translation after a
  translated save, shown red on the unchanged code.
- [ ] 2.2 Functional test that a translated website survives the following
  synchronisation, shown red by forcing the synchronisation to copy it.

## 3. Step 2: structure in the default language

- [ ] 3.1 Refuse adding, deleting and sorting documents and contacts while a
  translation is edited; functional tests for add, delete and sort on
  language 1 assert that nothing changes and the hint is returned, each
  shown red on the code of step 1.
- [ ] 3.2 Replace the add, delete and sort controls of the document and
  contact sections by the hint on a translation; functional test renders the
  language-1 editing page, shown red on the code of step 1.
- [ ] 3.3 Functional test that a text change to an existing translated entry
  on language 1 lands on the translation row and the default-language row
  keeps its text. It passes before and after the change; show it red by
  forcing the write onto the default-language row.

## 4. Documentation

- [ ] 4.1 Extend `docs/architecture/translation-synchronization.md` with the
  translated editing path.
- [ ] 4.2 Update `academic-persons-edit/Documentation/` and add
  `Documentation/Changelog/3.0/Important-EditingATranslationSynchronizesTheProfile.rst`
  and `Breaking-EntriesAreAddedInTheDefaultLanguage.rst` for the refused
  structural writes.

## 5. Backport

- [ ] 5.1 Backport: separate change on branch 2 after a backport analysis
  (`docs/workflow/backporting.md`); `origin/2` sets the overlay profile in
  `ProfileInformationFactory` the same way.

## 6. File the issue

- [ ] 6.1 After implementation, file the ACE issue in YouTrack, rename the
  change to `ace-<NNN>-editor-translation-writes`, and commit as
  `[BUGFIX] ACE-<NNN>: Synchronise after writes on translations` in TYPO3 Core
  format.

## 7. Definition of done

- [ ] 7.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v13, and the same after its own `composerUpdate` for
  TYPO3 v14; the translated editing tests also with `-d postgres`.
- [ ] 7.2 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 7.3 `docs/` and the extension's `Documentation/` changelog updated in the
  same change.
- [ ] 7.4 Archive the change as the last commit of the pull request.
