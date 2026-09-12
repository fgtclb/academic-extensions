## Why

Every one of the six projects re-implements the program facts box. The
upstream templates print all category types in registry order, the page adds
a fixed list of four text fields, the details element shows categories only
and the card the degree only. No integrator can choose which facts appear or
in which order, credit points never appear with an icon, and two projects
register a pseudo icon for them.

## What Changes

- A site setting lists the facts of the program page and the details content
  element, in order: category type identifiers of the `programs` group and
  the built-in facts `creditPoints`, `jobProfile`, `performanceScope` and
  `prerequisites`.
- A second site setting lists the facts of the program card in the list,
  defaulting to `degree`.
- An empty list keeps today's output of each place exactly.
- Category type facts follow the category type order of the `programs`
  group wherever the list does not order them itself: the registry order
  today, the priority order once `ace-tbd-category-type-priority-order` has
  landed. A non-empty list keeps the order it states.
- Credit points become a fact with their own icon,
  `tx-academicprograms-info-credit-points`.
- Page, details element and card render the facts through one shared partial,
  so a project overrides a single fact row instead of three templates.
- **BREAKING** `Partials/Program/Categories.html` is removed. The program
  page and the details element render `Program/Facts` instead, so a project
  override of the removed partial is no longer rendered and moves to
  `Program/Facts` or `Program/Facts/Item`.
- Both page object integrations (FLUIDTEMPLATE and PAGEVIEW) receive the
  setting.

Behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-programs/program-facts`: which facts a program page, the details
  content element and the program card show, and in which order.

### Modified Capabilities

None.

## Impact

- `academic_programs` (`packages/fgtclb/academic-programs`): site settings
  definition, constants, plugin and page object TypoScript, the `program-data`
  data processor, templates `Pages/AcademicProgram.html`,
  `Templates/Details/Show.html`, `Partials/Program/Item.html`, new partials
  `Program/Facts` and `Program/Facts/Item`, the removed partial
  `Program/Categories`, a credit points icon.
- `category_types` (`packages/fgtclb/typo3-category-types`): read only; the
  facts take the category type order from its registry, which
  `ace-tbd-category-type-priority-order` orders by priority. Whichever of
  the two changes lands second turns the facts order test to priorities; no
  code has to be wired.
- Builds on `ace-tbd-program-page-layout-and-sections`, whose facts section
  renders the new partial.
- The credit points icon follows the icon naming of ACE-591 (pull request
  #617), so this change is applied after that pull request has merged.
- Project overrides of `Partials/Program/Categories.html` stop rendering and
  have to move to the facts partials; the Breaking changelog describes the
  migration.

## Non-goals

- Hiding parent categories (`ace-tbd-most-specific-categories-only`).
- Changing the credit points column type or format.
- A per content element facts selection in the FlexForm.
- The content-load sets; `ace-tbd-program-page-content-without-getcontent`
  removes the programs one.
- Backporting to branch `2`.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`programs-studyplan-05`). All six analysed projects carry their own code for
this today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-program-facts-field-list` when the issue is filed after
implementation.
