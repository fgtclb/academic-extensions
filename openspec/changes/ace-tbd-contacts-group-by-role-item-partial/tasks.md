## 1. Tests first

- [ ] 1.1 Extend `Tests/Functional/Plugins/AcademicContacts4PagesListPluginTest.php`
  with the `contactsListPage_mixedRoles.csv` fixture and a content element
  with `settings.groupByRole = 0`: assert no role heading, all contacts in
  sorting order, and the role name next to the contact that has one. Record
  that it fails against the unchanged code, which renders the headings.
- [ ] 1.2 Add a case with a stored FlexForm that lacks the key, asserting the
  grouped output, so the TypoScript default is pinned. Remove the default on
  purpose once, watch the case go red, restore.
- [ ] 1.3 Add a fixture partial path with its own `Contacts/Item.html` and
  assert it renders in the grouped, the ungrouped and the without-role
  branch. Record that it fails today, where no such partial is called.
- [ ] 1.4 Add a fixture list template that prints the uid of `record`, and
  assert it on v13 and v14. Record that it fails today, where `record` is
  not assigned.

## 2. Implementation

- [ ] 2.1 Add `settings.groupByRole` to `Configuration/FlexForms/ContactsList.xml`
  with labels in `locallang_be.xlf` and `de.locallang_be.xlf`, and the
  TypoScript default in `Configuration/TypoScript/List/setup.typoscript`;
  verify with `Tests/Functional/Tca/PluginFlexFormTest.php`.
- [ ] 2.2 Add `Resources/Private/Partials/Contacts/Item.html` and route all
  three loops of `List.html` through it; verify that the existing plugin
  tests still pass unchanged, which proves the default markup is the same.
- [ ] 2.3 Assign `record` in the controller through
  `GetCurrentContentRecordMethodTrait`; verify task 1.4 passes on v13 and v14.

## 3. Documentation

- [ ] 3.1 Document the option and the item partial with its arguments in
  `packages/fgtclb/academic-contact4pages/Documentation/Configuration/Index.rst`.
- [ ] 3.2 Add `Documentation/Changelog/3.0/Feature-OptionalRoleGroupingAndContactItemPartial.rst`
  and `Important-PluginsAssignRecordViewVariable.rst`, the latter in the shape
  of the jobs and bite jobs entries of the same name; verify the `3.0` index
  lists both.
- [ ] 3.3 Grep `docs/` for statements about the contacts list template and
  update them; no new page is expected.

## 4. File the issue

- [ ] 4.1 After implementation, file the ACE issue in YouTrack, verify the key
  and rename the change to `ace-<NNN>-contacts-group-by-role-item-partial`.
- [ ] 4.2 Commit in TYPO3 Core format `[FEATURE] ACE-<NNN>: <subject>`,
  subject at most 52 characters, body wrapped at 72.

## 5. Definition of done

- [ ] 5.1 `Build/Scripts/runTests.sh -t 13 -s composerUpdate`, then
  `lintPhp`, `cgl -n`, `phpstan`, `unit` and `functional` with `-t 13` green.
- [ ] 5.2 The same for `-t 14` after its own `composerUpdate`.
- [ ] 5.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 5.4 `docs/` and the `Documentation/` changelog entries are part of the
  change; `README.md` and `CONTRIBUTING.md` still only summarize and link.
- [ ] 5.5 Archive the change as the last commit of the pull request and
  verify the delta spec landed in `openspec/specs/`.
