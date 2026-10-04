## 1. Verify the premises

- [x] 1.1 Confirm on `main` that `ace-810-frontend-icon-registry` and
  `ace-811-category-type-frontend-icons` are merged: `FrontendIcons.php` is
  read, the icon ViewHelper of `academic_base` renders the `core:icon` markup,
  `frontendIcon`/`frontendInlineIcon` are honoured and a `FrontendIcons.php`
  entry wins over a contributed category type icon. Render an unknown
  identifier through the ViewHelper on both core versions and note whether
  `data-identifier` says `default-not-found` or the requested identifier. If
  any premise does not hold, stop and update this change. Done: all premises
  hold on both cores. `FrontendIconFactory::getIcon()` replaces an unknown
  identifier with `default-not-found`, so the wrapper says
  `data-identifier="default-not-found"`, and `ProgramFactsTest` goes red when
  the entry of 2.1 is missing (shown on both cores).
- [x] 1.2 Grep the seven partials of `design.md` again and confirm that
  `core:icon` is their only use of the core namespace and that no other
  frontend template of the three extensions renders an icon. Done: the seven
  partials use `ct:` and `f:` besides `core:icon`, nothing else of the core
  namespace. The partner map draws Leaflet marker images, not registry
  icons.

## 2. Programs

- [x] 2.1 Add `academic-programs/Configuration/FrontendIcons.php` with
  `tx-academicprograms-info-credit-points` (provider, source and comment taken
  over) and remove the entry from `Configuration/Icons.php`. Verified by 4.2.
- [x] 2.2 Switch `Partials/Program/Facts/Item.html` to `xmlns:ab` and
  `<ab:icon identifier="{fact.iconIdentifier}" />`, dropping `xmlns:core`.
  Verified by 4.1 and the unchanged `ProgramFactsTest`.
- [x] 2.3 Say in the docblocks of `ProgramFactsBuilder::CREDIT_POINTS_ICON` and
  of `ProgramFact` that the identifier is one of the frontend icon registry.
  The value stays.

## 3. Partners and projects

- [x] 3.1 Switch `Partner/Page/Categories.html`, `Partner/Item.html`,
  `Partnerships/List/Item.html` and `Partnerships/Teaser/Item.html` of
  `academic_partners` to `xmlns:ab` and `<ab:icon … />`, dropping `xmlns:core`.
  Verified by 4.3 and the unchanged `CategoryTypeTitleTest`.
- [x] 3.2 The same for `Project/Page/Categories.html` and `Project/Item.html`
  of `academic_projects`. Verified by 4.4 and the unchanged
  `CategoryTypeTitleTest`.

## 4. Tests

- [x] 4.1 Fixture extension `test_programs_frontend_icons` with its own SVG
  files, each carrying a distinct marker: `FrontendIcons.php` entries for the
  credit points icon and `category_types.programs.degree`, an `Icons.php`
  entry for the credit points icon with another drawing, and a
  `CategoryTypes.yaml` type of the group `programs` with `icon` and
  `frontendIcon`. A functional test on both core versions asserts the
  frontend markers of credit points, degree and the new type on the program
  page, in the details element and on the card (`card.fields` set), the
  absence of the backend markers, and the declared `icon` source of the new
  type in the core registry. Show it red by reverting `Facts/Item.html` to
  `core:icon`. If 1.1 found the requested identifier in the not-found
  `data-identifier`, also assert the shipped drawing of the credit points icon
  without the fixture, and show it red by removing the entry of 2.1. Done as
  `ProgramFactsFrontendIconsTest`, which also asserts the shipped drawing of
  `standard_period`, a type nobody replaces, and the backend sources of the
  new type, the degree and the credit points icon of the fixture's
  `Icons.php`. The conditional part does not apply, see 1.1.
- [x] 4.2 Move `FactIconsTest` to the frontend registry trait of
  `ace-810-frontend-icon-registry` and add an assertion that the core
  `IconRegistry` does not know `tx-academicprograms-info-credit-points`. Show
  the frontend assertions red by moving the entry back to `Icons.php`, and the
  new assertion red by keeping it in both files. Done, plus the inverse for
  the page type icon `academic-programs`, shown red by registering it in
  `FrontendIcons.php`.
- [x] 4.3 Fixture extension `test_partners_frontend_icons` (a type with `icon`
  and `frontendIcon`, a `FrontendIcons.php` entry for
  `category_types.partners.region`) and a functional test on both core
  versions over the partner page, the partner card, the partnerships list and
  the partnerships teaser, with a dataset modelled on
  `CategoryTypes/Fixtures/categoryTypeTitle.csv`: frontend markers present,
  backend markers absent, declared `icon` source still in the core registry,
  and the shipped region icon rendered without the fixture. Show it red per
  place by reverting each partial to `core:icon`. Done as
  `CategoryTypeFrontendIconsTest`. The shipped icon nobody replaces is the one
  of `partner_type` in the same instance, not the region in a second instance
  without the fixture, and the scenario of the spec names `partner_type`
  accordingly. Each partial reverted to `core:icon` turns exactly the two
  cases of its place red.
- [x] 4.4 The same for `academic_projects` with
  `test_projects_frontend_icons` and `competence_field`, over the project page
  and the project card. Show it red per place the same way. Done, with
  `cooperation` as the shipped type nobody replaces.
- [x] 4.5 Run the unchanged `ProgramFactsTest` and both `CategoryTypeTitleTest`
  on both core versions: they pass without an edit, which shows the markup is
  unchanged. Done, alone and in the full functional suites on v13 and v14. On
  top, every page the suites of the three extensions render was compared
  before and after the change on both cores: 771 pages with 1048 category type
  and credit points icons, byte identical.

## 5. Documentation

- [x] 5.1 `academic_programs`: `Documentation/Changelog/3.0/Breaking-CreditPointsIconIsAFrontendIcon.rst`
  (the move, the not-found placeholder for an override on `core:icon`, an
  `Icons.php` replacement no longer reaching the frontend, the migration to
  `FrontendIcons.php` and `<ab:icon>`), and the facts passage of
  `Documentation/Configuration/Index.rst` (credit points icon is a frontend
  icon, how a site package replaces it). Add a reference to the new entry in
  `Feature-ProgramFactsFieldList.rst` where it names the identifier.
- [x] 5.2 `academic_partners` and `academic_projects`:
  `Documentation/Changelog/3.0/Important-CategoryIconsComeFromTheFrontendIconRegistry.rst`
  each (the partials concerned, rendered icons unchanged, an override on
  `core:icon` keeps the backend drawing, replace in `FrontendIcons.php` or
  through `frontendIcon`).
- [x] 5.3 Amend the unreleased `Breaking-RecordAndCategoryIconsFollowTheColourScheme.rst`
  of programs and partners and `Breaking-CategoryIconsFollowTheColourScheme.rst`
  of projects: their Impact names the ViewHelper of `academic_base` instead of
  `core:icon`, partners and projects name `Partials/Partner/Page/Categories.html`
  and `Partials/Project/Page/Categories.html` instead of the page templates,
  and the wrapper promise of the Migration stays.
- [x] 5.4 `docs/architecture/icons.md`: the programs, partners and projects
  rows of the registration and frontend tables, the credit points passage and
  the `FactIconsTest` passage. `docs/testing/fixture-extensions.md`: the three
  new fixture extensions. Done, together with `docs/architecture/program-facts.md`,
  `docs/testing/testing-helper.md` and the unreleased Feature entries of
  `academic_base` and `category_types`, which still named the three extensions
  as rendering with `core:icon`.
- [x] 5.5 Run `checkRstRenderingAll` and `lintMarkdown -n` green.

## 6. File the issue

- [x] 6.1 File the ACE issue (Task, version 3.0.0, subtask of ACE-10, relates
  to the frontend icons umbrella and to the issues of the two changes this one
  depends on), verify the key, and rename the change to
  `ace-NNN-programs-partners-projects-frontend-icons`. Done: ACE-814.

## 7. Definition of done

- [x] 7.1 After `composerUpdate` for TYPO3 v13: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` (`-j auto`) green.
- [x] 7.2 After `composerUpdate` for TYPO3 v14: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` (`-j auto`) green.
- [x] 7.3 `docs/` is updated, and `README.md` and `CONTRIBUTING.md` still only
  summarize.
- [x] 7.4 Commit as `[!!!][TASK] ACE-<NNN>: Frontend icons for facts, types`
  in TYPO3 Core format, with a verified key and no attribution, and archive the
  change as the last commit of the pull request.
