## 1. Tests first

- [ ] 1.1 Add a rendering assertion to `AcademicJobsNewJobFormPluginTest`
  that the form contains both flag checkboxes, with the alumni label
  "Recommended by alumni" on the default language and "Von Alumni empfohlen"
  on a German site language, and record that it fails on the unchanged
  partial and labels.
- [ ] 1.2 Add a form submission test that reuses the form extraction of
  `AcademicJobsNewJobFormUploadTest` without an uploaded file: submit with
  both flags unchecked and assert a job row with both flags 0 and no mapping
  error; submit with one flag checked and assert 1. Add the rendered flag
  fields to the submission by hand first and record that the unchecked
  submission fails on the unchanged controller. Keep the test on v13 as well;
  if it has to be excluded there, state why in the class docblock.
- [ ] 1.3 Add a rendering test with a fixture template path that overrides
  `Job/Forms/AdditionalFields.html`; assert its field appears before the
  submit button. Record that it fails before the slot exists.

## 2. Implementation

- [ ] 2.1 Render both flags in `Partials/Job/Properties/Job.html`, add
  `create.job.internationalsWelcome.label` in English and German, and reword
  `create.job.alumniRecommend.label` to the ACE-596 detail wording in
  `locallang.xlf` and `de.locallang.xlf` (source and target on one line
  each); verify test 1.1 passes.
- [ ] 2.2 Map `''` to `'0'` for the two flags in `initializeCreateAction()`;
  verify test 1.2 passes on v13 and v14.
- [ ] 2.3 Add the empty `Job/Forms/AdditionalFields.html` and render it in
  `New.html`; verify test 1.3 passes and the default form output is
  otherwise unchanged.

## 3. Documentation

- [ ] 3.1 Add `Documentation/Changelog/3.0/Feature-NewJobFormFlagsAndAdditionalFields.rst`
  to `academic_jobs`, covering the two checkboxes, the reworded alumni label
  and the slot partial with the rule that slot fields are not bound to
  unknown job properties; verify it renders.
- [ ] 3.2 Describe the slot in the templates chapter of the extension's
  `Documentation/`, and add it to `docs/` where the form extension points of
  `academic_jobs` are described.

## 4. File the issue

- [ ] 4.1 After implementation, file the ACE issue in YouTrack, rename the
  change to `ace-<NNN>-jobs-form-flag-fields`, and commit in TYPO3 Core
  format, e.g. `[FEATURE] ACE-<NNN>: Offer job flags in new-job form`.

## 5. Definition of done

- [ ] 5.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v13, all green.
- [ ] 5.2 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v14, all green; the submission test of 1.2 also
  green with `-d postgres` on both versions, because it writes.
- [ ] 5.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 5.4 `docs/` and the extension's `Documentation/` changelog updated in the
  same change.
- [ ] 5.5 Archive the change as the last commit of the pull request.
