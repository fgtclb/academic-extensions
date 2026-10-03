## 1. Verify the premises

- [ ] 1.1 Confirm that #111 (`ace-tbd-job-contact-icons`) and #112
  (`ace-tbd-frontend-icon-registry`) are merged on `main`: `Contact.html`
  renders `academic_jobs-contactPhone` and `-contactEmail`, the fixture
  extension `test_job_contact_icon` and `AcademicJobsContactIconReplacementTest`
  exist, and `academic_base` ships the frontend registry, the icon tag and the
  testing-helper trait. If a name differs, adapt tasks 3 and 4 to it.
- [ ] 1.2 Before any change, after `composerUpdate` for v13 and for v14, save
  the rendered markup of every icon wrapper of the job list, the job detail
  view and the study plan page (from the existing functional fixtures) to
  `.agent/tmp/`. Task 2.5 compares against it. If the icon tag of #112 does
  not produce the same bytes for `SvgIconProvider` in default and `inline`
  markup, stop and update this change.

## 2. Move the registrations and switch the templates

- [ ] 2.1 Write `packages/fgtclb/academic-jobs/Configuration/FrontendIcons.php`
  with the 17 `academic_jobs-*` entries, provider and source unchanged, with a
  comment on the three spares and the `academic_jobs-<property>` naming.
  Remove them from `Configuration/Icons.php`, which keeps the record and the
  plugin icon.
- [ ] 2.2 Write `packages/fgtclb/academic-study-plan/Configuration/FrontendIcons.php`
  with `academic-study-plan-plus`, `-minus` and `-close`, provider and source
  unchanged, and move the "frontend controls" comment there. Remove them from
  `Configuration/Icons.php`.
- [ ] 2.3 In `academic-jobs/Resources/Private/Partials/Job/Information.html`,
  `Item.html` and `Contact.html` replace `xmlns:core` by
  `xmlns:ab="http://typo3.org/ns/FGTCLB/AcademicBase/ViewHelpers"` and
  `<core:icon` by `<ab:icon`, arguments unchanged. Verify with
  `grep -rn "core:" packages/fgtclb/academic-jobs/Resources/Private` (no hit).
- [ ] 2.4 In `academic-study-plan/Resources/Private/Frontend/Default/Partials/StudyPlan/Semester.html`
  and `ModuleDialog.html` add the same `xmlns:ab` declaration and replace
  `<core:icon` by `<ab:icon`, arguments unchanged. Verify with the same grep
  over `packages/fgtclb/academic-study-plan/Resources/Private`.
- [ ] 2.5 Render the pages of task 1.2 again on both cores and diff the icon
  wrappers against the saved markup: no difference. `checkJsBuildClean`
  green, so the compiled study plan CSS is unchanged.

## 3. Tests for academic_jobs

- [ ] 3.1 `packages/fgtclb/academic-jobs/Tests/Functional/Imaging/FrontendIconsTest.php`:
  each of the 17 identifiers is in the frontend registry with its provider and
  source, and the core `IconRegistry` does not know it. The record and plugin
  icon stay asserted by `RecordIconsTest` and the wizard tests. Show it fails
  on the state before task 2.1, then green on v13 and v14.
- [ ] 3.2 Rename #111's fixture extension to `test_job_icons`
  (`tests/job-icons`) and its class to `AcademicJobsIconReplacementTest`. Move
  its registration of `academic_jobs-contactPhone` to `FrontendIcons.php` and
  add `academic_jobs-companyName` there, with an SVG of its own. Add an
  `Icons.php` with a second SVG under `academic_jobs-contactEmail` and
  `academic_jobs-workLocation`. Assert in the list and the detail view: phone
  and company name render the fixture's frontend file, e-mail and work
  location the shipped `Email.svg` and `Location.svg`. Show it fails with the partials of
  task 2.3 reverted (both pairs swap), then green on v13 and v14.
- [ ] 3.3 In `AcademicJobsListAndDetailPluginTest` add the two-assertion pair
  for the property icons of the list and the detail view (no
  `default-not-found`, a `data-identifier="academic_jobs-<property>"` for each
  of the twelve properties), on a new fixture job that carries all twelve
  (`jobPages.csv` sets only four of them). Show it fails by leaving
  `academic_jobs-sector` out of `FrontendIcons.php`, then green on v13 and
  v14. #111's contact icon tests
  stay unchanged and green.

## 4. Tests for academic_study_plan

- [ ] 4.1 `packages/fgtclb/academic-study-plan/Tests/Functional/Imaging/FrontendIconsTest.php`:
  the three controls are in the frontend registry with provider and source and
  unknown to the core `IconRegistry`. Show it fails before task 2.2, then green
  on v13 and v14.
- [ ] 4.2 Create the fixture extension `test_study_plan_icons`
  (`tests/study-plan-icons`, requiring `fgtclb/academic-study-plan`) by the
  recipe of `docs/testing/fixture-extensions.md`, with
  `academic-study-plan-close` in `FrontendIcons.php` and
  `academic-study-plan-plus` in `Icons.php`, each with an SVG of its own.
  `AcademicStudyPlanIconReplacementTest` asserts the dialog renders the
  fixture's close glyph and the semester header the shipped `plus.svg`. Show
  it fails with the partials of task 2.4 reverted, then green on v13 and v14.
- [ ] 4.3 `contentElementRendersOnlyResolvableIcons()`: rewrite the comment for
  the frontend registry and keep both assertions. Show it fails by leaving
  `academic-study-plan-close` out of `FrontendIcons.php`, then restore.
- [ ] 4.4 Add `contentElementGivesTheGlyphsTheClassesTheStylesheetSelects()`:
  inside every `data-study-plan-semester-header` one element carries `icon`
  and `icon-academic-study-plan-plus`, one `icon` and
  `icon-academic-study-plan-minus`. Show it fails by rendering `-close` in
  place of `-plus` in `Semester.html`, then restore. Green on v13 and v14.
- [ ] 4.5 Reword the docblock of `classInventoryOf()` and the comment of
  `Tests/JavaScript/academic-study-plan.test.ts` from `core:icon` to the icon
  tag of `academic_base`. `testJs` green.

## 5. Documentation

- [ ] 5.1 `academic-jobs/Documentation/Changelog/3.0/Breaking-JobIconsAreFrontendIcons.rst`
  from `Build/Documentation/Templates/Changelog-Breaking.rst`: the 17
  identifiers, the three spares and why, the backend icons that stay, the
  migration from `Icons.php` to `FrontendIcons.php` of the site package, the
  namespace and tag for overridden partials.
- [ ] 5.2 `academic-study-plan/Documentation/Changelog/3.0/Breaking-StudyPlanControlIconsAreFrontendIcons.rst`:
  the three identifiers, the backend icons that stay, the migration, and that
  an override of `Semester.html` keeps the two identifiers for the glyph
  switch.
- [ ] 5.3 Amend `academic-jobs/.../3.0/Important-RecordIconsFollowTheColourScheme.rst:31-32`
  and #111's `Important-JobContactBlockShowsItsOwnIcons.rst` to name
  `Configuration/FrontendIcons.php` and refer to the Breaking entry.
- [ ] 5.4 Jobs manual `Documentation/Templates/Override/Index.rst`: a section
  on replacing the icon of a job property (`academic_jobs-<property>`, the
  site package's `FrontendIcons.php`, the three spares). Study plan manual
  `Documentation/Templates/Index.rst`: the glyph identifiers an override of
  `Semester.html` keeps, and the namespace of the icon tag.
- [ ] 5.5 `docs/architecture/icons.md`: the jobs and study plan rows of every
  table the page carries after #112, the sentence on the "seventeen
  `academic_jobs-*` icons", the count of `Icons.php` registrations. Re-run the
  commands on the page and take their numbers.
- [ ] 5.6 `docs/testing/fixture-extensions.md`: the renamed jobs fixture, the
  new study plan fixture, and the counts (the study plan becomes the eleventh
  package with fixtures), measured with the commands on that page.
- [ ] 5.7 `checkRstRenderingAll` and `lintMarkdown -n` green.

## 6. File the issue

- [ ] 6.1 File the ACE issue (Task, version 3.0.0, subtask of ACE-10, relates
  to the frontend icon umbrella issue), verify the key with a GET request, and
  rename the change to `ace-<NNN>-jobs-study-plan-frontend-icons`.

## 7. Definition of done

- [ ] 7.1 After `composerUpdate` for TYPO3 v13: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional -j auto` green.
- [ ] 7.2 After `composerUpdate` for TYPO3 v14: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional -j auto` green.
- [ ] 7.3 `checkJsBuildClean`, `testJs`, `lintMarkdown -n` and
  `checkRstRenderingAll` green.
- [ ] 7.4 `docs/` is updated, and `README.md` and `CONTRIBUTING.md` still only
  summarize.
- [ ] 7.5 Commit as `[!!!][TASK] ACE-<NNN>: Move jobs and study plan icons`
  in TYPO3 Core format, with a verified key and no attribution, and archive the
  change as the last commit of the pull request.
