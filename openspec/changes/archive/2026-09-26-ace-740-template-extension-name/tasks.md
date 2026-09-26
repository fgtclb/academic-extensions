## 1. Premises

- [x] 1.1 Count the calls on `2`: 249 in 70 templates, two more without a name; the PHP of
  the three sorting select view helpers, the select items trait, and the old profile
  editor (flash messages, select options with `persons_edit`).
- [x] 1.2 Verify on v12 by running: the ported tests fail on the override cases before the
  conversion, as on v13.

## 2. Tests first

- [x] 2.1 Port the `*LabelOverrideTest` of the eight extensions to `2`; the profile editor
  test is new, for the old editor. On v13 and v12 every override case failed before the
  change (partners 15, projects 19, programs 14, persons 15, editor 12, jobs 24 - the job
  alert already had the right name -, bite jobs 9, study plan 3).
- [x] 2.2 Hide the category filter and render the sorting select from a partial of the test.
- [x] 2.3 Add `TranslationExtensionNameTest`; it failed for the nine extensions and for the
  two job property values without a name, and for an underscored name handed to
  `translate()` in PHP.
- [x] 2.4 Add the editor's words before a single year (`detail.since`), which read the path
  of academic_persons: the override of academic_persons failed with the old name on v12
  and v13.

## 3. Implementation

- [x] 3.1 Rewrite the `extensionName` of every template to UpperCamelCase, name it where a
  call passed none.
- [x] 3.2 UpperCamelCase default of the three sorting select view helpers.
- [x] 3.3 `AcademicPersonsEdit` for the editor's flash messages and select options.
- [x] 3.4 The key conversion of the select items trait.

## 4. Documentation

- [x] 4.1 A `Labels` page per extension's configuration chapter.
- [x] 4.2 `Important-` entries in `Changelog/2.4/`, the same on `main` - they describe what
  2.4 changes; `main` adds what only it has in `Changelog/3.0/`. ACE-739's entries identical
  to `main`.
- [x] 4.3 `docs/architecture/label-overrides.md` for v12 and v13, linked from the index;
  `list-filter-types.md`, the unit test and monorepo layout pages and `AGENTS.md` updated.

## 5. Definition of done

- [x] 5.1 `lintPhp`, `cgl -n`, `phpstan`, `unit` and `functional -j auto` for TYPO3 v12
  (PHP 8.1) and v13, each after its own `composerUpdate`.
- [x] 5.2 `checkRstRenderingAll` and `lintMarkdown -n`.
- [x] 5.3 Commit message in the TYPO3 Core format with `ACE-740`; this change archived as
  the last commit of the pull request.
