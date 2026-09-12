## 1. Tests first

- [ ] 1.1 Add a functional test next to
  `Tests/Functional/Plugins/AcademicJobsNewJobFormPluginTest.php` that
  configures the `mbox` mail transport, submits the new-job form and asserts
  one mail with a plain-text and an HTML part, both containing the job title,
  the backend edit link and the English default message. Run it against the
  unchanged code and record that it fails: today the mail is a single
  plain-text part with the fixed sentence and without the title.
- [ ] 1.2 Add cases for a configured `email.template` text, for a fixture mail
  template path with a higher key carrying its own `JobCreated`, for
  `email.templateName` pointing at a fixture template, and for the German
  default message on a German site language. Record that each fails against
  the unchanged code.

## 2. Implementation

- [ ] 2.1 Add `Resources/Private/Templates/Email/JobCreated.html` (and the
  plain-text variant if the layout needs one) on the core `SystemEmail`
  layout, plus the label `email.jobCreated.message` in `locallang.xlf` and
  `de.locallang.xlf`, source and target on one line each.
- [ ] 2.2 Register the template path in `ext_localconf.php` under key `200`;
  verify with the fixture override case of 1.2 that a higher key wins.
- [ ] 2.3 Add `email.templateName` to `settings.definitions.yaml`, the
  constants and the setup mapping, and set the default of `email.template` to
  `''` in both places; extend the site set delivery probe so it pins both
  settings, and verify it on v13 and v14.
- [ ] 2.4 Rewrite `sendEmail()` onto `FluidEmail` with the template name, the
  request and the variables `job`, `url`, `settings` and `emailText`; verify
  that all tests of group 1 pass on v13 and v14.

## 3. Documentation

- [ ] 3.1 Document the four `email.*` settings and the template override in
  `packages/fgtclb/academic-jobs/Documentation/Configuration/General/`.
- [ ] 3.2 Add `Documentation/Changelog/3.0/Feature-NewJobMailUsesAFluidTemplate.rst`
  from `Build/Documentation/Templates/Changelog-Feature.rst`, quoting the new
  default message and the empty `email.template` default; verify the `3.0`
  index picks it up.
- [ ] 3.3 Add the mbox transport technique to
  `docs/testing/functional-tests.md` unless a mail assertion is documented
  there already.

## 4. File the issue

- [ ] 4.1 Verify that ACE-370 is still open. If it is, rename the change to
  `ace-370-jobs-fluid-notification-mail`; otherwise file a new ACE issue in
  YouTrack and rename the change to `ace-<NNN>-jobs-fluid-notification-mail`.
- [ ] 4.2 Commit in TYPO3 Core format, for example
  `[FEATURE] ACE-370: Render the job mail with Fluid`, subject at most 52
  characters, body wrapped at 72.

## 5. Definition of done

- [ ] 5.1 `Build/Scripts/runTests.sh -t 13 -s composerUpdate`, then
  `lintPhp`, `cgl -n`, `phpstan`, `unit` and `functional` with `-t 13` green.
- [ ] 5.2 The same for `-t 14` after its own `composerUpdate`.
- [ ] 5.3 `functional -d postgres` green for v13 and v14, because the form
  writes a job record.
- [ ] 5.4 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 5.5 `docs/` and the `Documentation/` changelog entry are part of the
  change; `README.md` and `CONTRIBUTING.md` still only summarize and link.
- [ ] 5.6 Archive the change as the last commit of the pull request and
  verify the delta spec landed in `openspec/specs/`.
