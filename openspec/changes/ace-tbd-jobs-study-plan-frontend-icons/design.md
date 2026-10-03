## Context

See `proposal.md` for the motivation. On `main` (428cf1a32), re-read for this
change (report `.agent/reports/frontend-icons/05-inventory-jobs-studyplan.md`
holds the full inventory):

- `academic-jobs/Configuration/Icons.php` registers 19 identifiers: the record
  icon `tx_academicjobs_domain_model_job` (`CurrentColorSvgIconProvider`), the
  plugin icon `academic_jobs_icon` and 17 `academic_jobs-*` with the core
  `SvgIconProvider`. `Partials/Job/Information.html:28` and `Item.html:48`
  render `<core:icon identifier="academic_jobs-{item}"/>` in a loop over twelve
  properties. Since #111, `Partials/Job/Contact.html` renders
  `academic_jobs-contactPhone` and `-contactEmail` the same way (default
  markup, a 16 px `<img>`). `academic_jobs-starttime`, `-contactName` and
  `-contactAdditionalInformation` are rendered by no template. All 17 have been
  registered since 2.1.0 (09b001569), and the loop has been the same since
  then. The three partials declare `xmlns:core`.
- `academic-study-plan/Configuration/Icons.php` registers the content element
  icon `academic-study-plan`, the three record icons and the controls
  `academic-study-plan-plus`, `-minus` and `-close` (`SvgIconProvider`).
  `Partials/StudyPlan/Semester.html:31-32` and `ModuleDialog.html:32` render
  them with `alternativeMarkupIdentifier="inline"` and declare no `core`
  namespace (it is global).
- `academic-study-plan.scss:90,95,99` switches the glyphs through
  `.icon-academic-study-plan-minus` and `-plus`, and `:154` hides `.icon` in
  the header from 768 px on. These are the wrapper classes of core's `Icon`,
  which the `academic_base` icon tag of #112 writes byte for byte the same.
- No `academic_jobs-*` or study plan control identifier is used in the backend
  (no TCA, TSconfig, PHP or backend template), and no backend identifier of the
  two extensions is used in a frontend template.
- `academic-bite-jobs` registers `bitejobs_list`, used by
  `Configuration/TCA/Overrides/tt_content.php:16` and
  `Configuration/TSconfig/List/page.tsconfig:15` only. A grep for `icon` over
  its `Resources/Private`, `Classes` and `Configuration` finds nothing else.
- Tests: `AcademicStudyPlanContentElementTest::contentElementRendersOnlyResolvableIcons()`
  asserts no `default-not-found` and the three `data-identifier`s, and
  `classInventoryOf()` drops every class attribute containing `icon`. Both
  hold for identical markup. #111 adds the contact icon tests and the fixture
  extension `test_job_contact_icon`, whose `Icons.php` replaces
  `academic_jobs-contactPhone`.
- Unreleased entries naming these icons:
  `academic-jobs/.../3.0/Important-RecordIconsFollowTheColourScheme.rst:31-32`
  ("The seventeen `academic_jobs-*` field icons ... keep the core provider and
  are unchanged") and #111's `Important-JobContactBlockShowsItsOwnIcons.rst`,
  which tells a site to register its files in `Configuration/Icons.php`. The
  study plan's `Important-RecordIconsFollowTheColourScheme.rst` speaks of the
  record icons only and stays correct.

## Goals / Non-Goals

**Goals:**

- Every frontend icon of the two extensions in `FrontendIcons.php` only, every
  backend icon in `Icons.php` only, with the rendered page unchanged byte for
  byte on v13 and v14.
- The documented replacement recipe proven by a test per extension, including
  that the old recipe no longer reaches the page.

**Non-Goals:**

- Providers, artwork and identifiers (#617). `academic_bite_jobs`.

## Decisions

### Split per identifier, nothing in both files

| Identifier                                                  | File after the change |
|-------------------------------------------------------------|-----------------------|
| `tx_academicjobs_domain_model_job`, `academic_jobs_icon`    | `Icons.php`           |
| the 17 `academic_jobs-*`                                    | `FrontendIcons.php`   |
| `academic-study-plan`, `-category`, `-semester`, `-module`  | `Icons.php`           |
| `academic-study-plan-plus`, `-minus`, `-close`              | `FrontendIcons.php`   |

Provider and source of each entry are copied unchanged, so the markup stays.
No identifier is used in both contexts, so none is registered twice.

### The three unrendered jobs icons are kept, for the frontend

`academic_jobs-starttime`, `-contactName` and `-contactAdditionalInformation`
move to `FrontendIcons.php` like the rest. The property loop builds its
identifier as `academic_jobs-{item}`, so an override that adds `starttime` to
the loop, the most likely extension of it, gets its icon from the
registration without naming it. That is the role of the three since 2.1.0.
They cost three entries of a cached array, and their files ship anyway
(`Calendar.svg` is shared with two used icons).

Rejected: removing them. It is Breaking just the same (released identifiers,
and an override on `core:icon` loses them either way), it saves nothing
measurable, and an override that extends the loop would have to register an
icon under the extension's prefix itself. Rejected: keeping them in
`Icons.php`. Nothing in the backend uses them, and the strict icon tag of #112
would never reach them there.

The move is Breaking for all 17, in one entry: a site that renders one of
them through `core:icon`, or replaces one in its `Icons.php`, needs to act.
The two contact identifiers have been rendered only since #111, also
unreleased, so the entry treats them like the rest.

### Templates switch the tag only

`core:icon` becomes `ab:icon` with the same arguments. The jobs partials
replace `xmlns:core` by
`xmlns:ab="http://typo3.org/ns/FGTCLB/AcademicBase/ViewHelpers"`, the two
study plan partials add that declaration. Neither extension's templates
declare the `academic_base` namespace today, so there is no existing prefix
to keep.

### The study plan stylesheet stays as it is

The SCSS keeps selecting `icon` and `icon-academic-study-plan-*`, and the
compiled CSS is not rebuilt. The icon tag writes the same wrapper, so the
switch works on unchanged markup, and an override of `Semester.html` that
renders the two identifiers keeps working too. The template chapter of the
manual says so, which it does not today.

Rejected: moving the glyph switch to wrapper elements of the extension, as the
analysis recommended. It changes the markup in a round whose premise is that
the markup does not change, and it becomes necessary only with a leaner icon
markup, which is a Breaking change of its own later.

### Tests

- Per extension, `Tests/Functional/Imaging/FrontendIconsTest`: each moved
  identifier is in the frontend registry with its provider and source, and
  the core `IconRegistry` does not know it. It uses the frontend registry
  trait of `packages-dev/testing-helper` from #112. Red on the state before
  the move.
- Jobs: #111's fixture extension becomes `test_job_icons` (`tests/job-icons`)
  and its class `AcademicJobsIconReplacementTest`, because it now proves the
  recipe for every jobs icon. Its `FrontendIcons.php` replaces
  `academic_jobs-contactPhone` and `academic_jobs-companyName`, its `Icons.php`
  registers a second file under `academic_jobs-contactEmail` and
  `academic_jobs-workLocation`. The test asserts the first two render the fixture
  files in the list and the detail view, the last two the shipped ones. Red
  while the partials still use `core:icon`. Rejected: a second fixture for the
  property icons, two packages proving one recipe.
- Study plan: the first fixture extension of the package,
  `test_study_plan_icons` (`tests/study-plan-icons`), with
  `academic-study-plan-close` in its `FrontendIcons.php` and
  `academic-study-plan-plus` in its `Icons.php`, and a class
  `AcademicStudyPlanIconReplacementTest` asserting the dialog's fixture glyph
  and the shipped plus glyph. Red while the partials use `core:icon`.
- `contentElementRendersOnlyResolvableIcons()` keeps both assertions, and its
  comment names the frontend registry. It is shown to guard the move by
  leaving `academic-study-plan-close` out of `FrontendIcons.php`. A new
  `contentElementGivesTheGlyphsTheClassesTheStylesheetSelects()` asserts the
  `icon` and `icon-academic-study-plan-plus|minus` classes inside each
  semester header. The jobs list and detail tests gain the two-assertion pair
  for the twelve property icons.
- `classInventoryOf()` is unchanged, only its docblock names the icon tag of
  `academic_base`. The comment of `Tests/JavaScript/academic-study-plan.test.ts`
  does the same.

### Changelog

`Breaking-JobIconsAreFrontendIcons.rst` (`academic_jobs`) and
`Breaking-StudyPlanControlIconsAreFrontendIcons.rst` (`academic_study_plan`),
from the drafts in report 05 with the decisions above filled in. The jobs
`Important-RecordIconsFollowTheColourScheme.rst` and #111's
`Important-JobContactBlockShowsItsOwnIcons.rst` are amended to point at
`FrontendIcons.php` and the Breaking entry.

`academic_bite_jobs` gets no entry: nothing changes for it, and an entry would
announce a non-change.

## Risks / Trade-offs

- [A site replaces a job or control icon in its `Icons.php` and silently gets
  the shipped artwork back] → The Breaking entries name every identifier and
  the move of the registration, and the upgrade path of the migration guide
  (#10) picks them up.
- [An override still on `core:icon` shows the not-found placeholder] → Named
  in both entries. #117 guards the shipped templates, not overrides.
- [The markup is not byte identical after all] → Task 1.2 compares the
  rendered icons before and after on both cores. A difference stops the change.
- [#111 is merged with a different fixture or test name] → Task 1.1 re-reads
  #111 as merged and adapts the rename.
