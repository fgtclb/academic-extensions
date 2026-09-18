## 1. Backport analysis

- [x] 1.1 Diff every file the change on `main` touches against this branch and
  record what has to be adapted.

## 2. Tests first

- [x] 2.1 Write `Tests/Functional/Plugins/AcademicJobsListAndDetailPluginTest`
  with its page fixture and the `ListAndDetailConfiguration.typoscript`
  constants: a baseline for both plugins, both flag labels without their value,
  a job with neither flag nor link, an external link as an anchor, a
  `t3://page?uid=` link resolved to the page URL, the German labels and the
  same assertions on the list view.
- [x] 2.2 Record the failures on the unchanged partials, on TYPO3 v12 and v13.

## 3. Implementation

- [x] 3.1 Add `jobs.internationalsWelcome`, `jobs.alumniRecommend` and
  `jobs.linkText` to `locallang.xlf` and `de.locallang.xlf`, tab-indented as
  the rest of both files.
- [x] 3.2 Render the flag items without their value and the link item as a
  typolink in `Partials/Job/Information.html` and `Partials/Job/Item.html`.

## 4. Documentation

- [x] 4.1 Add
  `Documentation/Changelog/2.4/Important-JobViewsLabelFlagsAndRenderTheLink.rst`.
- [x] 4.2 List the label keys in `Documentation/Templates/Override/Index.rst`.

## 5. Definition of done

- [x] 5.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v12, all green.
- [x] 5.2 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v13, all green.
- [x] 5.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 5.4 Archive the change as the last commit of the pull request.
