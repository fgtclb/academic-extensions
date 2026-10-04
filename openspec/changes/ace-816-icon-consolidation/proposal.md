## Why

The icons of the academic extensions come from four sets and hand-made files,
in fixed colours and under names that follow no scheme
(`persons_icon`, `academic-persons-envelope`, `academic_jobs-starttime`). The
same concept is drawn up to three times and replaced up to three times. The
frontend icon registry of ACE-808 gives frontend icons a home of their own, so
names, sets and registries are settled now, before 3.0 is released.

## What Changes

- One naming scheme, `tx-<extension key without underscores>-<group>-<name>`,
  with files `Resources/Public/Icons/<group>/<name>.svg`.
- **The group decides the registry**: `action`, `state` and `info` icons are
  frontend icons in `Configuration/FrontendIcons.php` only, `record`, `plugin`
  and `doktype` icons backend icons in `Configuration/Icons.php` only. Category
  type and group identifiers stay, only their files change.
- `academic_base` (`academic-base`) ships a shared set of 38 Font Awesome Free
  solid icons with its licence notice, and the checks of the rules.
- **BREAKING**: every extension renames its icons without aliases and draws
  Font Awesome Free solid icons in `currentColor`: `academic_persons`
  (`academic-persons`), `academic_persons_edit` (`academic-persons-edit`),
  `academic_jobs` (`academic-jobs`), `academic_bite_jobs`
  (`academic-bite-jobs`), `academic_contacts4pages`
  (`academic-contact4pages`), `academic_partners` (`academic-partners`),
  `academic_programs` (`academic-programs`), `academic_projects`
  (`academic-projects`) and `academic_study_plan` (`academic-study-plan`).
  Persons, persons_edit and study_plan render the shared set directly. Jobs
  keeps one identifier per job property, `tx-academicjobs-info-<property>`.
- **BREAKING**: every frontend icon renders inline in `currentColor`, the job
  icons were 16 px images.
- Every extension checks its icons against the rules, and the development seed
  gets a page listing every icon of both registries (ACE-594).
- One Breaking changelog entry about icons per extension, with old and new
  identifier and registry.

TYPO3 v13 and v14 behave the same. `category_types` (`typo3-category-types`)
and `academic_persons_sync` (`academic-persons-sync`) ship no icon change.

## Non-goals

- A frontend icon API for scripts (ACE-595, a pull request of its own).
- Aliases or deprecated identifiers for the 2.x names.
- Reporting removed identifiers in site packages through the upgrade check.
- `Extension.svg` and `BackendLayout.png` of every extension.
- Branch `2`.

## Capabilities

### New Capabilities

- `academic-base/shared-icon-set`: the shared icon set of `academic_base`,
  how it renders, how a site package replaces one of its icons, and its
  licence notice.

### Modified Capabilities

- `academic-persons/public-profile-icons`: the contact and fold-out icons of
  the public profile are icons of the shared set.
- `academic-persons-edit/profile-editing-icons`: the controls of the profile
  editor are icons of the shared set.
- `academic-jobs/job-detail`: the property icons are renamed and render inline
  in `currentColor`.
- `academic-jobs/job-contact`: the contact icons are renamed and render
  inline.
- `academic-partners/partner-page`: the category type icons draw new files, and
  page type, content element and records carry icons of their own.
- `academic-projects/project-page`: the category type icons draw new files.
- `academic-study-plan/frontend-markup-contract`: the controls are icons of the
  shared set, and the stylesheet selects their new classes.

## Impact

- Identifiers, files and `icon-<identifier>` classes of every extension above,
  migrated by a site package per the Breaking entries.
- `packages-dev/testing-helper`: checks per registry and for the icon files.
- `packages-dev/dev-site`: the icon overview page and the seed manifests.
- `docs/architecture/icons.md`, `docs/testing/testing-helper.md` and the
  manual of every extension.
