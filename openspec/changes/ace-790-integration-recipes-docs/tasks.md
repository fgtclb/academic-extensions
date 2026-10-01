## 1. Wizard behaviour

- [x] 1.1 Add a functional backend test in `academic-base/Tests/Functional/`
  that builds the new content element wizard and asserts the position of the
  academic group and the order of its items, on v13 and v14. Shown red by
  removing `after = special` from
  `academic-base/Configuration/TSconfig/CTypeGroup/page.tsconfig`, once the
  fixture has an element in a group behind "Special elements".
- [x] 1.2 Extend the test with the relabel, position and hide TSconfig the
  recipe shows, and record which of them take effect on each version:
  element `before`/`after` on v14 only, defining the elements again on both.
  Every test shown red by removing or changing the page TSconfig it covers,
  on v13 and v14.

## 2. Recipes

- [x] 2.1 Create `academic-base/Documentation/Integration/Index.rst` and add
  it to the toctree of `academic-base/Documentation/Index.rst`.
- [x] 2.2 Write the wizard recipe from the findings of 1.2 only: group
  position, relabelling, hiding and item order, without a numbering scheme
  (left to ACE-286).
- [x] 2.3 Write the EXT:solr recipe for EXT:solr 13.1: profile index queue
  with the text columns taken from the profile TCA, the detail link through
  the detail plugin's arguments, and page queues for doktypes 20, 30 and 40.
  Name the release (13.1.4) and check it against its source and the
  configuration of the analysed installations. No v14 section until an
  EXT:solr release for v14 is checked.
- [x] 2.4 Take the link to `b13/permission-sets` from its `composer.json`,
  check the format against the source of release 1.1.0, and write one example
  per extension with its tables, fields, content types and page types, taken
  from the loaded TCA.
- [x] 2.5 Link the chapter from the manuals of `academic_persons`,
  `academic_programs`, `academic_projects` and `academic_partners`.
- [x] 2.6 No `Documentation/Changelog/3.0/` entry for the recipes: nothing
  an installation renders or an integrator configures changes. `docs/`
  changes the test counts only. The pull request says so.

## 3. File the issue

- [x] 3.1 File the ACE issue in YouTrack (ACE-790), rename the change to
  `ace-790-integration-recipes-docs`, and commit in TYPO3 Core format as
  `[TASK] ACE-790: Document integration recipes`.
- [x] 3.2 File what the work found outside its scope: ACE-791 (the partner
  `link` column replaces the core field on TYPO3 v14), ACE-792 (the shipped
  group position moves "Special elements" to the front) and ACE-793 (the
  partner `description` column replaces the core field).

## 4. ACE-792: the shipped group position

- [x] 4.1 Remove `after = special` from
  `academic-base/Configuration/TSconfig/CTypeGroup/page.tsconfig`, keep the
  header.
- [x] 4.2 Test the order without a position (the group after the groups of
  TYPO3), and keep `after = special` as the TSconfig of a page of its own.
  Shown red by
  restoring the shipped position, which fails the order test and the "group
  first with `before`" test.
- [x] 4.3 Rewrite the position section of the wizard recipe, add
  `Important-AcademicGroupComesLastInTheWizard.rst` and the spec
  `academic-base/content-element-wizard-group`.

## 5. ACE-791: the page link on TYPO3 v14

- [x] 5.1 Add the partner column `link` only when TYPO3 defines none.
- [x] 5.2 Test the definition against the TCA file of the core extension, the
  field of TYPO3 on v14 and the column of the extension on v13. Shown red on
  v14 by restoring the old override, and on v13 by removing the column of the
  extension whatever TYPO3 defines.
- [x] 5.3 Add `Important-PageLinkKeepsItsDefinitionOnTypo3V14.rst` and the
  link requirement of `academic-partners/core-page-fields`.

## 6. ACE-793: the page description

- [x] 6.1 Stop defining `description`, and label it for partner pages through
  `columnsOverrides`.
- [x] 6.2 Test the definition of TYPO3 and the partner label. Shown red on v13
  and v14 by restoring the old override.
- [x] 6.3 Add `Breaking-PageDescriptionIsAnExcludeFieldAgain.rst`, list the
  field in the partner permission set, and add the description requirement
  of `academic-partners/core-page-fields`.

## 7. Definition of done

- [ ] 7.1 `lintPhp` green.
- [ ] 7.2 After `-t 13 -s composerUpdate`: `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 13`.
- [ ] 7.3 After `-t 14 -s composerUpdate`: `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 14`.
- [ ] 7.4 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 7.5 `README.md` and `CONTRIBUTING.md` still only summarize and link.
- [ ] 7.6 Archive the change as the last commit of the pull request.
