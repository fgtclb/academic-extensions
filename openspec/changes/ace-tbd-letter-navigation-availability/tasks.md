## 1. Preparation

- [ ] 1.1 Read ACE-597, ACE-598 and ACE-599 in YouTrack and align the tasks
  below with the acceptance criteria written there; note every difference in
  this file before coding.

## 2. No navigation for a manual selection (ACE-599)

- [ ] 2.1 Render the letter navigation only without a manual selection and
  skip the letter query then; add a functional list test with a manual
  selection and the navigation enabled asserting no navigation, and show it
  fails today because the navigation renders.

## 3. Letter availability (ACE-597)

- [ ] 3.1 Add the repository method that computes available letters under the
  list constraints with the letter cleared, ordered deterministically; add a
  functional test with profiles starting with A and B that asserts A and B
  available and C unavailable, including under an active letter A, and show
  it fails when the letter is not cleared.
- [ ] 3.2 Add functional tests for the storage folder and organisational unit
  restriction and for a hidden profile with the hidden-records option off.

## 4. Disabled letters and the reset option (ACE-598)

- [ ] 4.1 Render unavailable letters disabled without a link and mark the
  active letter as current; assert the markup in a functional test and show it
  fails today because every letter is a link.
- [ ] 4.2 Add the `activeLetterResets` site setting and constant (default `0`)
  and its mapping; add a functional test with the option on asserting the
  active letter links to the list without a letter.
- [ ] 4.3 Move the alignment class to the navigation wrapper and verify the
  existing list rendering assertions still hold.

## 5. Documentation

- [ ] 5.1 Document availability, the reset option and the new template
  variable in `Documentation/Configuration/` and `Documentation/Templates/`.
- [ ] 5.2 Add `Documentation/Changelog/3.0/Feature-LetterNavigationAvailability.rst`
  and an `Important-LetterNavigationMarkup.rst` for the markup change.
- [ ] 5.3 Add the availability query to `docs/architecture/database-queries.md`
  as an example of reusing list constraints.

## 6. Issue and commits

- [ ] 6.1 Verify ACE-597, ACE-598 and ACE-599 with a GET request, and ask the
  maintainer whether the reset option needs an issue of its own.
- [ ] 6.2 Rename the change to `ace-597-letter-navigation-availability` and
  verify `openspec validate` passes under the new name.
- [ ] 6.3 Commit one change per issue, `[FEATURE] ACE-597: ...`,
  `[FEATURE] ACE-598: ...` and `[BUGFIX] ACE-599: ...`, in TYPO3 Core format
  and.

## 7. Definition of done

- [ ] 7.1 `composerUpdate -t 13`, then `lintPhp`, `cgl -n`, `phpstan`, `unit`
  and `functional` green for v13, and `functional -d postgres` for the list
  tests.
- [ ] 7.2 `composerUpdate -t 14`, then `lintPhp`, `cgl -n`, `phpstan`, `unit`
  and `functional` green for v14, and `functional -d postgres` for the list
  tests.
- [ ] 7.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 7.4 `docs/` and the `Documentation/` changelog entries are part of the
  commits.
- [ ] 7.5 Archive the change as the last commit of the pull request.
