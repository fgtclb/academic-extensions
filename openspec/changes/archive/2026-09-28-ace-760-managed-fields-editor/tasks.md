## 1. Prerequisite

- [x] 1.1 Confirm `ace-758-managed-fields-backend` is merged and its resolver
  is available. Stop otherwise.

## 2. Server side

- [x] 2.1 Let `ManagedFieldResolver` answer the managed property names of a
  profile, contract or contact model from the same checks as its column
  answer. Unit tests for the record kinds, shown red by answering every
  property.
- [x] 2.2 Drop a submitted value of a locked field (managed, `readonly`,
  `frontendreadonly`, `disabled`) in the profile, document and contact
  endpoints and store the rest. Functional tests: a changed synchronised
  e-mail keeps its address and stores its type, a manual e-mail stores its
  address, a contract save with a `frontendreadonly` room stores the other
  fields, a crafted profile title is kept. Shown red with the old refusal
  restored and with an empty managed list.
- [x] 2.3 Pass the managed property names into `mayApplyProperty()` of the
  five factories of records with an import identifier. Factory tests shown
  red by passing an empty list.
- [x] 2.4 Refuse delete of a managed contract or contact and edit of a fully
  managed row with the existing 403 codes, in the form and the write
  endpoints. Functional tests for a synchronised and a manual phone number,
  for an installation without a map, and for hiding a synchronised contact,
  shown red with the check removed.

- [x] 2.5 In a translated site language, decide the delete of a row on its
  default-language record, so a synchronised row cannot be deleted there
  either, and keep a managed field whose column all languages share locked on
  the translation. Resolver tests for overlays and a functional test in
  German, shown red without the change. The older defects of the editor in a
  translated language are filed as ACE-761.

## 3. Rendering

- [x] 3.1 Serialise `readOnly` and the managed marker into the field
  descriptors and the managed, editable and deletable flags into the contact
  items. Render the marker and drop the delete and edit buttons in the
  TypeScript editor, disable a read-only select and checkbox, rebuild with
  `buildJs` and cover it in `testJs`, shown red without the marker.
- [x] 3.2 Mark managed profile fields read-only in the page and filter the
  actions of a managed contract row. Functional rendering test asserts the
  marker and the missing delete button for a synchronised row and their
  absence for a `skip_sync` profile.

## 4. Documentation

- [x] 4.1 Extend `docs/architecture/profile-editing-contract.md` with the
  managed marker, the refused actions and the ignored locked values.
- [x] 4.2 Document the behaviour in `academic-persons-edit/Documentation/` and
  add `Documentation/Changelog/3.0/Feature-EditorHonoursManagedFields.rst`,
  naming the changed answer for a submitted locked value.

## 5. File the issue

- [x] 5.1 File the ACE issue in YouTrack (ACE-760), rename the change to
  `ace-760-managed-fields-editor`, and commit as
  `[FEATURE] ACE-760: Lock managed fields in the editor` in TYPO3 Core
  format.

## 6. Definition of done

- [x] 6.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v13, and the same after its own `composerUpdate` for
  TYPO3 v14. The write tests also with `-d postgres`.
- [x] 6.2 `checkJsBuildClean`, `lintTypescript`, `typecheckJs`, `testJs`,
  `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 6.3 `docs/` and the extension's `Documentation/` changelog updated in the
  same change.
- [x] 6.4 Archive the change as the last commit of the pull request.
