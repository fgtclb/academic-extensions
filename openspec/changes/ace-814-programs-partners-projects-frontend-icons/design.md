## Context

See `proposal.md` for the motivation. This change applies the API of
`ace-810-frontend-icon-registry` (`Configuration/FrontendIcons.php`, the
`IconViewHelper` of `academic_base` under
`http://typo3.org/ns/FGTCLB/AcademicBase/ViewHelpers`, markup byte identical
to `core:icon`, an unknown identifier answered with core's `default-not-found`
drawing) and relies on `ace-811-category-type-frontend-icons` registering every
`category_types.<group>.<type>` icon in both registries, with `frontendIcon`
and `frontendInlineIcon` in `CategoryTypes.yaml` and a `FrontendIcons.php`
entry winning over the contributed one. On `main` (verified 2026-10-03):

- `academic-programs/Configuration/Icons.php` registers `academic-programs`
  (page type, plugins, wizard) and `tx-academicprograms-info-credit-points`,
  both with `CurrentColorSvgIconProvider`. The credit points icon has no backend
  consumer. `ProgramFactsBuilder::CREDIT_POINTS_ICON` holds the identifier
  (`:42`, used at `:94`), `ProgramFact::forCategoryType()` builds
  `category_types.programs.<type>` (`:39`).
- `Partials/Program/Facts/Item.html:20` renders both kinds through
  `<core:icon identifier="{fact.iconIdentifier}" />`, for the program page,
  the details element and the card.
- Partners: `Partner/Page/Categories.html:19`, `Partner/Item.html:29`,
  `Partnerships/List/Item.html:30`, `Partnerships/Teaser/Item.html:30`.
  Projects: `Project/Page/Categories.html:19`, `Project/Item.html:42`. All six
  build `category_types.<group>.{type}` in Fluid. Partners registers three
  backend only icons, projects none.
- The seven partials declare `xmlns:core` for `core:icon` only, none uses
  another core ViewHelper. None declares the `academic_base` namespace, which
  only `academic_base`'s own partials use, with the prefix `p`.
- Frontend tests matching the wrapper: `ProgramFactsTest`
  (`data-identifier="tx-academicprograms-info-credit-points"`, `:53`), the
  `CategoryTypeTitleTest` of partners (`:224`) and projects (`:171`).
  `FactIconsTest` asserts the credit points icon in the core registry through
  `ColourSchemeAwareIconsTrait`.
- The fixture types `funding_body` of `test_partners_titled_category_type` and
  `test_projects_titled_category_type`, and the extra program fixture types,
  name `EXT:core/.../apps/apps-pagetree-folder-contains-category.svg`, which
  exists on neither core. Their icons render empty today, unnoticed because
  the tests only anchor on the wrapper.

## Goals / Non-Goals

**Goals:**

- Every icon these three extensions render in the frontend comes from the
  frontend registry, with unchanged markup.
- The one frontend only icon of the three lives in `FrontendIcons.php` only.

**Non-Goals:**

- Renaming identifiers, sharing the identifier derivation (`ProgramFact`, six
  templates), or the page module summary, a backend view.

## Decisions

### The credit points icon goes to `FrontendIcons.php` only

It is removed from `Icons.php` and added to a new
`academic-programs/Configuration/FrontendIcons.php` with the same provider,
source and identifier, and with its comment. That is the rule of the round: a
frontend only icon is registered for the frontend only.

Rejected: registering it in both files for one release, so an override still
on `core:icon` keeps resolving. It keeps a backend registration nothing in the
backend uses, and it hides the Breaking change instead of naming it. The
Breaking entry gives the one line migration.

### Identifiers and the constant stay

`CREDIT_POINTS_ICON` keeps `tx-academicprograms-info-credit-points`, and the
templates keep building `category_types.<group>.{type}`. Only the docblocks of
the constant and of `ProgramFact::$iconIdentifier` change to say the value is
an identifier of the frontend registry. #617 renames later, in one step for
both registries.

Rejected: using `CategoryType::getIconIdentifier()` in the partials now. It is
a template refactoring with no behaviour of its own and would touch project
overrides twice.

### `xmlns:ab` in the seven partials

Each partial replaces `xmlns:core` by
`xmlns:ab="http://typo3.org/ns/FGTCLB/AcademicBase/ViewHelpers"` and
`<core:icon …/>` by `<ab:icon …/>` with the same arguments. The prefix `p` is
`academic_base`'s internal choice, and `ace` already means each extension's
own namespace here.

### No `FrontendIcons.php` for partners and projects

Their only frontend icons are category type icons, which `category_types`
contributes. The `Icons.php` of partners holds backend icons only, projects
has none. The change is therefore an Important entry for them,
a Breaking one for programs.

### Tests prove the registry, not the markup

The existing `data-identifier` assertions stay unchanged: they pin the markup
contract and go red if a partial loses the wrapper.

New functional tests, per extension, load a fixture extension with its own SVG
files that carry a distinct marker:

- `test_programs_frontend_icons`: a `FrontendIcons.php` and an `Icons.php`
  entry for the credit points icon with different drawings, a
  `FrontendIcons.php` entry for `category_types.programs.degree`, and a
  `CategoryTypes.yaml` type with `icon` and `frontendIcon`.
- `test_partners_frontend_icons` and `test_projects_frontend_icons`: a type
  with `icon` and `frontendIcon`, and a `FrontendIcons.php` entry for the
  shipped `region` and `competence_field`.

The tests assert the frontend marker in every place of the specs and the
absence of the backend marker, and that the core registry still answers the
declared `icon` source for the type. The shipped type no site package replaces
is one the fixture leaves alone in the same test instance, `standard_period`,
`partner_type` and `cooperation`, rather than the replaced type in a second
instance without the fixture. `FactIconsTest` moves to the frontend
registry trait of `ace-810-frontend-icon-registry` and asserts that the core
registry no longer knows the identifier. A new fixture is preferred over the
titled fixtures, which keep their single purpose.

## Risks / Trade-offs

- [The not-found answer of `ace-810-frontend-icon-registry` keeps the requested
  identifier in `data-identifier`] → then `ProgramFactsTest` does not notice a
  missing `FrontendIcons.php` entry. Checked in task 1.1: it does not, the
  answer carries `default-not-found`, so `ProgramFactsTest` goes red without
  the entry.
- [An override of `Facts/Item.html` on `core:icon` silently shows the
  not-found placeholder for credit points] → the Breaking entry names the
  file and the migration, and `ace-tbd-frontend-icon-guard` catches it in the
  shipped templates.
- [An override of a partner or project partial on `core:icon` keeps working
  but ignores a frontend only replacement] → the Important entries say so.

## Open Questions

- None left. The fixture icon path that exists on neither core (see Context)
  is left as it is: those tests anchor on the wrapper only, and the new
  fixtures of this change ship their own files.
