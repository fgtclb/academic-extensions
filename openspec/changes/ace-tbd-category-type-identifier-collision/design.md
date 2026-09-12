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
  PHP names `department`.
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
exception carries a new code and names the identifier and the extension keys
the types record.

Rejected: the check in the registry's `attach()`. The registry also receives
types restored from the cache and types built in tests, and cannot tell a
package configuration error from a programming error.

### Rename the projects type

The projects type becomes `project_department`; its label keys and its icon
file stay. Rejected: renaming the programs type. The analysed projects that
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

- Classification: default-language categories of type `department`, joined
  through the MM table to non-deleted pages, collecting the doktypes per
  category. Hidden pages count; deleted pages do not.
- `ExtensionManagementUtility::isLoaded('academic_programs')` decides the
  "not installed" case.
- The update sets the type for the classified uids and for rows whose
  `l10n_parent` or `t3ver_oid` is among them. Value lists go through
  `quoteArrayBasedValueListToIntegerList()`, every statement is built and run
  on one query builder, and the listed ambiguous categories are ordered by
  `uid`.
- The command prints how many categories it moved and lists the ambiguous
  ones by uid and title; a second run moves nothing and lists them again. It
  exits with status zero in both cases, because an ambiguous category is a
  decision for the integrator, not a failure.

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
  points to it] → the `Breaking-` entry names it as the upgrade step, and the
  integrator migration guide lists it.
- [Project templates naming the projects department] → the `Breaking-` entry
  says what to search for.
- [Cached category types] → caches are flushed after an extension update
  anyway; the changelog says so.

## Migration Plan

1. Update the extensions and flush the caches.
2. Run `academic:projects:department:migrate`; decide the listed categories
   by editing their type.
3. Rollback: restore the code and set `project_department` back to
   `department` with one update statement; no other data changes.

## Open Questions

None.
