## Why

`academic_programs` and `academic_projects` both declare a category type with
the identifier `department`. The registry keeps them apart by group, but a
category stores the bare identifier, so the type select of a category offers
two entries with the value `department`. After saving, the backend preselects
the first one, and the frontend filters count such categories for both
extensions. ACE-64 reports it; two projects removed the projects type to get
around it.

## What Changes

- **BREAKING** `category_types` (`packages/fgtclb/typo3-category-types`):
  loading the category types fails with an error naming the identifier and
  both extensions when one identifier is declared in two groups. A project
  declaring such an identifier fails at load time instead of producing a
  broken select.
- **BREAKING** `academic_projects` (`packages/fgtclb/academic-projects`): the
  department type is renamed to `project_department`. Templates, filters and
  `CategoryTypes.yaml` overrides naming the projects department must follow.
- `academic_projects` ships a console command,
  `academic:projects:department:migrate`, that moves stored department
  categories to `project_department`. It is not an upgrade wizard, because no
  new wizard is added while TYPO3 v13 is supported (ACE-294). The command:
  - a category assigned only to project pages is moved, with its
    translations;
  - without `academic_programs` installed, every `department` category is
    moved;
  - a category assigned only to program pages stays unchanged;
  - a category assigned to both, or to no page, stays unchanged and is listed
    for a manual decision;
  - can be run again, moving only what is left.
- `academic_programs` (`packages/fgtclb/academic-programs`) keeps
  `department`.
- The development seed moves its project department category to the new
  type.

The behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `typo3-category-types/category-type-identifiers`: a type identifier
  belongs to exactly one group.
- `academic-projects/project-department-category-type`: the projects
  department as its own type, and the migration of stored categories.

### Modified Capabilities

None.

## Impact

- The category type loader, the projects `CategoryTypes.yaml`, one console
  command in `academic_projects`; no new upgrade wizard.
- Data: `sys_category.type` of project department categories.
- The seed in `packages-dev/dev-site` and everything generated from it.
- `Breaking-` changelog entries in `category_types` and `academic_projects`.

## Non-goals

- Storing group-qualified identifiers in `sys_category.type`; it migrates
  every category of every installation and breaks every lookup by type name.
- Renaming the programs type.
- An upgrade wizard, until ACE-294 settles how wizards are written once v13
  support ends.
- A backport to branch `2`: it renames an identifier and migrates data.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`cross-cutting-16`). Three of the six analysed projects carry their own code for
this today. The change implements the existing issue ACE-64 and is renamed to
`ace-64-category-type-identifier-collision` once that issue is verified in
YouTrack before implementation starts.

Implements ACE-64.
