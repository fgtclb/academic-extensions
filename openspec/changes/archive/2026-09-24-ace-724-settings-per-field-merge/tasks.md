## 1. Prerequisites

- [x] 1.1 Confirm `ace-711-settings-loader-deep-merge` is merged, and verify
  in the merged loader that maps merge recursively, lists replace and a null
  value removes an entry; record which tests of the first draft ACE-711
  already contains.

## 2. Comparison

- [x] 2.1 Add unit tests for the comparison of a package with the packages
  before it: a delta-only file, a copy of `profile` that changes the
  `gender` validators and leaves out one field, a `~` for an entry an earlier
  package has and for one none has, a map replacing a list, a complete
  restatement of `contracts.fields` in another order, a new entry placed
  between restated ones, and a restated field that leaves out its
  `validators`; assert the delta, the removed and the omitted entries, and
  that merging the delta onto the earlier packages equals merging the file,
  key order included. Show them red against a stub. Added after review: a
  copy made before upstream added a key to its fields, a copy with its keys in
  another order, a restated `contracts` map with its fields cut down, a copy
  that keeps few entries, the two-entry boundary, a map with integer keys, and
  a new entry in a partial map.
- [x] 2.2 Implement the comparison as a stateless service that folds with the
  loader's merge; show the copy rule, the two-entry threshold, the copy
  inherited from a copied parent, the restatement fallback and the list guard
  each turn a test red when broken.
- [x] 2.3 Add a factory test with two packages shipping legacy `validations`
  for different fields, asserting both apply; show it red with a top-level
  fold of the package arrays.

## 3. Report and command

- [x] 3.1 Report removed and omitted entries per package in
  `LegacySettingsStatus`; add a fixture package with a copied map and a `~`,
  extend the functional test under `Tests/Functional/Report/` and show it
  fails without the new entries; a removal-only package is an info.
- [x] 3.2 Add `--delta` to `academic:persons:settings:migrate`; add a
  functional command test under `Tests/Functional/Command/` that asserts the
  printed delta, including `~` and the omission comments, and that merging it
  gives the same settings; verify the default output and exit codes are
  unchanged. Shown red without the option (it does not exist), with `null`
  dumped as `null`, and with the full file printed instead of the delta; a
  unit test covers the droppable file and the installation without a second
  package.

## 4. Documentation

- [x] 4.1 Name the report entry and `--delta` in the "Loading and overriding"
  header comment of `Configuration/AcademicPersons/Settings.yaml`.
- [x] 4.2 Describe the report entry and `--delta` in
  `Documentation/Configuration/Sections/Index.rst` or `Validations/Index.rst`,
  wherever overriding is explained, and on the page of the migration command.
- [x] 4.3 Add `Documentation/Changelog/3.0/Feature-SettingsOverrideReportAndDelta.rst`
  and link it from `Breaking-SettingsFilesMergeRecursively.rst`.
- [x] 4.4 Update "Overriding the settings in an installation" in
  `docs/architecture/validation-settings.md`.

## 5. File the issue

- [x] 5.1 After implementation, file the ACE issue in YouTrack (relating it to
  ACE-536, ACE-109, ACE-161 and ACE-711) and verify the key with a GET request:
  ACE-724.
- [x] 5.2 Rename the change to `ace-724-settings-per-field-merge` and verify
  `openspec validate` passes under the new name.
- [x] 5.3 Commit as `[FEATURE] ACE-724: Report settings override deltas` in
  TYPO3 Core format.

## 6. Definition of done

- [x] 6.1 `composerUpdate -t 13`, then `lintPhp`, `cgl -n`, `phpstan`, `unit`
  and `functional` green for v13.
- [x] 6.2 `composerUpdate -t 14`, then `lintPhp`, `cgl -n`, `phpstan`, `unit`
  and `functional` green for v14.
- [x] 6.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 6.4 `docs/` and the `Documentation/` changelog entry are part of the
  commit.
- [x] 6.5 Archive the change as the last commit of the pull request.
