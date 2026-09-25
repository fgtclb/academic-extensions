## 1. Tests first

- [x] 1.1 Extend `Tests/Functional/Plugins/AcademicContacts4PagesListPluginTest.php`
  with a content element with `settings.groupByRole = 0`: assert no role
  heading, all contacts in sorting order, and the role name next to the
  contact that has one. The new `contactsListPage_interleavedRoles.csv`
  fixture is used instead of `contactsListPage_mixedRoles.csv`, which has one
  role on the page and contacts already sorted by role, so the grouped and the
  sorting order cannot differ there. Its uids run against the sorting, so
  neither order can come out of uid order by accident. Fails against the
  unchanged code, which renders the headings.
- [x] 1.2 Add a case with a stored FlexForm that lacks the key, asserting the
  grouped output, so the TypoScript default is pinned. Removing the default
  turned it red, together with three existing grouped-output tests.
- [x] 1.3 Add a fixture partial path with its own `Contacts/Item.html` and
  assert it renders in the grouped, the ungrouped and the without-role
  branch. Fails today, where no such partial is called.
- [x] 1.4 Add a fixture list template that renders the core header partial
  and prints the uid of `record`, with a fixture layout for the element that
  leaves the header out, and assert the header renders exactly once, on v13
  and v14. Fails today, where `record` is not assigned, and with the layout
  of `EXT:fluid_styled_content`, which renders the header a second time.

## 2. Implementation

- [x] 2.1 Add `settings.groupByRole` to `Configuration/FlexForms/ContactsList.xml`
  with labels in `locallang_be.xlf` and `de.locallang_be.xlf`, and the
  TypoScript default in `Configuration/TypoScript/List/setup.typoscript`;
  verify with `Tests/Functional/Tca/PluginFlexFormTest.php`.
- [x] 2.2 Add `Resources/Private/Partials/Contacts/Item.html` and route all
  three loops of `List.html` through it; verify that the existing plugin
  tests still pass unchanged, which proves the default markup is the same.
- [x] 2.3 Assign `record` in the controller through
  `GetCurrentContentRecordMethodTrait`; verify task 1.4 passes on v13 and v14.

## 3. Documentation

- [x] 3.1 Document the option and the item partial with its arguments in
  `packages/fgtclb/academic-contact4pages/Documentation/Configuration/Index.rst`.
- [x] 3.2 Add `Documentation/Changelog/2.4/Feature-OptionalRoleGroupingAndContactItemPartial.rst`
  (2.4 rather than 3.0, see design.md) and
  `Documentation/Changelog/3.0/Important-PluginAssignsRecordViewVariable.rst`,
  the latter in the shape of the jobs entry about the same variable.
- [x] 3.3 Grep `docs/` for statements about the contacts list template and
  update them: `docs/architecture/content-element-rendering.md` (which
  controllers assign `record`) and `docs/architecture/overridable-partials.md`
  (the item partial, and why overriding key `10` does not lose it), and the
  extension's `Documentation/Introduction/Index.rst`, which described the
  grouping as unconditional.

## 4. File the issue

- [x] 4.1 After implementation, file the ACE issue in YouTrack, verify the key
  and rename the change to `ace-<NNN>-contacts-group-by-role-item-partial`
  (ACE-728).
- [x] 4.2 Commit in TYPO3 Core format `[FEATURE] ACE-<NNN>: <subject>`,
  subject at most 52 characters, body wrapped at 72.

## 5. Definition of done

- [x] 5.1 `Build/Scripts/runTests.sh -t 13 -s composerUpdate`, then
  `lintPhp`, `cgl -n`, `phpstan`, `unit` and `functional` with `-t 13` green.
- [x] 5.2 The same for `-t 14` after its own `composerUpdate`.
- [x] 5.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 5.4 `docs/` and the `Documentation/` changelog entries are part of the
  change; `README.md` and `CONTRIBUTING.md` still only summarize and link.
- [x] 5.5 Archive the change as the last commit of the pull request and
  verify the delta spec landed in `openspec/specs/`.
