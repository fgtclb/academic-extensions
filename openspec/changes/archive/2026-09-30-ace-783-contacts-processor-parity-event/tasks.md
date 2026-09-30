## 1. Tests first

- [x] 1.1 Extend `Tests/Functional/DataProcessing/ContactsProcessorTest.php`,
  which renders a page with a fixture page template that prints the processed
  variables. Assert the contact without role in `contactsWithoutRole`, the
  `as = pageContacts` shape, the hidden contact and its hidden address record
  with `showHiddenRecords = 1`, the contacts of another page with `pageUid`,
  an option given through stdWrap, and the processor on a content element
  with and without `pageUid`. Record that each case fails against the
  unchanged processor.
- [x] 1.2 Add a fixture extension below `Tests/Functional/Fixtures/Extensions/`
  (psr-4 autoload, then `composerUpdate`) with a listener that removes one
  contact, driven by TypoScript for both outputs or the page output only, and
  by the FlexForm of one of two content elements through its plugin context.
  Assert the plugin and page output accordingly, including the dropped role.
  Record that it fails today, where no event is dispatched.
- [x] 1.3 Add a case that wires the processor by class name, as installations
  do, and assert the same output as by identifier.
- [x] 1.4 Add a unit test for the setter of the event, which rejects anything
  but a list of contacts.
- [x] 1.5 Render the content element after and inside the page template with
  hidden records shown by one output only, and assert that the option of one
  output never reaches the other.

## 2. Implementation

- [x] 2.1 Add `ModifyPageContactsEvent` and the `PageContactsOutput` enum,
  dispatch the event in `PageContactsProvider`, derive roles and contacts
  without role after it, and hand the address record provider to copies of
  the contacts there instead of in the controller. Verify that tasks 1.2 and
  1.5 pass.
- [x] 2.2 Rewrite the processor with the three options, tag it with the
  identifier `academic-page-contacts` in `Configuration/Services.yaml` and
  switch `Configuration/TypoScript/List/setup.typoscript`. Verify that tasks
  1.1 and 1.3 pass.
- [x] 2.3 Hand the plugin action context and the `Plugin` output from the
  controller to the provider. Verify that the existing plugin tests pass
  unchanged on v13 and v14.
- [x] 2.4 List the event and the enum on the extension points page of
  `academic_base` and tag both `@api`.

## 3. Documentation

- [x] 3.1 Document the processor options, the variables and attaching the
  processor to further page objects in
  `packages/fgtclb/academic-contact4pages/Documentation/Configuration/Index.rst`,
  and the event in a new `Documentation/Developers/Index.rst` of the
  extension.
- [x] 3.2 Add `Documentation/Changelog/3.0/Feature-PageContactsProcessorOptionsAndEvent.rst`
  and verify the `3.0` index lists it.
- [x] 3.3 Describe the shared provider and its event in a page of
  `docs/architecture/`, linked from `docs/architecture/Index.md`, and bring
  the data processor section of `docs/architecture/dependency-injection.md`,
  the enums of `class-design.md` and the fixture extensions of
  `docs/testing/fixture-extensions.md` up to date.

## 4. File the issue

- [x] 4.1 File the ACE issue in YouTrack (ACE-783), verify the key and
  rename the change to `ace-783-contacts-processor-parity-event`.
- [x] 4.2 Commit in TYPO3 Core format `[FEATURE] ACE-783: <subject>`,
  subject at most 52 characters, body wrapped at 72.

## 5. Definition of done

- [x] 5.1 `Build/Scripts/runTests.sh -t 13 -s composerUpdate`, then
  `lintPhp`, `cgl -n`, `phpstan`, `unit` and `functional` with `-t 13` green,
  functional on SQLite and PostgreSQL.
- [x] 5.2 The same for `-t 14` after its own `composerUpdate`.
- [x] 5.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 5.4 `docs/` and the `Documentation/` changelog entry are part of the
  change, and `README.md` and `CONTRIBUTING.md` still only summarize and link.
- [x] 5.5 Archive the change as the last commit of the pull request and
  verify the delta spec landed in `openspec/specs/`.
