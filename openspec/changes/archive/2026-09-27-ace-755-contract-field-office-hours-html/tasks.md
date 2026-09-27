## 1. Tests

- [x] 1.1 Add card fixtures with editor HTML, unsafe markup and plain text
  office hours, once with office hours selected and once with the default
  fields, and functional card tests for markup, unsafe markup, line breaks
  and the default fields, a card test that position and room stay escaped, and
  a contacts test of `academic_contacts4pages`. Show the office hours tests
  fail against the unchanged partial, where the card prints
  `&lt;p&gt;Tuesday …` as text, and the escaping test against a partial that
  prints position and room raw.

## 2. Partial

- [x] 2.1 Give office hours a branch of their own in
  `Partials/Profile/Contract/Field.html`, rendered with `f:format.nl2br` and
  `f:sanitize.html`. Show the unsafe markup test fails with `f:format.raw`
  instead of the sanitizer, and the line break test without `f:format.nl2br`.

## 3. Documentation

- [x] 3.1 Add
  `Documentation/Changelog/3.0/Important-ContractFieldOfficeHoursRenderAsHtml.rst`.
- [x] 3.2 Name the list items in the office hours part of
  `Documentation/Configuration/Sections/Index.rst`, or where the fields to show
  are documented.

## 4. Definition of done

- [x] 4.1 `composerUpdate -t 13`, then `lintPhp`, `cgl -n`, `phpstan`, `unit`
  and `functional` green for v13.
- [x] 4.2 `composerUpdate -t 14`, then `lintPhp`, `cgl -n`, `phpstan`, `unit`
  and `functional` green for v14.
- [x] 4.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 4.4 Commit as `[BUGFIX] ACE-755: Render office hours of list items` in
  TYPO3 Core format.
- [x] 4.5 Archive the change as the last commit of the pull request.
- [x] 4.6 No backport to branch `2`, see the non-goals of the proposal.
