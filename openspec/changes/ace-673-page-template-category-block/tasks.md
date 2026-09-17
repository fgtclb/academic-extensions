## 1. Tests first

- [x] 1.1 Extend the fixture of `AcademicPartnerPageTemplateTest` with
  categories of registered partner category types, one of them assigned to no
  page, add tests for the listed and the omitted type, and record that the
  listing test fails on the unchanged template on v12 and v13.
- [x] 1.2 Merge the same kind of fixture into the ACE-676 fixture of
  `AcademicProjectPageTemplateTest`, keeping page 11 as link target, add the
  same tests and record the failure on v12 and v13.
- [x] 1.3 Add a test per page type asserting that a page without categories
  renders no category list, and verify it passes before and after the change.

## 2. Template fix

- [x] 2.1 Switch `academic-partners/Resources/Private/Pages/AcademicPartner.html`
  to the `attributes` accessor and verify test 1.1 passes on v12 and v13.
- [x] 2.2 Switch `academic-projects/Resources/Private/Pages/AcademicProject.html`
  to the `attributes` accessor and verify test 1.2 passes on v12 and v13.
- [x] 2.3 Verify the diff of both templates touches only the category lines.

## 3. Documentation

- [x] 3.1 Add `Documentation/Changelog/2.4/Important-PartnerPageRendersCategories.rst`
  to `academic_partners` and `Important-ProjectPageRendersCategories.rst` to
  `academic_projects`, and verify both render with `checkRstRenderingAll`.
- [x] 3.2 `docs/` describes no page template category block; nothing changes
  there, which the pull request states.

## 4. Definition of done

- [x] 4.1 `-t 12 -p 8.1`: `composerUpdate`, `lintPhp`, `cgl -n`, `phpstan`,
  `unit` and `functional` all green.
- [x] 4.2 `-t 13 -p 8.2`: `composerUpdate`, `lintPhp`, `cgl -n`, `phpstan`,
  `unit` and `functional` all green.
- [x] 4.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 4.4 Commit in TYPO3 Core format with ACE-673 and archive the change as
  the last commit of the pull request.
