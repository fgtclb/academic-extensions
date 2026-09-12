## 1. Tests first

- [ ] 1.1 Extend the fixture of `AcademicProjectPageTemplateTest` with a short
  description and a funders value that each contain
  `<a href="t3://page?uid=1">`; assert a resolved `href` and no `t3://` in
  the output. Record that it fails on the unchanged template.
- [ ] 1.2 Extend the project list plugin test with a project whose short
  description contains the same link, assert the resolved `href`, and record
  the failure on the unchanged partial.

## 2. Template fix

- [ ] 2.1 Replace `f:format.raw` with `f:format.html` in
  `Pages/AcademicProject.html` (short description and funders) and verify
  test 1.1 passes on v13 and v14.
- [ ] 2.2 Do the same in `Partials/Project/Item.html` and verify test 1.2
  passes on v13 and v14.

## 3. Documentation

- [ ] 3.1 Add `Documentation/Changelog/3.0/Important-ProjectRichTextUsesParseFunc.rst`
  to `academic_projects`, naming the `lib.parseFunc_RTE` requirement, the
  exception 1641989097 and the possible markup difference; verify it
  renders.
- [ ] 3.2 Add the `lib.parseFunc_RTE` requirement to the integrator chapter
  of the extension's `Documentation/`, and to `docs/` where the TypoScript
  requirements of the page types are described.

## 4. File the issue

- [ ] 4.1 After implementation, file the ACE issue in YouTrack, rename the
  change to `ace-<NNN>-project-rte-fields-parsefunc`, and commit in TYPO3
  Core format, e.g. `[BUGFIX] ACE-<NNN>: Render project rich text fields`.

## 5. Backport

- [ ] 5.1 Backport: separate change on branch `2` after a backport analysis
  (`docs/workflow/backporting.md`).

## 6. Definition of done

- [ ] 6.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v13, all green.
- [ ] 6.2 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v14, all green.
- [ ] 6.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 6.4 `docs/` and the extension's `Documentation/` changelog updated in the
  same change.
- [ ] 6.5 Archive the change as the last commit of the pull request.
