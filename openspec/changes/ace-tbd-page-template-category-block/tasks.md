## 1. Tests first

- [ ] 1.1 Extend the fixture of `AcademicPartnerPageTemplateTest` with one
  `sys_category` of a registered partner category type assigned to the
  doktype 40 page, add a test asserting the category title and the type
  label, and record that it fails on the unchanged template.
- [ ] 1.2 Do the same for `AcademicProjectPageTemplateTest` on the doktype 30
  page, and record the failure on the unchanged template.
- [ ] 1.3 Add a test per page type asserting that a page without categories
  renders no category list, and verify it passes before and after the
  change.

## 2. Template fix

- [ ] 2.1 Switch `academic-partners/Resources/Private/Pages/AcademicPartner.html`
  to the `attributes` accessor and verify test 1.1 passes on v13 and v14.
- [ ] 2.2 Switch `academic-projects/Resources/Private/Pages/AcademicProject.html`
  to the `attributes` accessor and verify test 1.2 passes on v13 and v14.
- [ ] 2.3 Verify the diff of both templates touches only the category lines
  and adds no `styles.content.getContent` call.

## 3. Documentation

- [ ] 3.1 Add `Documentation/Changelog/3.0/Important-PartnerPageRendersCategories.rst`
  to `academic_partners` and `Important-ProjectPageRendersCategories.rst` to
  `academic_projects`, naming the visible change, and verify both render with
  `checkRstRenderingAll`.
- [ ] 3.2 Check `docs/` for statements about the page templates and update
  them if one describes the category block; state in the pull request when
  nothing needed a change.

## 4. File the issue

- [ ] 4.1 After implementation, file the ACE issue in YouTrack, rename the
  change to `ace-<NNN>-page-template-category-block`, and commit in TYPO3
  Core format, e.g. `[BUGFIX] ACE-<NNN>: Render page template categories`.

## 5. Backport

- [ ] 5.1 Backport: separate change on branch `2` after a backport analysis
  (`docs/workflow/backporting.md`).

## 6. Definition of done

- [ ] 6.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v13, all green.
- [ ] 6.2 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v14, all green.
- [ ] 6.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 6.4 `docs/` and both extensions' `Documentation/` changelog updated in
  the same change.
- [ ] 6.5 Archive the change as the last commit of the pull request.
