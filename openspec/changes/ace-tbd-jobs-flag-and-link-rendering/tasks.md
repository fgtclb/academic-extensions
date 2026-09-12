## 1. Tests first

- [ ] 1.1 Extend `AcademicJobsListAndDetailPluginTest` with a job that has
  both flags set and an external link; on its detail page assert both flag
  labels, no value `1` in their rows, and the row label "Link" followed by an
  anchor to the URL with the link text. Record that the flag and anchor
  assertions fail on the unchanged partial.
- [ ] 1.2 Add a job whose link is `t3://page?uid=1`; assert the anchor carries
  the page URL. Record the failure on the unchanged partial.
- [ ] 1.3 Add a German-language detail rendering of the job of 1.1 and assert
  the German labels.
- [ ] 1.4 Render the job list with the jobs of 1.1 and 1.2 and assert, for
  each entry, both flag labels without the value `1` and an anchor with the
  link text and the resolved URL. Record that it fails on the unchanged
  `Partials/Job/Item.html`.

## 2. Implementation

- [ ] 2.1 Add `jobs.internationalsWelcome` ("International applicants
  welcome" / "Internationale Bewerbungen willkommen"),
  `jobs.alumniRecommend` ("Recommended by alumni" / "Von Alumni empfohlen")
  and `jobs.linkText` ("To the job posting" / "Zur Stellenausschreibung") to
  `locallang.xlf` and `de.locallang.xlf`, one line per `source`/`target`,
  two-space indentation; verify test 1.3 passes.
- [ ] 2.2 Render the flag items without their value and the link item as a
  typolink in `Partials/Job/Information.html`; verify tests 1.1 and 1.2 pass
  on v13 and v14.
- [ ] 2.3 Apply the same flag and link branches to `Partials/Job/Item.html`;
  verify test 1.4 passes on v13 and v14.

## 3. Documentation

- [ ] 3.1 Add `Documentation/Changelog/3.0/Important-JobDetailLabelsFlagsAndLinksTheJob.rst`
  to `academic_jobs`, naming the new label keys, the link text change and
  both affected partials; verify it renders.
- [ ] 3.2 List the label keys in the extension's `Documentation/` where the
  templates are described, and check `docs/` for statements about the job
  detail and list partials; state in the pull request when nothing needed a
  change.

## 4. File the issue

- [ ] 4.1 Verify ACE-596 in YouTrack, extend its scope by the job link
  rendering, and close ACE-371 as its duplicate.
- [ ] 4.2 Rename the change to `ace-596-jobs-flag-and-link-rendering` and
  commit in TYPO3 Core format, e.g.
  `[BUGFIX] ACE-596: Label job flags and link job URL`.

## 5. Backport

- [ ] 5.1 Backport: separate change on branch `2` after a backport analysis
  (`docs/workflow/backporting.md`), covering both partials and adapting the
  labels to the tab-indented XLF files of branch `2`; not a cherry-pick.

## 6. Definition of done

- [ ] 6.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v13, all green.
- [ ] 6.2 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v14, all green.
- [ ] 6.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 6.4 `docs/` and the extension's `Documentation/` changelog updated in the
  same change.
- [ ] 6.5 Archive the change as the last commit of the pull request.
