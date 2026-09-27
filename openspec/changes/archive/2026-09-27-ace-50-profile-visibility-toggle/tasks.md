## 1. Preparation

- [x] 1.1 Re-read ACE-50, ACE-20 and ACE-474 and note here which part of
  ACE-50 this change covers: the public directory through `hidden`, the
  internal directory through "Show hidden records", with the limits the design
  names. Covered: the owner's switch for the public directory, and the
  internal directory of the second use case through "Show hidden records".
  Not covered: a directory that tells an owner's choice from an editor's, and
  field-level visibility (ACE-474).
- [x] 1.2 Re-verify the premises of `design.md` on `main` HEAD: no `hidden`
  property on the profile model, the editor's profile lookups without
  `$showHidden`, the `null` answer of the image uid resolution for a hidden
  row, and the `skipSync` special field and its gate.

## 2. Owner reaches the own hidden profile

- [x] 2.1 Add the `hidden` property to the profile model of
  `academic_persons`, covered by the functional test of the owner lookup,
  which maps it from the database.
- [x] 2.2 Add an owner lookup that ignores only the `disabled` enable field,
  and use it for the editor's list and the editable profile lookup. Add
  functional tests that the owner lists and opens an own hidden profile, that
  a hidden profile of another owner stays refused, and that an expired profile
  stays unlisted. Show the first fails without the change.
- [x] 2.3 Let the image uid resolution accept the rows of a profile whose
  default record is hidden, and keep refusing a translation hidden on its own.
  Add a functional image upload test on a hidden own profile and show it fails
  without the change.

## 3. The switch

- [x] 3.1 Declare the special field `hidden` in `Settings.yaml` next to
  `skipSync`, with labels and help text in English and German, and render it
  in the header partial, inverted as "Show my profile publicly".
- [x] 3.2 Add the JSON endpoint that accepts exactly one boolean `hidden` and
  writes it through the DataHandler on the default-language row. Add
  functional tests for hide and show, for a malformed payload, for the
  general `update` endpoint refusing `hidden`, and for a `readOnly`, a
  `disabled` and a removed switch that leave the value unchanged. Show the
  gate tests fail without the gate.
- [x] 3.3 Add a functional test with a translated profile, switched off in the
  translated site language, whose default record and translation are both
  hidden afterwards, also without `profile/allowedLanguages`. Show it fails
  with the Extbase write path.
- [x] 3.4 Add a functional frontend test: after the owner switches the profile
  off, the list omits it and the detail answers 404.
- [x] 3.5 Leave the switch's validation out of the TCA merge, with a
  functional test that a `readOnly` switch keeps the backend checkbox
  writable. Show it fails without the change.
- [x] 3.6 Add the switch to the TypeScript editor, rebuild the committed
  JavaScript, and add a `testJs` case per behaviour of the switch.

## 4. Documentation

- [x] 4.1 Document the switch, its default and how an installation takes it
  away, in the `academic_persons_edit` `Documentation/`.
- [x] 4.2 Document the internal directory with "Show hidden records" and its
  two limits in the `academic_persons` `Documentation/`.
- [x] 4.3 Add `Documentation/Changelog/3.0/Feature-ProfileVisibilitySwitch.rst`
  to `academic_persons_edit`, with the migration example for an installation
  with its own consent column, written without naming a project.
- [x] 4.4 Add the owner visibility rule and the write path to `docs/`: the
  endpoint count of `docs/architecture/profile-editing-contract.md`, the TCA
  exception in `docs/architecture/validation-settings.md`, and the write past
  the synchronizer in `docs/architecture/translation-synchronization.md`.

## 5. Issue and commit

- [x] 5.1 Verify ACE-50 with a GET request.
- [x] 5.2 Rename the change to `ace-50-profile-visibility-toggle` and verify
  `openspec validate` passes under the new name.
- [x] 5.3 Commit as `[FEATURE] ACE-50: Let owners hide their profile` in TYPO3
  Core format.

## 6. Definition of done

- [x] 6.1 `composerUpdate -t 13`, then `lintPhp`, `cgl -n`, `phpstan`, `unit`
  and `functional` green for v13, and `functional -d postgres -j 8`.
- [x] 6.2 `composerUpdate -t 14`, then `lintPhp`, `cgl -n`, `phpstan`, `unit`
  and `functional` green for v14, and `functional -d postgres -j 8`.
- [x] 6.3 `lintMarkdown -n` and `checkRstRenderingAll` green, never in parallel
  with `phpstan`.
- [x] 6.4 `docs/` and the `Documentation/` changelog entry are part of the
  commit.
- [x] 6.5 Archive the change as the last commit of the pull request.
