## Why

`ace-810-frontend-icon-registry` adds a frontend icon registry and its icon
ViewHelper to `academic_base`, and `ace-811-category-type-frontend-icons`
registers every category type icon in it. Seven frontend partials of three
extensions still render their icons from the backend icon registry of TYPO3
through `core:icon`, so a frontend icon a site package registers never reaches
them, and the credit points icon of the program facts sits in the backend
registry although no backend view shows it.

## What Changes

- `academic_programs` (`packages/fgtclb/academic-programs`): the credit points
  icon `tx-academicprograms-info-credit-points` moves from
  `Configuration/Icons.php` to a new `Configuration/FrontendIcons.php` and is
  no longer registered in the backend registry. The identifier and the value of
  the facts builder constant stay. **BREAKING**: an override still rendering it
  with `core:icon` shows the not-found placeholder, and a replacement in a
  site package's `Icons.php` no longer reaches the frontend.
- `Partials/Program/Facts/Item.html` renders the fact icons (credit points and
  category types) with the ViewHelper of `academic_base`.
- `academic_partners` (`packages/fgtclb/academic-partners`): the partner page,
  partner card, partnerships list and partnerships teaser partials render the
  category type icons with that ViewHelper. No icon moves.
- `academic_projects` (`packages/fgtclb/academic-projects`): the project page
  and project card partials do the same. No icon moves.
- The rendered markup does not change, so site styles and the existing
  `data-identifier` assertions keep working.
- TYPO3 v13 and v14 behave the same.

## Non-goals

- Renaming any identifier. The icon consolidation (#617) renames later, in
  both registries.
- Registering the category type icons, the `frontendIcon` option and the not-found
  answer: they belong to the two changes this one builds on.
- The backend page module summary of `category_types`, which stays on
  `core:icon`.
- A leaner frontend markup.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `academic-programs/program-facts`: the credit points icon is a frontend icon
  a site package replaces in its frontend icons, and the category type facts
  show the frontend icon of their type.
- `academic-partners/partner-page`: the partner page, card and partnerships
  show the frontend icon of each category type.
- `academic-projects/project-page`: the project page and card show the frontend
  icon of each category type.

## Impact

- Code: one new `FrontendIcons.php`, one shrunk `Icons.php`, seven partials,
  docblocks of the facts builder and the fact model, `FactIconsTest`, new
  functional tests with three fixture extensions.
- Documentation: a Breaking entry for programs, an Important entry each for
  partners and projects, the three unreleased colour scheme entries of 3.0
  amended (they name `core:icon` and the wrong partial), the facts section of
  the programs configuration chapter, `docs/architecture/icons.md`,
  `docs/testing/fixture-extensions.md`.
- An integrator moves a replaced credit points icon from `Icons.php` to
  `FrontendIcons.php` of the site package, and switches an override of
  `Partials/Program/Facts/Item.html` to the ViewHelper. An override of a partner
  or project partial on `core:icon` keeps rendering the backend icon of the
  type and is switched to see a frontend only replacement. An analysed project
  that registers its own credit points identifier for its own override is not
  affected.
- Depends on `ace-810-frontend-icon-registry` and
  `ace-811-category-type-frontend-icons`. 3.0.0, `main` only.
