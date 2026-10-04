## Why

`academic_jobs` (`packages/fgtclb/academic-jobs`) and `academic_study_plan`
(`packages/fgtclb/academic-study-plan`) register their frontend icons in the
backend icon registry and render them with the core icon tag. With the
frontend icon registry of #112 in place, they belong there. No icon of either
extension is used in both the backend and the frontend, so the split is
clean.

## What Changes

- **BREAKING** `academic_jobs`: the seventeen `academic_jobs-*` identifiers
  leave the backend registry and are registered for the frontend only: the
  twelve job property icons of the list and the detail view, the contact
  phone and e-mail icons the contact block renders since
  `ace-809-job-contact-icons` (#111), and `academic_jobs-starttime`,
  `-contactName` and `-contactAdditionalInformation`, which no shipped
  template renders and which are kept as the documented spares of the
  `academic_jobs-<property>` naming the property list follows.
- **BREAKING** `academic_study_plan`: `academic-study-plan-plus`, `-minus` and
  `-close` leave the backend registry and are registered for the frontend
  only.
- The job list, the job detail view, the semester and the module dialog
  partials render these icons from the frontend registry. Markup, size and
  colour stay exactly as they are, so the study plan stylesheet keeps
  switching the accordion glyphs without a change.
- Record, plugin and content element icons stay in the backend registry.
- `academic_bite_jobs` (`packages/fgtclb/academic-bite-jobs`) is not changed:
  its one icon, `bitejobs_list`, is a plugin icon of the backend, and none of
  its templates renders an icon.
- A Breaking changelog entry for each of the two extensions. The two
  unreleased 3.0 entries of `academic_jobs` that place these icons in the
  backend registry (ACE-523, #111) are amended.

TYPO3 v13 and v14 behave the same.

## Non-goals

- Renaming an identifier, which the icon consolidation (#617) does later.
- Redrawing or inlining the job icons. They keep rendering as images, which
  inlining would change in size and colour.
- Moving the study plan glyph switch away from the icon classes. That belongs
  to a later change of the frontend icon markup.
- Removing `tx_academicjobs_domain_model_contact.svg`, which no
  registration names.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `academic-jobs/job-detail`: the property icons of the job list and the job
  detail view are replaced through the frontend icon registration.
- `academic-jobs/job-contact`: the contact icons are replaced through the
  frontend icon registration.
- `academic-study-plan/frontend-markup-contract`: the control icons are
  replaced through the frontend icon registration, and the accordion glyph
  switch keeps working.

## Impact

- `academic-jobs`: `Configuration/Icons.php`, a new
  `Configuration/FrontendIcons.php`, `Partials/Job/Information.html`,
  `Item.html` and `Contact.html`, tests, changelog.
- `academic-study-plan`: `Configuration/Icons.php`, a new
  `Configuration/FrontendIcons.php`, `Partials/StudyPlan/Semester.html` and
  `ModuleDialog.html`, tests, the template chapter of the manual, changelog.
- `docs/architecture/icons.md`.
- A site package that replaces one of these icons moves the registration from
  its `Configuration/Icons.php` to its `Configuration/FrontendIcons.php`, with
  the same entry. A template override of one of the five partials replaces
  the core icon tag by the icon tag of `academic_base`, or it shows the "icon
  not found" placeholder. An override of the semester partial keeps the two
  glyph identifiers, which the stylesheet selects.
- Depends on #111 and #112. 3.0.0, `main` only.
