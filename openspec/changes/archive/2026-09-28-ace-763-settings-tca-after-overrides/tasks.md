## 1. Listener

- [x] 1.1 Add a fixture extension that replaces the teaching area column and the
  publication record type, locks the profile title, makes the contract position
  optional, declares a field without a column, locks the website from a listener
  named `content-blocks-tca`, and unlocks the middle name from a listener
  ordered after the settings. A functional test for each, for the e-mail soft
  reference, and for the cached TCA and the cached TCA schema. Red before the
  change: all nine tests, because the field without a column stops the old TCA
  build with `Missing "type"`. Without that field, the first six tests written
  showed five failures, and the soft reference test passed as it should.
- [x] 1.2 Apply the settings in a listener of `AfterTcaCompilationEvent`
  ordered after `content-blocks-tca`, and remove the merges from the six TCA
  files. Each part shown red when broken: the ordering after
  `content-blocks-tca`, the soft reference, the column filter, the disabled
  column (the visibility switch tests of `academic_persons_edit`), the guard for
  a missing table (unit test), and the public identifier (the fixture listener
  without its ordering, and the persons identifier renamed).

## 2. The project field change

- [x] 2.1 Record in `ace-tbd-editor-custom-profile-fields` that it depends on
  this change, and its decisions: the column check at use in the editor, and a
  deprecation notice while the TCA is compiled.

## 3. Definition of done

- [x] 3.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v13, and the same after its own `composerUpdate` for
  TYPO3 v14, functional also with `-d postgres`.
- [x] 3.2 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 3.3 `docs/`: `docs/architecture/validation-settings.md`, the listener
  counts in `AGENTS.md`, `docs/architecture/dependency-injection.md` and
  `docs/architecture/class-design.md`, the fixture page, the backend paragraph
  of `docs/architecture/form-data-transformation.md`.
- [x] 3.4 `Documentation/`:
  `Changelog/3.0/Breaking-SettingsApplyAfterTcaOverrides.rst`, the validations
  chapter, the upgrade guide, the two 3.0 entries that named the TCA files, and
  the identifier on the extension points page of `academic_base`.
- [x] 3.5 File the ACE issue, rename the change, commit in TYPO3 Core format.
- [x] 3.6 Archive the change as the last commit of the pull request.
