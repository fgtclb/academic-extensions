## 1. Backport analysis

- [x] 1.1 Diff the touched files between `main` and this branch, and record
  in `design.md` which differences touch this change.
- [x] 1.2 Check the APIs against TYPO3 v12 and v13: `styles.content.get`,
  `FLUIDTEMPLATE` variables, `PAGEVIEW` and site sets (v13 only).

## 2. Tests first

- [x] 2.1 Port the content tests of `AcademicProgramPageTemplateTest` from
  `main`: manual order, other columns and hidden elements, a translated page,
  an integrator override, no variable on other page types, two tied pairs
  written in opposite directions; the `PAGEVIEW` case and the site set cases
  (component sets and aggregate) with the group `not-core-12`. Drop the
  content-load include from the test setup.
- [x] 2.2 Record that they fail on the unchanged template: on v12, 6 of 7 die
  with the `f:cObject` exception; the seventh, no variable on other page
  types, is a guard.
- [x] 2.3 Measure the tie test without the `uid` tiebreaker on v12 and v13:
  PostgreSQL 10 and 16 fail, SQLite passes.

## 3. Implementation

- [x] 3.1 Add `page.10.variables.programContent` inside the doktype 20
  condition and render it in `AcademicProgram.html`.
- [x] 3.2 Correct the comments of the content-load set and TypoScript and of
  the `core-13` site configuration.

## 4. Documentation

- [x] 4.1 `Documentation/Configuration/Index.rst`: the content load section no
  longer warns about the page type; a new section on the program page
  content.
- [x] 4.2 The 2.4 Breaking entry of the configuration split: the warning is
  replaced by a pointer to the new entry.
- [x] 4.3 `Documentation/Changelog/2.4/Important-ProgramPageRendersItsOwnContent.rst`.
- [x] 4.4 `docs/architecture/database-queries.md`: the paragraph `main` added
  on tied rows, whose order depends on the PostgreSQL version, with this
  branch's core versions.

## 5. Definition of done

- [x] 5.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` (SQLite and PostgreSQL) green with `-t 12`.
- [x] 5.2 The same with `-t 13`.
- [x] 5.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 5.3a `docs/` (task 4.4) and the extension's `Documentation/` with its
  2.4 changelog entry (tasks 4.1 to 4.3) updated in the same change.
- [x] 5.4 Commit in TYPO3 Core format, `[BUGFIX] ACE-721: <subject>`.
- [ ] 5.5 Archive the change as the last commit of the pull request.
