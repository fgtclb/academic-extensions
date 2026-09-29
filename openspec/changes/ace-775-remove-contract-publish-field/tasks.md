## 1. Premises

- [x] 1.1 Re-check on `main` HEAD that no public view, repository or
  ViewHelper reads the contract `publish` field (`grep -rnw publish` over
  `packages/` and `packages-dev/`), that `hidden` is the contract's enable
  column with `l10n_mode: exclude`, and that the editor's hide action writes
  `hidden` for contracts. Record the result in the pull request.

## 2. Tests first

- [x] 2.1 Add a functional test of the backend contract form (or the TCA of
  the table) that asserts there is no `publish` column. Record that it fails
  on the unchanged `main`.
- [x] 2.2 Add a functional editor test: the contract form renders no
  "Publish" switch, and a contract save that sends `publish` is refused as an
  unknown field while nothing is stored. Record that both fail on the unchanged
  `main`.
- [x] 2.3 Add a functional test that the upgrade wizard registry does not
  know `academicPersons_migrateContractPublishToHidden`, and one with a
  fixture extension whose `Services.yaml` declares the wizard class with
  `autoconfigure: true`, where the registry knows it. Show the first red by
  removing `#[Exclude]`, and the second red by removing the declaration.
- [x] 2.4 Add a functional wizard test with a fixture extension whose
  `ext_tables.sql` adds the old `publish` column. Rows: an unpublished
  visible contract with a translation, a published contract, a published and
  hidden contract, an unpublished contract with a published translation, a
  published contract with an unpublished translation, a workspace version,
  workspace versions of translations whose workspace holds a version of the
  default record with the opposite flag, or none, a translation without a
  default record, one whose default record is gone, and a deleted row. Assert
  the `hidden` values after a run, that `updateNecessary()` is false
  afterwards, and that a second run changes nothing. Show it red by updating
  the default rows only (a translation of a hidden row stays visible, since no
  DataHandler carries the value over), by deciding by the translation's own
  flag, by dropping the lookup of the workspace version, and by leaving a
  translation without a default record visible.
- [x] 2.5 Run the wizard test of 2.4 once more with the column named
  `zzz_deleted_publish` (a second fixture extension), and once with neither
  column, where `updateNecessary()` is false. Show the first red by reading
  `publish` only.

## 3. Remove the field

- [x] 3.1 `academic_persons`: remove the column from `ext_tables.sql`, the
  TCA column and its palette entry, `Contract::$publish` with `setPublish()`,
  `isPublish()` and `getPublish()`, `contracts.fields.publish` in
  `Configuration/AcademicPersons/Settings.yaml`, the TCA label and the help
  text in English and German, and the `@todo` in `ContractItems.php`. Verify
  with 2.1.
- [x] 3.2 `academic_persons_edit`: remove `ContractFormData::$publish`, its
  constructor parameter and `isPublish()`, `ContractFactory::setPublish()`,
  the getter special case in `ProfileController`, and `contract.publish.label`
  in English and German. Verify with 2.2.
- [x] 3.3 Adjust every test and fixture that names the field, and name each in
  the pull request: `ContractTest`, `ContractFormDataTest`,
  `ContractFactoryTest` with its CSV fixtures, the settings tests
  (`SettingsSourceTest`, `SettingsOverrideComparatorTest`,
  `AcademicPersonsSettingsFactoryTest`, the `test_settings_copy` fixture),
  `AcademicPersonsEditProfileEditingTest`, `AcademicPersonsEditDocumentSortingTest`,
  `AcademicPersonsEditLabelOverrideTest` and the persons plugin CSV fixtures.
  Where a test used `publish` as its only check field, pick another field of
  the same render type, or drop the case and say why.
- [x] 3.4 Check what a project `Settings.yaml` that still configures
  `contracts.fields.publish`, and a `managedFields` map that names the
  column, do on v13 and v14. Keep a loud error, and turn a silently broken
  field into one. Cover the result with a test and record it for the
  changelog.

## 4. The upgrade wizard

- [x] 4.1 Add `Classes/Upgrades/MigrateContractPublishToHiddenUpgradeWizard.php`
  with `#[UpgradeWizard('academicPersons_migrateContractPublishToHidden')]`
  and Symfony's `#[Exclude]`, doing what design.md describes. Verify with 2.3
  to 2.5.
- [x] 4.2 Re-measure the upgrade wizard call sites with the command in
  `docs/architecture/core-version-aware-code.md` and update the counts there,
  in `docs/architecture/dependency-injection.md` and in `AGENTS.md`.

## 5. Development seed

- [x] 5.1 Remove the `publish` keys from
  `packages-dev/dev-site/Configuration/DataFactory/academics-instance/Scenario.yaml`,
  regenerate `ScenarioLegacy.yaml` with
  `Build/Scripts/generateLegacyScenario.php` and verify with `--check`.
- [x] 5.2 Run `seedManifest` for v13 and v14 and commit both manifests.
- [x] 5.3 Rebuild both `sqlite-databases/core-*.sqlite` templates from the
  seed, following the snapshot hygiene of `docs/development/environment.md`,
  and verify that neither holds a `publish` column on the contract table.

## 6. Documentation

- [x] 6.1 `academic_persons`: add
  `Documentation/Changelog/3.0/Breaking-ContractPublishFieldRemoved.rst` from
  `Build/Documentation/Templates/`: what is removed, that no rendered output
  changes, what breaks in project code (Extbase accessors, raw queries,
  settings and `managedFields` keys, the silently dropped `DataHandler`
  value), and the migration to `hidden`. It points to the wizard and says it
  is not registered by default.
- [x] 6.2 `academic_persons`: add
  `Documentation/Changelog/3.0/Important-ContractPublishToHiddenWizard.rst`.
  The first paragraph says when not to run it: an installation whose
  `publish` never meant anything would have every contract hidden. Then the
  `Services.yaml` declaration for a site package, the order (schema update
  without removals, wizard, then removal of the renamed column), and what it
  does with translations and workspace versions, including that a translation
  unpublished below a published default row stays visible.
- [x] 6.3 `academic_persons_edit`: add
  `Documentation/Changelog/3.0/Breaking-ContractPublishSwitchRemoved.rst`
  for the form switch, `ContractFormData`, the label and the refused field.
  Correct the line about `contract.publish.label` in
  `Breaking-ReplacedProfileEditingPlugin.rst`, which is unreleased 3.0 text.
- [x] 6.4 Describe contract visibility in the manuals: `hidden`, the "show
  hidden records" option and the editor's hide action, with the wizard in the
  upgrade chapter of `academic_persons`. Verify with `checkRstRenderingAll`.
- [x] 6.5 `docs/`: a section on shipping a service unregistered with
  `#[Exclude]` for a site package to declare, in
  `docs/architecture/dependency-injection.md`. Verify with
  `lintMarkdown -n`.

## 7. File the issue

- [x] 7.1 File the ACE issue in YouTrack, verify its key and rename the
  change to `ace-775-remove-contract-publish-field`.
- [x] 7.2 Commit as `[!!!][TASK] ACE-775: Drop the contract publish field`
  in TYPO3 Core format.

## 8. Definition of done

- [x] 8.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 13`, and `functional -d postgres -j 8`, since
  the wizard writes.
- [x] 8.2 `composerUpdate`, then the same suites green with `-t 14`.
- [x] 8.3 `lintMarkdown -n`, `checkRstRenderingAll` and `seedManifest` green.
- [x] 8.4 `docs/` and both `Documentation/` changelogs updated as in group 6.
  `README.md` and `CONTRIBUTING.md` still only summarise.
- [ ] 8.5 No backport: a removal on a maintenance line. State it in the pull
  request, together with anything else left out.
- [ ] 8.6 Archive the change as the last commit of the pull request.
