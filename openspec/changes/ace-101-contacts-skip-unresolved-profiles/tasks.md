## 1. Tests first

- [x] 1.1 Add `AcademicContacts4PagesListPluginTest` with a baseline and a
  fixture of one page with four contacts: visible profile, hidden profile,
  hidden contract, expired profile; two share a role. Assert the card count
  and that no heading renders for a role whose contacts are all hidden.
  Record the failure on the unchanged code on v12 and v13.
- [x] 1.2 Add the same fixture with `settings.showHiddenRecords` enabled plus
  a hidden contact row pointing at a visible profile; assert two cards and
  record the failure on v12 and v13.
- [x] 1.3 Add a processor test driven through a frontend request with the
  shipped `page.10.dataProcessing.400` registration, asserting the count and
  the roles; record the failure on v12 and v13.

## 2. Implementation

- [x] 2.1 Add `PageContactsProvider` and `PageContacts` as `final` classes
  with `readonly` properties; verify with a provider test that the role split
  matches the shown contacts.
- [x] 2.2 Use the provider in `ContactsController::listAction()`, keeping the
  address record provider handling; verify 1.1 and 1.2 on v12 and v13.
- [x] 2.3 Publish the processor with the provider injected; verify 1.3, and
  on v12 that removing the publication makes it fail.

## 3. Documentation

- [x] 3.1 Add `Documentation/Changelog/2.4/Important-ContactsWithoutVisibleProfileAreSkipped.rst`
  and a paragraph in the extension's `Introduction` chapter.
- [x] 3.2 Add the published data processor section to
  `docs/architecture/dependency-injection.md` and update the strict types
  figures of `docs/architecture/class-design.md` measured on this branch.

## 4. Definition of done

- [x] 4.1 `-t 12 -p 8.1`: `composerUpdate`, `lintPhp`, `cgl -n`, `phpstan`,
  `unit` and `functional` all green.
- [x] 4.2 `-t 13 -p 8.2`: `composerUpdate`, `lintPhp`, `cgl -n`, `phpstan`,
  `unit` and `functional` all green.
- [x] 4.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 4.4 Commit in TYPO3 Core format with ACE-101 and archive the change as
  the last commit of the pull request.
