## 1. Recursive merge in the loader

- [x] 1.1 Rewrite the top-level expectations in
      `academic-base/Tests/Unit/Settings/SettingsFileLoaderTest.php` and add
      tests for a nested change that keeps its siblings, a replaced list, an
      emptied list, `null` removing a top-level and a nested key, a list
      against a map, a map with integer keys, and both key-order rules; run
      them against the unchanged `array_merge()` and record that the sibling,
      `null` and order tests fail.
- [x] 1.2 Implement the recursive merge in `loadMergedArray()` with a private
      helper and correct the class docblock; verify 1.1 is green on v13 and
      v14.
- [x] 1.3 Add a test in `academic-persons/Tests/Unit/Settings/AcademicPersonsSettingsFactoryTest.php`
      where a second package changes one nested field; assert the upstream
      fields survive in the normalised settings, and show it red with
      `array_merge()` restored.
- [x] 1.4 Run the persons settings tests (`AcademicPersonsSettingsFactoryTest`,
      `LegacySettingsMigratorTest`, `SettingsSourceTest`) unchanged and
      correct the legacy-key docblocks - the claim sits on
      `AcademicPersonsSettingsFactory::overlayLegacySettings()` and in the
      class comment of `LegacySettingsMigratorTest`, not on the migrator
      itself; verify the legacy report and
      `academic:persons:settings:migrate` still attribute values per package.
- [x] 1.5 Fold the package arrays of `MigrateSettingsCommand` with the loader
      merge rather than its own `array_merge()`, which printed a document the
      runtime would not produce once a package before the legacy one
      overrode part of a top-level map; `SettingsFileLoader::merge()` becomes
      public for it. Pin it with a unit test that fixes the package order, and
      show it red with the `array_merge()` restored.
- [x] 1.6 Migrate the two test fixtures that existed only because of the
      shallow merge: `test_contract_contact_actions` of `academic_persons_edit`
      and `test_public_profile_settings` of `academic_persons`.

## 2. Documentation

- [x] 2.1 Replace "Restate the whole top-level map" in
      `docs/architecture/validation-settings.md` with the merge rules, and
      verify the page still ends with its `## See also`.
- [x] 2.2 Rewrite the top-level paragraphs in
      `academic-persons/Documentation/Configuration/Sections/Index.rst` and
      `Validations/Index.rst` with one example each for a one-key change, a
      replaced list and a `null` removal.
- [x] 2.3 Add `academic-persons/Documentation/Changelog/3.0/Breaking-SettingsFilesMergeRecursively.rst`
      from `Build/Documentation/Templates/Changelog-Breaking.rst`, with the
      omission-to-`null` migration; no academic_base entry, because the loader
      is `@internal`.
- [x] 2.4 Sweep every other document that describes the merge: the comment
      block of the shipped `Settings.yaml`, the `Upgrade` page, three further
      3.0 changelog entries of `academic_persons`, the `academic_base`
      changelog entry of the loader, and the settings page of
      `academic_persons_edit`. `academic_jobs` keeps its own top-level merge
      and its documentation stays as it is.

## 3. File the issue

- [x] 3.1 Verify ACE-109, ACE-161 and ACE-536 in YouTrack, file the ACE issue
      as a sub-task of ACE-109 and link ACE-161 and ACE-536 to it as related
      issues - ACE-161 is the duplicate of ACE-109, not of this change -,
      rename the change to `ace-711-settings-loader-deep-merge`, and commit as
      `[!!!][FEATURE] ACE-711: Deep merge settings files` in TYPO3 Core format;
      the tag is the one the change type asks for, and the subject keeps to 52
      characters.

## 4. Definition of done

- [x] 4.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
      `functional` for TYPO3 v13; the same after its own `composerUpdate` for
      TYPO3 v14.
- [x] 4.2 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 4.3 `docs/` and the academic_persons `Documentation/` changelog updated
      in the same change; `README.md` and `CONTRIBUTING.md` still only link.
- [x] 4.4 Archive the change as the last commit of the pull request.
