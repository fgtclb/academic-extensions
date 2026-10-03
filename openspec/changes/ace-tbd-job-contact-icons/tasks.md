## 1. Prove the defect

- [ ] 1.1 Add `detailPluginRendersTheShippedContactIcons` to
  `packages/fgtclb/academic-jobs/Tests/Functional/Plugins/AcademicJobsListAndDetailPluginTest.php`
  (fixture `jobPages`, job 1). Inside the contact block
  (`academic-jobs-contact`) assert a wrapper with
  `data-identifier="academic_jobs-contactPhone"` holding an `<img>` whose `src`
  ends in `Icons/Phone.svg` with `width="16"` and `height="16"`, the same for
  `academic_jobs-contactEmail` and `Icons/Email.svg`, and that the block
  contains no `default-not-found`. Run it on unchanged `main` after
  `composerUpdate` for v13 and for v14, and record that it fails on both
  because the block renders `data-identifier="default-not-found"` twice.
- [ ] 1.2 Add `detailPluginRendersOnlyTheEmailIconForAContactWithoutPhone`
  (fixture `jobPages_contactPhone`, job 2): the e-mail icon is present, no
  `academic_jobs-contactPhone` and no `default-not-found` in the block. Record
  that it fails on `main` on both core versions.
- [ ] 1.3 Add `detailPluginRendersContactIconsAtThePropertyIconSize`: the
  `width` and `height` of both contact `<img>` equal those of the
  `academic_jobs-workLocation` icon of the same response. Record its result on
  `main`: it fails there, because the not-found icon comes from core's
  `SvgSpriteIconProvider` and its `inline` markup is an `<svg>`, not an
  `<img>`.

## 2. Fix the partial

- [ ] 2.1 In `packages/fgtclb/academic-jobs/Resources/Private/Partials/Job/Contact.html`
  render `<core:icon identifier="academic_jobs-contactPhone"/>` and
  `<core:icon identifier="academic_jobs-contactEmail"/>`, without
  `alternativeMarkupIdentifier`. Leave `Configuration/Icons.php` unchanged.
  Verify: tasks 1.1 to 1.3 green on v13 and v14, and the existing contact tests
  (`detailPluginRendersContactInformationOfTheJob`, the two phone link tests,
  `detailPluginOmitsContactBlockForJobWithoutContact`) unchanged and green.

## 3. Prove the replacement recipe

- [ ] 3.1 Create the fixture extension
  `packages/fgtclb/academic-jobs/Tests/Functional/Fixtures/Extensions/test_job_contact_icon/`
  (`tests/job-contact-icon`, requiring `fgtclb/academic-jobs`) by the recipe of
  `docs/testing/fixture-extensions.md`, with an own SVG and a
  `Configuration/Icons.php` that registers it under
  `academic_jobs-contactPhone`. Verify with `composerUpdate` for each core
  version that the package is installed.
- [ ] 3.2 Add `AcademicJobsContactIconReplacementTest` loading that extension:
  the phone row of the contact block renders the fixture's file, the e-mail row
  still `Icons/Email.svg`. Show it can fail by removing the registration from
  the fixture's `Icons.php` (the row falls back to `Icons/Phone.svg`), then
  restore. Green on v13 and v14.
- [ ] 3.3 Add the extension to the table of `docs/testing/fixture-extensions.md`
  and raise its count, measured with the commands on that page.

## 4. Documentation

- [ ] 4.1 `docs/architecture/icons.md`: the `academic-jobs` row of the markup
  table no longer names "core `phone`/`mail`" or inline sites. It lists
  `Job/Contact.html` with the property partials under the default markup. Re-run
  the counting command on that page and correct every number it changes.
- [ ] 4.2 `packages/fgtclb/academic-jobs/Documentation/Changelog/3.0/Important-JobContactBlockShowsItsOwnIcons.rst`
  from `Build/Documentation/Templates/`: before and after markup, the two
  identifiers, that a site which registered `phone` or `mail` for this block
  registers its files under `academic_jobs-contactPhone` and
  `academic_jobs-contactEmail` in `Configuration/Icons.php` to keep them, that
  a stylesheet selecting the old identifier classes or an inlined `<svg>` in the
  block follows, and that an override of `Partials/Job/Contact.html` keeps its
  own output. Verify with `checkRstRenderingAll`.
- [ ] 4.3 Check that no other page of `docs/`, the jobs manual or a
  `README.md` names `phone`/`mail` for this block
  (`grep -rn 'identifier="phone"\|identifier="mail"\|core .phone'`), and fix any
  hit.

## 5. File the issue

- [ ] 5.1 File the ACE issue in YouTrack (Bug, version 3.0.0, subtask of
  ACE-10, relates to the frontend icon umbrella issue), and verify the key with
  a GET request.
- [ ] 5.2 Rename the change to `ace-<NNN>-job-contact-icons`.

## 6. Definition of done

- [ ] 6.1 After `composerUpdate` for TYPO3 v13: `lintPhp`, `cgl -n`, `phpstan`,
  `unit` and `functional -j auto` green.
- [ ] 6.2 After `composerUpdate` for TYPO3 v14: `lintPhp`, `cgl -n`, `phpstan`,
  `unit` and `functional -j auto` green.
- [ ] 6.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 6.4 `docs/` is updated, and `README.md` and `CONTRIBUTING.md` still only
  summarize.
- [ ] 6.5 Commit as `[BUGFIX] ACE-<NNN>: Show the job contact icons` in TYPO3
  Core format with a verified key and no attribution, and archive the change as
  the last commit of the pull request.
