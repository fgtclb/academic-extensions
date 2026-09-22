## 1. Preparation

- [x] 1.1 Read ACE-597, ACE-598 and ACE-599 in YouTrack and align the tasks
  below with the acceptance criteria written there; note every difference in
  this file before coding. The differences are recorded in `design.md`,
  section *Differences from the issues*; the tasks below follow the issues'
  test plans, and 4.3 is dropped (see `design.md`, *Markup*).

## 2. No navigation for a manual selection (ACE-599)

- [x] 2.1 Render the letter navigation only without a manual selection; add
  a functional list test with a manual selection and the navigation enabled
  asserting no navigation, and show it fails today because the navigation
  renders.

## 3. Letter availability (ACE-597)

- [x] 3.1 Add the letter constant and the repository method that computes
  availability over the list query with the letter cleared, through both
  demand and query events; add a repository test with absolute expectations
  for enable fields, storage folders, function type and organisational unit,
  the four language overlay modes, the fallback option, the hidden-records
  option, live and a workspace, and a manual selection. Show it fails when
  the letter is not cleared.
- [x] 3.2 Add the parity test: for every fixture set, scenario and letter,
  the availability equals whether the list for that letter is non-empty -
  by the list's own count, and in live also by its records. Include a name
  starting with an umlaut, whose placement is DBMS specific.
- [x] 3.3 Cover both events: a demand listener and a query listener each
  narrow the letters as they narrow the list.
- [x] 3.4 Assign `alphabetFilterLetters` in the list action only when the
  navigation is on and no selection is set; assert the query runs only then.

## 4. Disabled letters and the reset option (ACE-598)

- [x] 4.1 Render unavailable letters disabled without a link, give the
  navigation an accessible name, and mark the active letter and "A-Z" as
  current; keep every letter a link when the variable is not passed. Assert
  the markup of the shipped partial for the list and the list-and-detail
  plugin, and show it fails today because every letter is a link.
- [x] 4.2 Add the `activeLetterResets` site setting and constant (default
  `0`) and its mapping; add a functional test with the option on asserting
  the active letter links to the list without a letter.
- [x] 4.3 ~~Move the alignment class to the navigation wrapper.~~ Dropped
  before implementing: Bootstrap's utility classes are `!important`, so the
  move gains a project nothing - see `design.md`, *Markup*.

## 5. Documentation

- [x] 5.1 Document availability, the reset option and the new template
  variable in `Documentation/Configuration/` and `Documentation/Templates/`.
- [x] 5.2 Changelog entries in `Documentation/Changelog/3.0/`: an
  `Important-` entry for the navigation that disappears with a manual
  selection, `Feature-LetterNavigationAvailability.rst`, and an
  `Important-LetterNavigationMarkup.rst` for the markup change, and
  `Feature-LetterNavigationActiveLetterReset.rst` for the reset option - a
  setting added is a `Feature`.
- [x] 5.3 Add the availability query to `docs/architecture/database-queries.md`
  as an example of reusing list constraints.

## 6. Issue and commits

- [x] 6.1 Verify ACE-597, ACE-598 and ACE-599 with a GET request, and ask the
  maintainer whether the reset option needs an issue of its own. It does:
  ACE-718, filed after the implementation was green, with a commit of its
  own.
- [x] 6.2 Rename the change to `ace-597-letter-navigation-availability` and
  verify `openspec validate` passes under the new name.
- [x] 6.3 Commit one change per issue - `[BUGFIX] ACE-599: ...`,
  `[FEATURE] ACE-597: ...`, `[FEATURE] ACE-598: ...` and
  `[FEATURE] ACE-718: ...` - in TYPO3 Core format.

## 7. Definition of done

- [x] 7.1 `composerUpdate -t 13`, then `lintPhp`, `cgl -n`, `phpstan`, `unit`
  and `functional` green for v13, and `functional -d postgres`.
- [x] 7.2 `composerUpdate -t 14`, then `lintPhp`, `cgl -n`, `phpstan`, `unit`
  and `functional` green for v14, and `functional -d postgres`.
- [x] 7.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 7.4 `docs/` and the `Documentation/` changelog entries are part of the
  commits.
- [x] 7.5 Archive the change as the last commit of the pull request.
