## 1. Tests first

- [x] 1.1 Port the `main` tests of the option to
  `Tests/Functional/Plugins/AcademicContacts4PagesListPluginTest.php`, with
  the fixtures `contactsListPage_interleavedRoles.csv` (uids against the
  sorting) and `contactsListPage_withoutRoles.csv`: grouping on, a stored
  FlexForm without the key, grouping off in sorting order, and the role name
  of each contact. Against the unchanged templates on v12, the two tests of
  the option switched off were red (role headings rendered); removing the
  TypoScript default turned the missing-key test red.
- [x] 1.2 Port the item partial override tests with the fixture partial path,
  for the grouped, the flat and the without-role branch. All three were red
  on v12 against the unchanged templates, which call no such partial.
- [x] 1.3 Port the heading level tests of the grouped and the ungrouped
  branch, and add a test that the shipped item partial survives a replaced
  partial root path constant. The heading level tests pin today's output and
  pass on the unchanged templates; dropping `groupedProfiles` from the item
  partial turns the grouped one red. The path test pins core behaviour and has
  no mutation of its own.
- [x] 1.4 Add `Tests/Unit/Configuration/ContactsListFlexFormTest.php` for the
  field and its default. Red on v12 against the unchanged FlexForm.
- [x] 1.5 Show the new tests fail against the unchanged code on v12, as noted
  per task above.

## 2. Implementation

- [x] 2.1 `settings.groupByRole` in `ContactsList.xml`, the labels in both
  XLIFF files, the TypoScript default in `List/setup.typoscript`.
- [x] 2.2 `Resources/Private/Partials/Contacts/Item.html`, and the three
  loops of `List.html` routed through it.

## 3. Documentation

- [x] 3.1 The option and the item partial in
  `Documentation/Configuration/Index.rst`.
- [x] 3.2 `Documentation/Changelog/2.4/Feature-OptionalRoleGroupingAndContactItemPartial.rst`.
- [x] 3.3 `Documentation/Introduction/Index.rst`, which described the grouping
  as unconditional.
- [x] 3.4 `docs/`: no page here covers the contacts templates or overridable
  partials, so nothing to update.

## 4. Definition of done

- [x] 4.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit`,
  `functional` on SQLite and the extension's functional tests on PostgreSQL
  green with `-t 12`.
- [x] 4.2 The same with `-t 13`.
- [x] 4.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 4.4 Commit in TYPO3 Core format, `[FEATURE] ACE-728: <subject>`.
- [x] 4.5 Archive the change as the last commit of the pull request.
