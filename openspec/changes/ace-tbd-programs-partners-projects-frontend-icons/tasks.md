## 1. Verify the premises

- [ ] 1.1 Confirm on `main` that `ace-tbd-frontend-icon-registry` and
  `ace-tbd-category-type-frontend-icons` are merged: `FrontendIcons.php` is
  read, the icon ViewHelper of `academic_base` renders the `core:icon` markup,
  `frontendIcon`/`frontendInlineIcon` are honoured and a `FrontendIcons.php`
  entry wins over a contributed category type icon. Render an unknown
  identifier through the ViewHelper on both core versions and note whether
  `data-identifier` says `default-not-found` or the requested identifier. If
  any premise does not hold, stop and update this change.
- [ ] 1.2 Grep the seven partials of `design.md` again and confirm that
  `core:icon` is their only use of the core namespace and that no other
  frontend template of the three extensions renders an icon.

## 2. Programs

- [ ] 2.1 Add `academic-programs/Configuration/FrontendIcons.php` with
  `tx-academicprograms-info-credit-points` (provider, source and comment taken
  over) and remove the entry from `Configuration/Icons.php`. Verified by 4.2.
- [ ] 2.2 Switch `Partials/Program/Facts/Item.html` to `xmlns:ab` and
  `<ab:icon identifier="{fact.iconIdentifier}" />`, dropping `xmlns:core`.
  Verified by 4.1 and the unchanged `ProgramFactsTest`.
- [ ] 2.3 Say in the docblocks of `ProgramFactsBuilder::CREDIT_POINTS_ICON` and
  of `ProgramFact` that the identifier is one of the frontend icon registry.
  The value stays.

## 3. Partners and projects

- [ ] 3.1 Switch `Partner/Page/Categories.html`, `Partner/Item.html`,
  `Partnerships/List/Item.html` and `Partnerships/Teaser/Item.html` of
  `academic_partners` to `xmlns:ab` and `<ab:icon … />`, dropping `xmlns:core`.
  Verified by 4.3 and the unchanged `CategoryTypeTitleTest`.
- [ ] 3.2 The same for `Project/Page/Categories.html` and `Project/Item.html`
  of `academic_projects`. Verified by 4.4 and the unchanged
  `CategoryTypeTitleTest`.

## 4. Tests

- [ ] 4.1 Fixture extension `test_programs_frontend_icons` with its own SVG
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
  without the fixture, and show it red by removing the entry of 2.1.
- [ ] 4.2 Move `FactIconsTest` to the frontend registry trait of
  `ace-tbd-frontend-icon-registry` and add an assertion that the core
  `IconRegistry` does not know `tx-academicprograms-info-credit-points`. Show
  the frontend assertions red by moving the entry back to `Icons.php`, and the
  new assertion red by keeping it in both files.
- [ ] 4.3 Fixture extension `test_partners_frontend_icons` (a type with `icon`
  and `frontendIcon`, a `FrontendIcons.php` entry for
  `category_types.partners.region`) and a functional test on both core
  versions over the partner page, the partner card, the partnerships list and
  the partnerships teaser, with a dataset modelled on
  `CategoryTypes/Fixtures/categoryTypeTitle.csv`: frontend markers present,
  backend markers absent, declared `icon` source still in the core registry,
  and the shipped region icon rendered without the fixture. Show it red per
  place by reverting each partial to `core:icon`.
- [ ] 4.4 The same for `academic_projects` with
  `test_projects_frontend_icons` and `competence_field`, over the project page
  and the project card. Show it red per place the same way.
- [ ] 4.5 Run the unchanged `ProgramFactsTest` and both `CategoryTypeTitleTest`
  on both core versions: they pass without an edit, which shows the markup is
  unchanged.

## 5. Documentation

- [ ] 5.1 `academic_programs`: `Documentation/Changelog/3.0/Breaking-CreditPointsIconIsAFrontendIcon.rst`
  (the move, the not-found placeholder for an override on `core:icon`, an
  `Icons.php` replacement no longer reaching the frontend, the migration to
  `FrontendIcons.php` and `<ab:icon>`), and the facts passage of
  `Documentation/Configuration/Index.rst` (credit points icon is a frontend
  icon, how a site package replaces it). Add a reference to the new entry in
  `Feature-ProgramFactsFieldList.rst` where it names the identifier.
- [ ] 5.2 `academic_partners` and `academic_projects`:
  `Documentation/Changelog/3.0/Important-CategoryIconsComeFromTheFrontendIconRegistry.rst`
  each (the partials concerned, rendered icons unchanged, an override on
  `core:icon` keeps the backend drawing, replace in `FrontendIcons.php` or
  through `frontendIcon`).
- [ ] 5.3 Amend the unreleased `Breaking-RecordAndCategoryIconsFollowTheColourScheme.rst`
  of programs and partners and `Breaking-CategoryIconsFollowTheColourScheme.rst`
  of projects: their Impact names the ViewHelper of `academic_base` instead of
  `core:icon`, partners and projects name `Partials/Partner/Page/Categories.html`
  and `Partials/Project/Page/Categories.html` instead of the page templates,
  and the wrapper promise of the Migration stays.
- [ ] 5.4 `docs/architecture/icons.md`: the programs, partners and projects
  rows of the registration and frontend tables, the credit points passage and
  the `FactIconsTest` passage. `docs/testing/fixture-extensions.md`: the three
  new fixture extensions.
- [ ] 5.5 Run `checkRstRenderingAll` and `lintMarkdown -n` green.

## 6. File the issue

- [ ] 6.1 File the ACE issue (Task, version 3.0.0, subtask of ACE-10, relates
  to the frontend icons umbrella and to the issues of the two changes this one
  depends on), verify the key, and rename the change to
  `ace-NNN-programs-partners-projects-frontend-icons`.

## 7. Definition of done

- [ ] 7.1 After `composerUpdate` for TYPO3 v13: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` (`-j auto`) green.
- [ ] 7.2 After `composerUpdate` for TYPO3 v14: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` (`-j auto`) green.
- [ ] 7.3 `docs/` is updated, and `README.md` and `CONTRIBUTING.md` still only
  summarize.
- [ ] 7.4 Commit as `[!!!][TASK] ACE-<NNN>: Frontend icons for facts, types`
  in TYPO3 Core format, with a verified key and no attribution, and archive the
  change as the last commit of the pull request.
