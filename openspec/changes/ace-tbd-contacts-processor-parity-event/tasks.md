## 1. Tests first

- [ ] 1.1 Add `Tests/Functional/DataProcessing/ContactsProcessorTest.php`,
  rendering a page with a fixture page template that prints the processed
  variables. Assert the contact without role in `contactsWithoutRole`, the
  `as = pageContacts` shape, the hidden contact with `showHiddenRecords = 1`
  and the contacts of another page with `pageUid`. Record that each case fails
  against the unchanged processor.
- [ ] 1.2 Add a fixture extension below `Tests/Functional/Fixtures/Extensions/`
  (psr-4 autoload, then `composerUpdate`) with a listener that removes one
  contact, once for both contexts and once for the page output only. Assert
  the plugin and page output accordingly, including the dropped role. Record
  that it fails today, where no event is dispatched.
- [ ] 1.3 Add a case that wires the processor by class name, as installations
  do, and assert the same output as by identifier.

## 2. Implementation

- [ ] 2.1 Add `ModifyPageContactsEvent` and the `PageContactsContext` enum,
  dispatch the event in the page contacts provider of `listings-02` and
  derive roles and contacts without role after it; verify task 1.2 passes.
- [ ] 2.2 Rewrite the processor onto the provider with the three options,
  register it by identifier in `Configuration/Services.yaml` and switch
  `Configuration/TypoScript/List/setup.typoscript`; verify tasks 1.1 and 1.3.
- [ ] 2.3 Move the controller onto the provider with the `Plugin` context;
  verify the existing plugin tests pass unchanged on v13 and v14.

## 3. Documentation

- [ ] 3.1 Document the processor options, the variables, attaching the
  processor to further page objects, and the event in
  `packages/fgtclb/academic-contact4pages/Documentation/Configuration/Index.rst`.
- [ ] 3.2 Add `Documentation/Changelog/3.0/Feature-PageContactsProcessorOptionsAndEvent.rst`
  and verify the `3.0` index lists it.
- [ ] 3.3 Describe the shared provider and its event in a section of
  `docs/architecture/`, linked from `docs/architecture/Index.md`.

## 4. File the issue

- [ ] 4.1 After implementation, file the ACE issue in YouTrack, verify the key
  and rename the change to `ace-<NNN>-contacts-processor-parity-event`.
- [ ] 4.2 Commit in TYPO3 Core format `[FEATURE] ACE-<NNN>: <subject>`,
  subject at most 52 characters, body wrapped at 72.

## 5. Definition of done

- [ ] 5.1 `Build/Scripts/runTests.sh -t 13 -s composerUpdate`, then
  `lintPhp`, `cgl -n`, `phpstan`, `unit` and `functional` with `-t 13` green.
- [ ] 5.2 The same for `-t 14` after its own `composerUpdate`.
- [ ] 5.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 5.4 `docs/` and the `Documentation/` changelog entry are part of the
  change; `README.md` and `CONTRIBUTING.md` still only summarize and link.
- [ ] 5.5 Archive the change as the last commit of the pull request and
  verify the delta spec landed in `openspec/specs/`.
