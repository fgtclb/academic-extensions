## 1. Loader and registry

- [ ] 1.1 Add a fixture package with a `groups:` section and a second one
  redeclaring the group, and loader unit tests asserting the read titles,
  icons and the later declaration winning; record the failure on main.
- [ ] 1.2 Add `title` and `icon` to the group model, read and cache the groups
  under their own key, and expose them through the registry; the tests from
  1.1 turn green. A cache round trip test, modelled on the existing type cache
  test, is shown red by skipping the group cache write.

## 2. Backend

- [ ] 2.1 Functional TCA test asserting `itemGroups['programs']` of
  `sys_category.type` equals the programs group label and that an undeclared
  group has no entry; record the failure on main.
- [ ] 2.2 Register `itemGroups` in
  `typo3-category-types/Configuration/TCA/Overrides/sys_category.php`; the
  test turns green.
- [ ] 2.3 Functional test that `category_types.group.programs` is a registered
  icon; shown red without the registration in `ServiceProvider`.

## 3. Partners group

- [ ] 3.1 Declare the `partners` group with title and icon in
  `academic-partners/Configuration/CategoryTypes.yaml`, add the English and
  German labels, and extend the TCA test with the partners group.

## 4. Documentation

- [ ] 4.1 Document the `groups:` section on the `CategoryTypes.yaml` page of
  `category_types` (from `ace-tbd-category-types-yaml-docs`, or its TCA page
  if that has not landed), including the icon identifier scheme.
- [ ] 4.2 Add `typo3-category-types/Documentation/Changelog/3.0/Feature-CategoryTypeGroupTitlesAndIcons.rst`
  and `academic-partners/Documentation/Changelog/3.0/Feature-CategoryTypeGroupTitle.rst`
  from `Build/Documentation/Templates/Changelog-Feature.rst`.
- [ ] 4.3 Add the group icon identifiers to `docs/architecture/icons.md`,
  which describes the category type icons.

## 5. Track the issue

- [ ] 5.1 Verify ACE-364 in YouTrack, rename the change to
  `ace-364-category-type-group-labels`, and commit in TYPO3 Core format as
  `[FEATURE] ACE-364: Show category type group titles`.

## 6. Definition of done

- [ ] 6.1 `lintPhp` green.
- [ ] 6.2 After `-t 13 -s composerUpdate`: `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 13`.
- [ ] 6.3 After `-t 14 -s composerUpdate`: `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 14`. Nothing is written to the database, so a
  PostgreSQL run is not required.
- [ ] 6.4 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 6.5 `docs/` and the `Documentation/Changelog/3.0/` entries are part of
  the change; `README.md` and `CONTRIBUTING.md` still only summarize and
  link.
- [ ] 6.6 Archive the change as the last commit of the pull request.
