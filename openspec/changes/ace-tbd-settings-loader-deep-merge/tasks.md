## 1. Recursive merge in the loader

- [ ] 1.1 Rewrite the top-level expectations in
      `academic-base/Tests/Unit/Settings/SettingsFileLoaderTest.php` and add
      tests for a nested change that keeps its siblings, a replaced list, an
      emptied list, `null` removing a top-level and a nested key, a list
      against a map, a map with integer keys, and both key-order rules; run
      them against the unchanged `array_merge()` and record that the sibling,
      `null` and order tests fail.
- [ ] 1.2 Implement the recursive merge in `loadMergedArray()` with a private
      helper and correct the class docblock; verify 1.1 is green on v13 and
      v14.
- [ ] 1.3 Add a test in `academic-persons/Tests/Unit/Settings/AcademicPersonsSettingsFactoryTest.php`
      where a second package changes one nested field; assert the upstream
      fields survive in the normalised settings, and show it red with
      `array_merge()` restored.
- [ ] 1.4 Run the persons settings tests (`AcademicPersonsSettingsFactoryTest`,
      `LegacySettingsMigratorTest`, `SettingsSourceTest`) unchanged and
      correct the legacy-key docblock of `LegacySettingsMigrator`; verify the
      legacy report and `academic:persons:settings:migrate` still attribute
      values per package.

## 2. Documentation

- [ ] 2.1 Replace "Restate the whole top-level map" in
      `docs/architecture/validation-settings.md` with the merge rules, and
      verify the page still ends with its `## See also`.
- [ ] 2.2 Rewrite the top-level paragraphs in
      `academic-persons/Documentation/Configuration/Sections/Index.rst` and
      `Validations/Index.rst` with one example each for a one-key change, a
      replaced list and a `null` removal.
- [ ] 2.3 Add `academic-persons/Documentation/Changelog/3.0/Breaking-SettingsFilesMergeRecursively.rst`
      from `Build/Documentation/Templates/Changelog-Breaking.rst`, with the
      omission-to-`null` migration; no academic_base entry, because the loader
      is `@internal`.

## 3. File the issue

- [ ] 3.1 Verify ACE-109 and ACE-161 in YouTrack, file the ACE issue as a
      sub-issue of ACE-109 and link ACE-161 to it as the duplicate, rename
      the change to `ace-<NNN>-settings-loader-deep-merge`, and commit as
      `[!!!][TASK] ACE-<NNN>: Merge settings files recursively` in TYPO3 Core
      format.

## 4. Definition of done

- [ ] 4.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
      `functional` for TYPO3 v13; the same after its own `composerUpdate` for
      TYPO3 v14.
- [ ] 4.2 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 4.3 `docs/` and the academic_persons `Documentation/` changelog updated
      in the same change; `README.md` and `CONTRIBUTING.md` still only link.
- [ ] 4.4 Archive the change as the last commit of the pull request.
