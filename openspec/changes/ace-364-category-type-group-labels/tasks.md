## 1. Loader and registry

- [x] 1.1 Add a fixture package with a `groups:` section and a second one
  redeclaring the group, and loader unit tests asserting the read titles,
  icons and the later declaration winning; record the failure on main.
- [x] 1.2 Add `title` and `icon` to the group model, read and cache the groups
  under their own key, and expose them through the registry; the tests from
  1.1 turn green. A cache round trip test, modelled on the existing type cache
  test, is shown red by skipping the group cache write.

## 2. Backend

- [x] 2.1 Functional TCA test asserting `itemGroups['programs']` of
  `sys_category.type` equals the programs group label and that an undeclared
  group has no entry; record the failure on main.
- [x] 2.2 Register `itemGroups` in
  `typo3-category-types/Configuration/TCA/Overrides/sys_category.php`; the
  test turns green.
- [x] 2.3 Functional test that a declared group icon is registered as
  `category_types.group.<identifier>`, with the provider its `inlineIcon`
  asks for; shown red without the registration in `ServiceProvider`.

## 3. Shipped group icons

- [x] 3.1 Add the group icons `CategoryGroups/Programs.svg`,
  `CategoryGroups/Projects.svg` and `CategoryGroups/Partners.svg` from Font
  Awesome Free 7.3.1 solid, list them in each extension's
  `LICENSE-font-awesome.txt`, and declare the shipped groups with
  `inlineIcon: true`. A functional test asserts that every shipped group icon
  file exists.

## 4. Partners group

- [x] 4.1 Declare the `partners` group with title and icon in
  `academic-partners/Configuration/CategoryTypes.yaml`, add the English and
  German labels, and extend the TCA test with the partners group.

## 5. Documentation

- [x] 5.1 Replace the note that the `groups:` section is not read on
  `typo3-category-types/Documentation/Developers/CategoryTypes/Index.rst`
  (added by ACE-751) with the documentation of the section, including the icon
  identifier scheme, and update the item group statement of
  `Documentation/Developers/TCA/Index.rst` and the group heading assertion of
  `Tests/Functional/Configuration/SysCategoryTypeTest.php`.
- [x] 5.2 Add `typo3-category-types/Documentation/Changelog/3.0/Feature-CategoryTypeGroupTitlesAndIcons.rst`
  and `academic-partners/Documentation/Changelog/3.0/Feature-CategoryTypeGroupTitle.rst`
  from `Build/Documentation/Templates/Changelog-Feature.rst`, and
  `Important-CategoryTypeGroupTitleAndIcon.rst` in `academic-programs` and
  `academic-projects`.
- [x] 5.3 Add the group icon identifiers to `docs/architecture/icons.md`,
  which describes the category type icons.

## 6. Track the issue

- [x] 6.1 Verify ACE-364 in YouTrack, rename the change to
  `ace-364-category-type-group-labels`, and commit in TYPO3 Core format as
  `[FEATURE] ACE-364: Show category type group titles`.

## 7. Definition of done

- [x] 7.1 `lintPhp` green.
- [x] 7.2 After `-t 13 -s composerUpdate`: `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 13`.
- [x] 7.3 After `-t 14 -s composerUpdate`: `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 14`. Nothing is written to the database, so a
  PostgreSQL run is not required.
- [x] 7.4 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 7.5 `docs/` and the `Documentation/Changelog/3.0/` entries are part of
  the change; `README.md` and `CONTRIBUTING.md` still only summarize and
  link.
- [ ] 7.6 Archive the change as the last commit of the pull request.
