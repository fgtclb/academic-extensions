## 1. Tests first

- [x] 1.1 Extend the fixture of `AcademicProjectPageTemplateTest` with a short
  description and a funders value that each contain
  `<a href="t3://page?uid=1">`; assert a resolved `href` and no `t3://` in
  the output. Record that it fails on the unchanged template.
- [x] 1.2 Extend the project list plugin test with a project whose short
  description contains the same link, assert the resolved `href`, and record
  the failure on the unchanged partial.
- [x] 1.3 Render the project page on a site whose TypoScript does not include
  fluid_styled_content; assert the resolved `href` of the short description
  link, and record the failure on the unchanged template.

## 2. Template fix

- [x] 2.1 Replace `f:format.raw` with `f:format.html` in
  `Pages/AcademicProject.html` (short description and funders) and verify
  test 1.1 passes on v13 and v14.
- [x] 2.2 Do the same in `Partials/Project/Item.html` and verify test 1.2
  passes on v13 and v14.

## 3. Documentation

- [x] 3.1 Add `Documentation/Changelog/3.0/Important-ProjectRichTextUsesParseFunc.rst`
  to `academic_projects`, naming the use of `lib.parseFunc_RTE`, that TYPO3
  provides it for every site, and the possible markup difference; verify it
  renders.
- [x] 3.2 Add a note on the rich text fields to the extension's
  `Documentation/Configuration/Index.rst`, next to the existing
  `styles.content.getContent` warning. `docs/` describes no TypoScript
  requirements of the page types, so no page there changes; state that in
  the pull request.

## 4. File the issue

- [x] 4.1 After implementation, file the ACE issue in YouTrack, rename the
  change to `ace-<NNN>-project-rte-fields-parsefunc`, and commit in TYPO3
  Core format, e.g. `[BUGFIX] ACE-<NNN>: Render project rich text fields`.

## 5. Backport

- [ ] 5.1 Backport: separate change on branch `2` after a backport analysis
  (`docs/workflow/backporting.md`).

## 6. Definition of done

- [x] 6.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v13, all green.
- [x] 6.2 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v14, all green.
- [x] 6.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 6.4 `docs/` and the extension's `Documentation/` changelog updated in the
  same change.
- [x] 6.5 Archive the change as the last commit of the pull request.
