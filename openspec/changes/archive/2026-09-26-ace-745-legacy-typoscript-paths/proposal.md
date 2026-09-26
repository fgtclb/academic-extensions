## Why

Up to version 2.3, four extensions offered one static TypoScript template
each, at a path that 2.4 and 3.0 no longer fill. Installations store that path
in `sys_template.include_static_file`, and projects `@import` its
`setup.typoscript` by file. On `main` these folders hold no TypoScript, so the
stored include and the import deliver nothing, without an error, and the
plugins render unconfigured. TYPO3 also drops an unregistered value from the
template record the next time an integrator saves it. Four of the other five
academic extensions keep their old path working already; the 2.3 entry of
academic_partners named the wrong extension.

## What Changes

- Each path of version 2.3 delivers the TypoScript of its extension again. A
  stored template record gets what the "All components" static template
  delivers - for academic_contacts4pages without the academic_persons
  TypoScript, which a record of version 2.3 stores as an entry of its own. An
  `@import` of its `setup.typoscript` and `constants.typoscript` gets what an
  import of the files of the component folder gets:
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

- `academic-bite-jobs/legacy-static-template-path`: the static template path
  academic_bite_jobs offered up to version 2.3.
- `academic-contact4pages/legacy-static-template-path`: the same for
  academic_contacts4pages.
- `academic-persons-edit/legacy-static-template-path`: the same for
  academic_persons_edit.
- `academic-study-plan/legacy-static-template-path`: the same for
  academic_study_plan.

### Modified Capabilities

None.

## Impact

- Two TypoScript files per 2.3 folder, and one static template registration
  per extension in `Configuration/TCA/Overrides/sys_template.php`.
- A new trait in the testing helper for the tests.
- Sites that use the site set and still store the 2.3 path or import its files
  now get the double parse the documentation already warns about.
- No database or dependency change.

## Non-goals

- Detecting stored 2.3 values or dead imports (candidate `cross-cutting-09`).
- A guard against a site using a set and a static template together.
- Restoring TypoScript of version 2.3 that 2.4 removed on purpose, beyond what
  "All components" delivers.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`cross-cutting-07`). Two of the six analysed projects carry their own code for
this today. Filed as ACE-745 once the implementation was green.
