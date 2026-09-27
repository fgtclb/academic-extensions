# Category type identifiers

A category stores the identifier of its type in `sys_category.type`, without
the group. The identifier of a category type is therefore unique across all
groups, and loading the types fails when two groups declare the same one.

How an integrator meets the rule is documented in the `For Developers` chapter
of `category_types`, section `Identifiers are unique across groups`, and in the
`Breaking-` entries of `category_types` and `academic_projects` for 3.0. This
page is about where it is checked, and how the one collision of the shipped
extensions was resolved.

## What a collision broke

Until 3.0 `academic_programs` and `academic_projects` both declared
`department` (ACE-64). The registry kept the two apart by group, but the TCA
override of `sys_category` uses the bare identifier as the item value and as
the `typeicon_classes` key, so the type select had two items with the value
`department`. After saving, FormEngine selected the first of them, and every
reader of the identifier counted the category as its own: the project list
offered the departments of study programs in its filter, and the other way
round. Two of the analysed projects removed the projects type to get around it.

## The check lives in the loader

`CategoryTypeLoader::loadUncached()` checks the bare identifiers once the
`Configuration/CategoryTypes.yaml` of every package is read, and throws a
`CategoryTypeExistException` with code `1790505412`. Identifiers are compared
without surrounding whitespace and ignoring case, because the loader keys types
by the trimmed identifier and MySQL and MariaDB compare `sys_category.type`
case-insensitively under their default collation. The message names the
identifier and, per group, the extension key the type records. After a
`useExisting` override that is the overriding extension, which is the package an
integrator has to change.

Two other places were rejected:

- **After each package.** A package loaded later could then not resolve a
  collision with `remove: true`, and `collisionResolvedByALaterRemovalIsAccepted`
  pins that it can.
- **The registry's `attach()`.** The registry also receives types from the
  cache and from direct calls, in tests and in third party code. A collision
  there is not a mistake of a package configuration, and the cache is only
  written after the loader succeeded.

## The projects type was renamed, not the programs one

The projects department is `project_department`. The projects that removed one
of the two types removed the projects one, so the programs department is the
one in use. Storing `group.identifier` instead was rejected: it rewrites every
`sys_category.type` row of every installation and breaks every lookup by type
name in project templates.

The label key and the icon identifier follow the identifier, because the
project page, the list item and the list filter build both from it
(`sys_category.projects.{type}`, `category_types.projects.{type}`). The setting
`filter.categoryTypes` and a filter link name the identifier too, and no
fallback reads `department` as `project_department`.

## A console command, not an upgrade wizard

`academic:projects:department:migrate` (`MigrateProjectDepartmentsCommand`)
moves the stored categories. No new upgrade wizard is added while `main`
supports TYPO3 v13, because every wizard is another call site of
`Install\Updates`, which v15 removes and v13 has no replacement for (ACE-294).

It classifies the categories of type `department` that are neither a
translation nor a workspace version, by the doktypes of the non-deleted
program and project pages they are assigned to:

| Assigned to                | With `academic_programs` | Without it |
|----------------------------|--------------------------|------------|
| Project pages only         | moved                    | moved      |
| Program pages only         | kept                     | moved      |
| Program and project pages  | kept and listed          | moved      |
| No program or project page | kept and listed          | moved      |

Hidden pages count, and pages of other doktypes decide nothing. A translation or
workspace version of a page counts by its own doktype, which is normally the
page's. A moved category takes its translations and workspace versions along. A
category of another language without an original in the default language cannot
be told from a category of its own and is classified like one: the category
fields of TYPO3 only offer categories of the default language and of all
languages, so it is listed as on no program or project page. The update is a
plain statement rather than DataHandler, which would need a backend user on the
command line and run every hook for a select value. The command always exits
with success: a listed category is a decision for the integrator, and a second
run moves only what is left.

The program doktype `20` is a constant of the command, because
`academic_projects` does not depend on `academic_programs`.

## Where it is tested

| What                                                  | Test                                                                                                 |
|-------------------------------------------------------|------------------------------------------------------------------------------------------------------|
| The check, its message, and a removal resolving it    | `typo3-category-types/Tests/Unit/Loader/CategoryTypeLoaderTest.php`                                  |
| Unique type items with programs and projects together | `academic-projects/Tests/Functional/CategoryTypes/CategoryTypeSelectWithProgramsTest.php`            |
| The old identifier in the filter setting              | `academic-projects/Tests/Functional/Plugins/AcademicProjectsListFilterTest.php`                      |
| The command with `academic_programs`                  | `academic-projects/Tests/Functional/Command/MigrateProjectDepartmentsCommandTest.php`                |
| The command without it                                | `academic-projects/Tests/Functional/Command/MigrateProjectDepartmentsCommandWithoutProgramsTest.php` |

## See also

- [Category type order](category-type-order.md) — the registry that receives
  the types the loader checked.
- [Core version aware code](core-version-aware-code.md) — the v15 blockers,
  and why no upgrade wizard is added.
- [Database queries](database-queries.md) — the value lists and the single
  builder the command's statements follow.
