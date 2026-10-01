## 1. Tests first

- [x] 1.1 Add a rendering test to the new class `AcademicJobsNewJobFormFlagsTest`
  that the form contains both flag checkboxes, with the alumni label
  "Recommended by alumni" on the default language and "Von Alumni empfohlen"
  on a German site language, and record that it fails on the unchanged
  partial and labels. The class extends the notification mail test case,
  which already has a German site language and posts the form.
- [x] 1.2 Add a form submission test that reuses the form post of
  `AbstractAcademicJobsNotificationMailTestCase`, which takes further values
  by field name for it: submit with both flags unchecked and assert a job row
  with both flags 0 and no mapping error, then submit with flags checked and
  assert 1. Record that the unchecked submission fails once the partial
  renders the checkboxes and the controller is unchanged. Add a test that a
  form returned with a validation error keeps a checked flag. The tests run
  on v13 as well.
- [x] 1.3 Add a rendering test in a class of its own,
  `AcademicJobsNewJobFormAdditionalFieldsTest`, with a fixture partial root
  path that overrides `Job/Forms/AdditionalFields.html`, and assert its
  field appears before the submit button. Record that it fails before the
  slot exists. Add a test that the shipped partial renders nothing, and one
  that a field named outside the `job` argument does not keep the job from
  being created.

## 2. Implementation

- [x] 2.1 Render both flags in `Partials/Job/Properties/Job.html`, add
  `create.job.internationalsWelcome.label` in English and German, and reword
  `create.job.alumniRecommend.label` to the ACE-596 detail wording in
  `locallang.xlf` and `de.locallang.xlf` (source and target on one line
  each), and verify test 1.1 passes.
- [x] 2.2 Map `''` to `'0'` for the two flags in `initializeCreateAction()`,
  and verify test 1.2 passes on v13 and v14.
- [x] 2.3 Add the empty `Job/Forms/AdditionalFields.html` and render it in
  `New.html`, and verify test 1.3 passes and the default form output is
  otherwise unchanged.

## 3. Documentation

- [x] 3.1 Add `Documentation/Changelog/3.0/Important-NewJobFormOffersTheJobFlags.rst`
  to `academic_jobs`, covering the two checkboxes and the reworded alumni
  label, and verify it renders.
- [x] 3.2 Add `Documentation/Changelog/3.0/Feature-NewJobFormAdditionalFieldsPartial.rst`,
  covering the slot partial with the rule that slot fields are not bound to
  unknown job properties. Describe the slot in the templates chapter of the
  extension's `Documentation/`, and in `docs/` next to the other empty
  partials.

## 4. File the issue

- [x] 4.1 File the ACE issue in YouTrack (ACE-789), rename the change to
  `ace-789-jobs-form-flag-fields`, and commit in TYPO3 Core format, as
  `[BUGFIX] ACE-789: Offer job flags in new-job form` and
  `[FEATURE] ACE-789: Add field slot to new-job form`.

## 5. Definition of done

- [ ] 5.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v13, all green.
- [ ] 5.2 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v14, all green. The submission test of 1.2 is also
  green with `-d postgres` on both versions, because it writes.
- [ ] 5.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 5.4 `docs/` and the extension's `Documentation/` changelog updated in the
  same change.
- [ ] 5.5 Archive the change as the last commit of the pull request.
