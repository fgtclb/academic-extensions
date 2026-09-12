## 1. Tests first

- [ ] 1.1 Add a plugin fixture to `AcademicContacts4PagesListPluginTest` with
  one page and four contacts: visible profile, hidden profile, hidden
  contract, expired profile; two of them share a role. Assert that only the
  visible contact renders and that no heading renders for a role whose
  contacts are all hidden. Record that it fails on the unchanged code
  (empty cards are rendered).
- [ ] 1.2 Add the same fixture with `settings.showHiddenRecords` enabled plus a
  hidden contact row pointing at a visible profile; assert that contact is
  rendered and the hidden-profile contact is not.
- [ ] 1.3 Add a functional test for the page contacts data processor that
  processes a page with the fixture of 1.1 and asserts the processed
  `contacts` and `roles`. Record that it fails on the unchanged processor.

## 2. Implementation

- [ ] 2.1 Add `PageContactsProvider` and the `PageContacts` value object as
  `final readonly` classes; verify with a functional test of the provider
  that the role split matches the shown contacts.
- [ ] 2.2 Use the provider in `ContactsController::listAction()`, keep the
  address record provider handling, and verify tests 1.1 and 1.2 pass on v13
  and v14.
- [ ] 2.3 Make the processor a public service with the provider injected and
  verify test 1.3 passes, and that the existing repository and site set tests
  stay green.

## 3. Documentation

- [ ] 3.1 Add `Documentation/Changelog/3.0/Important-ContactsWithoutVisibleProfileAreSkipped.rst`
  to `academic_contacts4pages`, covering the hidden-profile rule, the
  unchanged meaning of the show hidden records option, and the `contacts`
  value becoming a list; verify it renders.
- [ ] 3.2 Update the plugin and processor chapter of the extension's
  `Documentation/` and add the shared-provider decision to `docs/` where the
  contacts4pages behaviour is described, linked from its `Index.md`.

## 4. File the issue

- [ ] 4.1 After implementation, confirm with the maintainer whether the change
  is carried by ACE-101 or by a new issue; rename the change to
  `ace-<NNN>-contacts-skip-unresolved-profiles` with that key and commit in
  TYPO3 Core format, e.g. `[BUGFIX] ACE-<NNN>: Skip contacts with hidden
  profile`.

## 5. Backport

- [ ] 5.1 Backport: separate change on branch `2` after a backport analysis
  (`docs/workflow/backporting.md`), re-derived from a diff of the contact
  repository, the controller and the processor on branch `2`; not a
  cherry-pick.

## 6. Definition of done

- [ ] 6.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v13, all green.
- [ ] 6.2 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v14, all green.
- [ ] 6.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 6.4 `docs/` and the extension's `Documentation/` changelog updated in the
  same change.
- [ ] 6.5 Archive the change as the last commit of the pull request.
