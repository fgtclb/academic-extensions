## Context

- `academic-programs/Configuration/CategoryTypes.yaml:36` and
  `academic-projects/Configuration/CategoryTypes.yaml:21` declare
  `identifier: department`. No other identifier collides today.
- The loader keys types `<group>.<identifier>`
  (`typo3-category-types/Classes/Loader/CategoryTypeLoader.php:83-87`), and the
  registry rejects a duplicate only within one group
  (`Classes/Registry/CategoryTypeRegistry.php:36`).
- `typo3-category-types/Configuration/TCA/Overrides/sys_category.php:26,30`
  uses the bare identifier as the item value and as the `typeicon_classes`
  key, so both types end up as the value `department`.
- Project pages are doktype 30, program pages doktype 20; both relate
  categories through `pages.categories` (`sys_category_record_mm`,
  `tablenames = pages`, `fieldname = categories`).
- Apart from the YAML and the label files, no shipped template, TypoScript or
  PHP names `department`. Since the filter types of the project list
  (ACE-736), the identifier is also a value of the setting
  `plugin.tx_academicprojects.filter.categoryTypes`, named in its description
  in `Configuration/Sets/Full/settings.definitions.yaml` and in the
  `Configuration` chapter, and it is the parameter name of the department
  filter in a filter link (`…[filterCollection][department]=4`).
- The seed in
  `packages-dev/dev-site/Configuration/DataFactory/academics-instance/Scenario.yaml`
  has two `department` categories: id 9 among the programs types and id 21
  among the projects types.

## Goals / Non-Goals

**Goals:**

- A collision is a load-time error, not a silently broken select.
- Stored project departments move without guessing.

**Non-Goals:**

- Resolving ambiguous categories automatically.

## Decisions

### Check in the loader, after every package

`loadUncached()` checks the bare identifiers across groups once all packages
are read, so a later package's `remove` can resolve a collision first. The
exception is a `CategoryTypeExistException` with a new code, and names the
identifier and, per group, the extension key the type records. That key is the
extension that declared the type last, so an override reports the overriding
extension, which is the one to change. Identifiers are compared trimmed and
ignoring case: the loader keys types by the trimmed identifier, and MySQL and
MariaDB compare `sys_category.type` case-insensitively under their default
collation, so `Department` would still find the categories of `department`
there.

Rejected: the check in the registry's `attach()`. The registry also receives
types restored from the cache and types built in tests, and cannot tell a
package configuration error from a programming error.

### Rename the projects type

The projects type becomes `project_department`, and its label key
`sys_category.projects.department` becomes
`sys_category.projects.project_department` in the four label files. The key
has to follow: the project page, the list item and the list filter build it
from the type identifier (`sys_category.projects.{type}`), and so does the
icon identifier `category_types.projects.{type}`, which the registry derives
from the identifier anyway. The icon file stays. Rejected: keeping the label
key and adding a second one; two keys for one label is what a translation or
a `locallangXMLOverride` of a project would then have to know about.
Rejected: renaming the programs type. The analysed projects that
removed one of the two removed the projects type, so the programs department
is the one in use. Rejected: group-qualified stored values (see the proposal's
non-goals).

### Decided: a console command instead of an upgrade wizard

No new upgrade wizard is added while the branch supports TYPO3 v13, because
every wizard is another call site of the v15 blocker `Install\Updates`
(ACE-294), and the replacement does not exist on v13. The migration is a
`final` Symfony console command in `academic-projects/Classes/Command/`,
registered with `#[AsCommand]` as `academic:projects:department:migrate`,
like the geocoding command of academic_partners. The `Breaking-` changelog
documents running it as the upgrade step. Revisit with ACE-294: once v13
support is dropped, the command may become a `Core\Upgrades` wizard.

Rejected: plain `UPDATE` statements in the changelog. Telling project
departments from programs departments needs the doktypes of the pages each
category is assigned to, and the ambiguous categories have to be listed; an
integrator should not have to write that join by hand. Rejected: runtime
self-healing, which would change stored categories during a frontend or
backend request.

- Classification: categories of type `department` that are neither a
  translation nor a workspace version (`l10n_parent = 0`, `t3ver_oid = 0`) and
  not deleted, joined through the MM table to non-deleted pages of the program
  and project doktypes, collecting the doktypes per category. Hidden pages
  count; deleted pages do not. A translation or workspace version of a page
  counts by its own doktype, which is normally the page's. Pages of any other
  doktype decide nothing: the type only matters to the program and project
  lists, so a category on a project page and a standard page is a project
  department. The program doktype `20` is a constant of the command, because
  `academic_projects` does not depend on `academic_programs`. -
  `ExtensionManagementUtility::isLoaded('academic_programs')` decides the "not
  installed" case. - The update sets the type for the classified uids and for
  rows whose `l10n_parent` or `t3ver_oid` is among them. Value lists go
  through `quoteArrayBasedValueListToIntegerList()`, every statement is built
  and run on one query builder, and the listed ambiguous categories are
  ordered by `uid`. - The command prints how many categories it moved and
  lists the ambiguous ones by uid and title, each with the reason: used on
  program and project pages, or on none of them. A second run moves nothing
  and lists them again. It exits with status zero in both cases, because an
  ambiguous category is a decision for the integrator, not a failure.

Rejected: DataHandler for the update. The change is a pure rename of a
select value; DataHandler would need a backend user on the CLI and would run
every hook for it.

### Decided: rename in 3.0 with the collision check and the ambiguity report

The collision is fixed in 3.0 by renaming the projects type to
`project_department`, with the load-time collision check and the migration
command that lists ambiguous categories. `department` is the only collision
today, and the projects that removed one of the two types removed the projects
one, so the programs department is the one in use. Storing group-qualified
values (`group.identifier`) in 4.0 was rejected: it migrates every
`sys_category.type` row and breaks every lookup by type name in project
templates. The ambiguity report is the console command above, not an upgrade
wizard: the only exception to "no new wizard while v13 is supported" is the
bite jobs list change, and it does not extend to this one.

### The filter setting and links follow, without a fallback

The description of `filter.categoryTypes`, the example in the `Configuration`
chapter and the tests of the project list use `project_department`. A setting
that still names `department` leaves that filter out, as it does for every
identifier the group does not have, and an old filter link no longer filters by
department. Both are named in the `Breaking-` entry. Rejected: reading
`department` as `project_department` in the projects filter. It would keep a
second spelling alive for a type that is no longer called that.

### The rule is documented in `docs/`

`docs/architecture/category-type-identifiers.md` is a new page: the stored
value is the bare identifier, the check runs in the loader after every
package, and the projects type was renamed with a command rather than a
wizard. The order page describes the registry, not the identifiers, so a
section there would not be found.

### The seed follows

Category 21 of `Scenario.yaml` becomes `project_department`,
`ScenarioLegacy.yaml` is regenerated with
`Build/Scripts/generateLegacyScenario.php`, and `seedManifest` runs per core
version.

## Risks / Trade-offs

- [Ambiguous categories stay `department` and show up as programs
  departments] → the command lists them; the changelog explains how to
  decide.
- [An integrator skips the command, since nothing in the upgrade wizard list
  points to it] → the `Breaking-` entry names it as the upgrade step.
- [A project `CategoryTypes.yaml` that overrides the old projects type stops
  TYPO3 after the update, the command included] → the `Breaking-` entry makes
  that file its first step, in the same deployment as the update.
- [A project that removed the projects type runs the command anyway] → its
  department categories on project pages would move to a type it removes;
  the `Breaking-` entry tells such a project not to run it.
- [Project templates naming the projects department] → the `Breaking-` entry
  says what to search for.
- [Cached category types] → caches are flushed after an extension update
  anyway; the changelog says so.

## Migration Plan

1. In the same deployment as the update, replace `department` with
   `project_department` in every `CategoryTypes.yaml` that changes or removes
   the projects type; a project that removes it does not run the command.
2. Update the extensions and flush the caches.
3. Run `academic:projects:department:migrate`; decide the listed categories
   by editing their type.
4. Replace `department` with `project_department` in the project filter
   setting and in project templates, then flush the page cache.
5. Rollback: restore the code and set `project_department` back to
   `department` with one update statement; no other data changes.

## Open Questions

None.
