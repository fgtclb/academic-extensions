## Context

The field and what reads it on `main`:

- `tx_academicpersons_domain_model_contract.publish`, `smallint NOT NULL
  DEFAULT 0` in `ext_tables.sql`. The TCA column is a `checkboxToggle` in the
  `general` palette, with no `l10n_mode`. `Contract::$publish = false`, with
  `setPublish()`, `isPublish()` and `getPublish()`.
- `contracts.fields.publish` in `Configuration/AcademicPersons/Settings.yaml`,
  which gives the frontend editor its "Publish" switch. In
  `academic_persons_edit` it is read by `ContractFormData`,
  `ContractFactory::setPublish()` and one getter special case in
  `ProfileController`.
- No public view, repository or ViewHelper reads it. The only other mention is
  a `@todo` in `Classes/Backend/FormEngine/ContractItems.php`.

What already covers the same need:

- `hidden` is the contract's `enablecolumns.disabled`, `l10n_mode: exclude`.
  The list, detail and selected-contracts output leave a hidden contract out.
- The list plugins' "show hidden records" option and `findByUids(...,
  showHidden: true)` keep it for an internal directory.
- The editor's `toggleDocumentVisibilityAction()` writes `hidden` for a
  contract, and marks it with "Hidden" in the list.

## Goals / Non-Goals

**Goals:**

- One visibility per contract, the one the core, the public views and the
  editor already share.
- A project that did give `publish` a meaning can carry it into `hidden`
  without writing its own migration.

**Non-Goals:**

- A deprecation phase. See the decision below.

## Decisions

### Remove the field in 3.0, without a deprecation phase

The column, TCA column, palette entry, model property and accessors, the
settings field, the editor property and all labels go in one `[!!!]` change.

3.0 is not tagged, and the field never had an effect. A deprecation would keep
a toggle in the backend and in the editor that says "Show this contract
online?" and does nothing for one more major version.

Rejected:

- Honouring the flag in the public views, the earlier definition of this
  change. It gives a contract two visibility switches with different reach:
  `hidden` is an enable column that the core, workspaces and the "show hidden
  records" option know, and `publish` would be a filter in our views only. The
  editor would need a second toggle next to the eye.
- Making `publish` an enable column instead of `hidden`. That turns the core's
  own visibility field into the duplicate.

### The wizard ships unregistered

`MigrateContractPublishToHiddenUpgradeWizard` in `Classes/Upgrades/` carries
TYPO3's `#[UpgradeWizard('academicPersons_migrateContractPublishToHidden')]`
and Symfony's `#[Exclude]`. The resource load in `Services.yaml` then skips
it, so it is no service and the core does not list it. A site package that
wants it declares the class in its own `Services.yaml` with `autoconfigure:
true`. Autoconfiguration reads the `UpgradeWizard` attribute and adds the
`install.upgradewizard` tag with the identifier. That is the same route on
v13.4 (`cms-install/Configuration/Services.php`) and v14.3
(`cms-core/Configuration/Services.php`), both verified in the installed
trees.

On an installation without code of its own, every contract carries `publish =
0`, the default of the column, the TCA and the model. A registered wizard
would offer to hide all of them, and `upgrade:run` without a name would do it
unasked.

`#[Exclude]` on the class is preferred over an `exclude` entry in
`Services.yaml`, because the reason the wizard is unregistered is then stated
where it is defined. A later declaration of the same class id in a site
package replaces the excluded definition in either loading order.

Rejected:

- A registered wizard that implements `ConfirmableInterface`. It is still
  offered on every installation, and it is one click away from hiding every
  contract.
- A documented `UPDATE` statement. It cannot express the language rule below,
  and it leaves translations with a `hidden` value their default row does not
  have.
- No migration at all. The projects that wrote code for the field would each
  write the same wizard.

The wizard is one more call site of the `Install\Attribute\UpgradeWizard` and
`Install\Updates\UpgradeWizardInterface` API, which is removed in v15
(ACE-294). The round's rule was to add no wizard while v13 is supported. The
maintainer decided for this one, and the counts in `AGENTS.md` and
`docs/architecture/` are updated with it.

### What the wizard does

- **Source column.** It reads `publish`, or `zzz_deleted_publish` when the
  database analyser has renamed it already. With neither column it has
  nothing to do. It checks the table's columns through the schema manager, not
  the TCA, which no longer knows either name, with `listTableColumns()` like
  the other wizards: `introspectTableByUnquotedName()` exists from
  doctrine/dbal 4.4 on, and earlier TYPO3 13.4 releases, which the extension
  allows, require 4.2 or 4.3.
- **Deciding rows.** Every row with `l10n_parent = 0`, live or a workspace
  version, deleted or not, with `publish = 0` gets `hidden = 1`. A row with
  `publish = 1` keeps its `hidden`, since a hidden contract was already left
  out. A translation without a default record of its own, or whose default
  record is gone, decides the same way by its own flag.
- **Translations.** A translation gets `hidden = 1` when its `l10n_parent` was
  not published, whether the wizard hid that row or it was hidden already, and
  the wizard writes the translation itself. Its own `publish` value is not
  read, unless it has no default record, see the bullet above. The core copies an `l10n_mode: exclude` column into the translations
  only inside a DataHandler write (`DataMapProcessor`).
  Extbase and the QueryBuilder copy nothing, so a wizard that updated the
  default rows alone would leave every translation visible. Deciding by the
  default row gives the state that the next DataHandler save of that row
  would force anyway. A translation that was unpublished while its default
  row was published stays visible, because `hidden` cannot hold a
  per-language value that survives a backend save.
- **Workspace translations.** The DataHandler copies a translation into a
  workspace with its `l10n_parent` unchanged, so a workspace version of a
  translation names the live default record. It follows the version of that
  record in its own workspace where there is one, the record it is shown and
  published with, and the live record otherwise.
- **Repeated runs.** `updateNecessary()` and `executeUpdate()` share one
  decision: the visible rows the rules above hide. There is work while that
  list is not empty, and a second run changes nothing.
- **Queries.** The rule for a workspace translation needs the flag of another
  row in the same workspace, which one statement cannot express on every
  database system. The wizard reads the rows once, ordered by uid, decides in
  PHP, and hides by uid in chunks like
  `SeedContractOrganisationalUnitSortingUpgradeWizard`, with the value list
  quoted as in `docs/architecture/database-queries.md` and the constraint built
  on the builder that executes it.
- No `DatabaseUpdatedPrerequisite`: `hidden` exists already, and the add-only
  schema update of the prerequisite does not touch the old column.

### The wizard writes with the QueryBuilder, not the DataHandler

The wizard writes the default rows and their translations itself, in the same
run.

Rejected: a DataHandler datamap on the default rows, which would carry
`hidden` into the translations by itself. The DataHandler:

- cannot write deleted rows
- writes a workspace version only inside that workspace, so it needs one run
  per workspace
- writes history and reference index entries for every contract of the
  installation

The wizard changes one column to a value the DataHandler would produce too,
and it writes nothing else.

### The development seed drops the key, it does not hide contracts

`Scenario.yaml` sets `publish: 0` on contracts 12, 13 and their translations.
The key is removed rather than converted to `hidden: 1`, so the instances
render what they render today. `ScenarioLegacy.yaml` is regenerated, the seed
manifests are rewritten by `seedManifest`, and both SQLite templates are
rebuilt so they carry no column the schema no longer declares.

### A leftover settings override

A project `Settings.yaml` that copied the shipped map before the update still
configures `contracts.fields.publish`. The settings merge recursively, so the
field comes back without a TCA column and without a model property:

- The TCA listener skips a settings field whose column the TCA does not have,
  so the backend form is not affected.
- The editor would show a "Publish" switch without a value and accept a save
  that stores nothing.

The settings factory therefore leaves a contract field out whose property 3.0
removed, and logs a warning that names the key to remove. An exception was
rejected: the settings are built while the TCA is compiled, so one leftover
key in a copied map would stop every request. The warning follows how the
factory reports the pre-3.0 settings keys. A save that sends the field is then
refused like any field the form does not have.

A `managedFields` map that names `contracts: [publish]` already fails loudly,
with the error of a name that is no field of `contracts.fields`, where the
map is used. It stays that way.

## Risks / Trade-offs

- A project registers the wizard on an installation whose `publish` never
  meant anything, and every contract is hidden. → The `Important-*.rst` says
  in its first paragraph when not to run it. The registration is a
  deliberate, reviewed code change, and the result is undone per contract
  with the eye action or in the backend.
- An importer that writes `publish` through Extbase fails with an undefined
  method. One that writes through the `DataHandler` silently loses the value.
  → The `Breaking-*.rst` names both.
- A project gave `publish` a different value in a translation than in its
  default row, and its own code honoured that per language. → The wizard
  keeps such a translation visible, since `hidden` follows the default row.
  The `Important-*.rst` names the case, and the project hides the whole
  contract or keeps a per-language rule of its own.
- The database analyser drops `zzz_deleted_publish` before the wizard ran. →
  The data is gone. The `Important-*.rst` says to run the wizard before
  removing renamed columns.

## Migration Plan

1. Projects with their own `publish` handling register the wizard, update the
   schema without removals, run the wizard, then remove their own code.
2. Every other installation updates and lets the analyser remove the column.
   Nothing changes in the frontend.

Rolling back the code needs the column again. It stays as
`zzz_deleted_publish` until the analyser's removal is confirmed a second
time.
