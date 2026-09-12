## 1. Shared renderer in category_types

- [ ] 1.1 Add the `final readonly` `PageCategorySummaryRenderer` and the
      `PageCategorySummary.html` backend template, rendering type title,
      icon, categories, the hidden marker and the "not set" note; verify with
      a functional test in `typo3-category-types/Tests/Functional/` that
      renders the summary for a fixture page with two categories and a type
      without any.
- [ ] 1.2 Add a functional test for a template override registered as
      `templates.fgtclb/category-types.*` in page TSconfig; show it fails
      when the renderer creates the view without the package name.

## 2. Listeners per extension

- [ ] 2.1 Add the `ModifyPageLayoutContentEvent` listener to
      `academic_programs` (doktype 20, group `programs`) with TYPO3's
      `#[AsEventListener]`; a functional test dispatches the event for a
      doktype-20 page and asserts both category titles and the translated
      type labels in the header content, and asserts nothing for a
      standard page. Remove the attribute and watch the test go red.
- [ ] 2.2 Same for `academic_projects` (doktype 30, group `projects`) and
      `academic_partners` (doktype 40, group `partners`), each with its own
      functional test, each shown to fail without its listener.
- [ ] 2.3 Add a test with a fixture type added to the `programs` group
      through YAML: its registered title is rendered. Restore the old
      `sys_category.academic_programs.{type}` key in the template and watch
      it fail.
- [ ] 2.4 Remove the `templates.typo3/cms-backend.academic-*` line from the
      three `Configuration/page.tsconfig` files and delete the three
      `Resources/Private/Backend/Partials/PageLayout/Doktype*.html`
      partials; verify no reference remains (grep).
- [ ] 2.5 Update the class comment of the be.category ViewHelper test that
      names the removed partials as its callers.

## 3. Documentation

- [ ] 3.1 Describe the page module summary and its override key in `docs/`
      (architecture section on category types) and link it from the
      section `Index.md`.
- [ ] 3.2 Add
      `Documentation/Changelog/3.0/Important-PageModuleCategorySummary.rst`
      to `academic_programs`, `academic_partners` and `academic_projects`
      (the removed override key, the new one), from the templates in
      `Build/Documentation/Templates/`; check the reST over/underline lengths.

## 4. File the issue

- [ ] 4.1 After implementation, file one ACE issue per commit in YouTrack
      (renderer, programs, projects, partners), rename the change after the
      renderer's key to `ace-<NNN>-page-module-category-summary`, and commit
      as four stacked commits in TYPO3 Core format
      `[BUGFIX] ACE-<NNN>: <subject>`: the `category_types` renderer (group
      1) first, then one commit per extension (group 2 and its changelog
      entry from 3.2), each green on its own.

## 5. Backport

- [ ] 5.1 Backport: separate change on branch 2 after a backport analysis
      (`docs/workflow/backporting.md`); the event exists on v12, the view
      creation differs there.

## 6. Definition of done

- [ ] 6.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
      `functional` green for TYPO3 v13; the same after its own
      `composerUpdate` for v14.
- [ ] 6.2 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 6.3 `docs/` and the three extensions' `Documentation/` changelog
      updated; `README.md` and `CONTRIBUTING.md` only link.
- [ ] 6.4 Archive the change as the last commit of the pull request.
