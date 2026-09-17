## 1. Tests first

- [x] 1.1 Extend the fixture of `AcademicProjectPageTemplateTest` with a link
  target page and a short description and funders value that each contain
  `<a href="t3://page?uid=11">`; assert the resolved anchor and no `t3://` in
  the output. Record that it fails on the unchanged template on v12 and v13.
- [x] 1.2 Add `AcademicProjectsProjectListPluginTest` for this extension, with
  a list fixture whose short description contains the same link; assert the
  resolved anchor and record the failure on the unchanged partial on v12 and
  v13.
- [x] 1.3 Render the project page without the TypoScript of
  fluid_styled_content on v13 only (`not-core-12`); assert the resolved
  anchors and record the failure on the unchanged template.
- [x] 1.4 Render the same page on v12 only (`not-core-13`) and assert the core
  exception 1641989097 for the missing rich text configuration.

## 2. Template fix

- [x] 2.1 Replace `f:format.raw` with `f:format.html` in
  `Pages/AcademicProject.html` (short description and funders) and verify
  tests 1.1, 1.3 and 1.4 on v12 and v13.
- [x] 2.2 Do the same in `Partials/Project/Item.html` and verify test 1.2 on
  v12 and v13.

## 3. Documentation

- [x] 3.1 Add `Documentation/Changelog/2.4/Important-ProjectRichTextUsesParseFunc.rst`
  to `academic_projects`: the use of `lib.parseFunc_RTE`, the v12 requirement
  with the exception 1641989097, that v13 provides it, and the possible markup
  difference; verify it renders.
- [x] 3.2 Add a note on the rich text fields, with the v12 requirement, to the
  extension's `Documentation/Configuration/Index.rst`. `docs/` describes no
  TypoScript requirements of the page types, so no page there changes; state
  that in the pull request.

## 4. Definition of done

- [x] 4.1 `-t 12 -p 8.1`: `composerUpdate`, `lintPhp`, `cgl -n`, `phpstan`,
  `unit` and `functional` all green.
- [x] 4.2 `-t 13 -p 8.2`: `composerUpdate`, `lintPhp`, `cgl -n`, `phpstan`,
  `unit` and `functional` all green.
- [x] 4.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 4.4 Commit in TYPO3 Core format with ACE-676, state the v12 requirement
  on the issue, and archive the change as the last commit of the pull request.
