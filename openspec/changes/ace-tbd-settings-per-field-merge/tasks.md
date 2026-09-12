## 1. Prerequisites

- [ ] 1.1 Confirm `ace-tbd-settings-loader-deep-merge` is merged, and verify
  in the merged loader that maps merge recursively, lists replace and a null
  value removes an entry.

## 2. Factory

- [ ] 2.1 Consume the loader's merged array unchanged, with no merge of the
  factory's own; extend
  `Tests/Unit/Settings/AcademicPersonsSettingsFactoryTest.php` with two
  package arrays where the second changes only `profile.gender.validators`,
  asserting every other field and the layout survive, and show the test
  fails with the old `array_merge()` fold.
- [ ] 2.2 Add unit tests for list replacement, `~` removal of a field, a
  section and a layout entry, a copied map that omits a field, and two
  packages with legacy `validations` for different fields, asserting both
  apply through the legacy overlay; show the legacy test fails with the old
  fold. Add tests that a complete restatement of `contracts.fields` in
  another order takes that order, and that a field set to `{}` replaces the
  upstream definition.
- [ ] 2.3 Extend
  `Tests/Functional/Plugins/AcademicPersonsPublicProfileSettingsOverrideTest.php`
  with a fixture package that ships only a delta, and assert the rendered
  detail layout.

## 3. Report and command

- [ ] 3.1 Report removed and omitted entries per package in
  `LegacySettingsStatus`; extend its functional test under
  `Tests/Functional/Report/` and show it fails without the new entries.
- [ ] 3.2 Add `--delta` to `academic:persons:settings:migrate` and switch its
  fold to the loader's merge; add a functional command test under
  `Tests/Functional/Command/` that asserts the printed delta, including `~`,
  and verify the default output and exit codes are unchanged.

## 4. Documentation

- [ ] 4.1 Rewrite the "Loading and overriding" header comment of
  `Configuration/AcademicPersons/Settings.yaml`.
- [ ] 4.2 Update `Documentation/Configuration/Sections/Index.rst` and
  `Documentation/Configuration/Validations/Index.rst` with delta examples,
  the order rule (restate every key to reorder; the display order comes from
  the layout lists) and the warning that `{}` replaces an entry while `~`
  removes it.
- [ ] 4.3 Add `Documentation/Changelog/3.0/Breaking-SettingsMergedPerEntry.rst`
  with the `~` recipe, the merge of the pre-3.0 keys and the migration steps.
- [ ] 4.4 Update "Overriding the settings in an installation" in
  `docs/architecture/validation-settings.md`.

## 5. File the issue

- [ ] 5.1 After implementation, file the ACE issue in YouTrack (relating it to
  ACE-536, ACE-109 and ACE-161) and verify the key with a GET request.
- [ ] 5.2 Rename the change to `ace-<NNN>-settings-per-field-merge` and verify
  `openspec validate` passes under the new name.
- [ ] 5.3 Commit as `[!!!][FEATURE] ACE-<NNN>: Merge persons settings per entry`
  in TYPO3 Core format.

## 6. Definition of done

- [ ] 6.1 `composerUpdate -t 13`, then `lintPhp`, `cgl -n`, `phpstan`, `unit`
  and `functional` green for v13.
- [ ] 6.2 `composerUpdate -t 14`, then `lintPhp`, `cgl -n`, `phpstan`, `unit`
  and `functional` green for v14.
- [ ] 6.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 6.4 `docs/` and the `Documentation/` changelog entry are part of the
  commit.
- [ ] 6.5 Archive the change as the last commit of the pull request.
