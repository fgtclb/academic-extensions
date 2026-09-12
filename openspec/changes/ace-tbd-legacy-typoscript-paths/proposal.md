## Why

Before 3.0, four extensions offered one static TypoScript template each, at a
path that 3.0 no longer fills. Installations store that path in
`sys_template.include_static_file`, and projects `@import` its
`setup.typoscript` by file. On `main` these folders hold no TypoScript, so the
stored include and the import deliver nothing, without an error, and the
plugins render unconfigured. TYPO3 also drops an unregistered value from the
template record the next time an integrator saves it. The other five academic
extensions keep their 2.x path working already.

## What Changes

- Each 2.x path delivers exactly what the extension's "All components" static
  template delivers today, both for a stored template record and for an
  `@import` of its `setup.typoscript` and `constants.typoscript`:
  - academic_bite_jobs (`packages/fgtclb/academic-bite-jobs`):
    `EXT:academic_bite_jobs/Configuration/TypoScript`
  - academic_contacts4pages (`packages/fgtclb/academic-contact4pages`):
    `EXT:academic_contacts4pages/Configuration/TypoScript/`
  - academic_persons_edit (`packages/fgtclb/academic-persons-edit`):
    `EXT:academic_persons_edit/Configuration/TypoScript`
  - academic_study_plan (`packages/fgtclb/academic-study-plan`):
    `EXT:academic_study_plan/Configuration/TypoScript/Default`
- Each path is selectable again as a static template, labelled as deprecated,
  so saving a template record keeps it.
- The paths are deprecated and removed in 4.0.
- The behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-bite-jobs/legacy-static-template-path`: the 2.x static template
  path of academic_bite_jobs.
- `academic-contact4pages/legacy-static-template-path`: the same for
  academic_contacts4pages.
- `academic-persons-edit/legacy-static-template-path`: the same for
  academic_persons_edit.
- `academic-study-plan/legacy-static-template-path`: the same for
  academic_study_plan.

### Modified Capabilities

None.

## Impact

- Two TypoScript files per 2.x folder, plus one `include_static_file.txt` for
  academic_contacts4pages, and one static template registration per extension
  in `Configuration/TCA/Overrides/sys_template.php`.
- Sites that use the site set and also import the 2.x file now get the double
  parse the documentation already warns about.
- No database, PHP or dependency change.

## Non-goals

- Detecting stored 2.x values or dead imports (candidate `cross-cutting-09`).
- A guard against a site using a set and a static template together.
- Restoring 2.x TypoScript that 3.0 removed on purpose, beyond what "All
  components" delivers.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`cross-cutting-07`). Two of the six analysed projects carry their own code for
this today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-legacy-typoscript-paths` when the issue is filed after
implementation.
