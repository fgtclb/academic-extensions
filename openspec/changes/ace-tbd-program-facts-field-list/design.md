## Context

See `proposal.md` - Why. Verified on main in
`packages/fgtclb/academic-programs`:

- `Partials/Program/Categories.html:17-34` renders every type of
  `allCategoriesByType` in registry order, from `program.attributes`
  (Extbase `Program`) or `program.categories` (`ProgramData` of the page).
  Only `Pages/AcademicProgram.html:19` and `Templates/Details/Show.html:9`
  render it; no other package references it.
- `Pages/AcademicProgram.html:23-41` adds the fixed list `creditPoints`,
  `jobProfile`, `performanceScope`, `prerequisites` as a plain list after the
  categories. The candidate's claim that credit points are never part of the
  facts is half right: they appear on the page, without an icon and outside
  the categories list, and never in the details element or the card.
- `Templates/Details/Show.html:7-12` renders only `Program/Categories`;
  `Partials/Program/Item.html:23-42` renders only the degree.
- `Resources/Public/Icons/CategoryTypes/` has `JobProfile.svg`,
  `PerformanceScope.svg` and `Prerequisites.svg`, but nothing for credit
  points.
- The extension has no `settings.definitions.yaml`. `academic_persons` keeps
  its definitions with the aggregate set only, keyed by the constant path,
  with the defaults mirrored in `constants.typoscript`
  (`academic-persons/Configuration/Sets/Full/`).
- A PAGEVIEW page object assigns `settings` from the TypoScript constants and
  ignores `page.10.settings` (`PageViewContentObject.php:101` on 13.4.35 and
  14.3.6); only `dataProcessing` and `variables` reach both integrations.
- `Documentation/Changelog/3.0/Breaking-RecordAndCategoryIconsFollowTheColourScheme.rst:49`
  names `Partials/Program/Categories.html` among the partials an override
  has to follow.

## Goals / Non-Goals

**Goals:**

- One resolver of "which facts, in which order" for page, details and card.
- No visible change without configuration.

**Non-Goals:**

- A FlexForm field per content element.
- Styling of the facts box beyond the existing markup.

## Decisions

### Settings follow the academic_persons convention

`plugin.tx_academicprograms.facts.fields` (string, comma-separated, default
empty) and `plugin.tx_academicprograms.card.fields` (string, default
`degree`), defined in `Configuration/Sets/Full/settings.definitions.yaml` and
mirrored in `Configuration/TypoScript/constants.typoscript` so a
`sys_template` installation gets the same defaults. The plugin maps them into
`plugin.tx_academicprograms.settings.facts.fields|card.fields`.

Rejected: `academicPrograms.facts.fields` in the ProgramList and
ProgramDetails sets as the candidate proposed. It would be the only
extension in the repository with a second naming scheme, and a definition in
two component sets is the duplication persons removed. Rejected: type
`stringlist`; the comma-separated string has one shape in the site setting
and in the constant.

### The page receives the list through the data processor

`page.10.dataProcessing.100` (`program-data`) gets the option
`factsFields = {$plugin.tx_academicprograms.facts.fields}` and the processor
emits a `facts` variable. Rejected: `page.10.settings`, which a PAGEVIEW page
object ignores.

### One stateless builder, one partial

A `final readonly` `ProgramFactsBuilder` (autowired, no state) takes the
program's category collection, the four built-in values and the field list,
and returns an ordered list of `final readonly` `ProgramFact` items
(identifier, label, icon identifier, values). Both `Program` and
`ProgramData` feed it through one small interface they implement. An empty
list resolves per place: page = all types then the built-ins, details = all
types, card = `degree`. The processor, `DetailsController` and a
`<ap:program.facts>` ViewHelper for the card call it; the partials
`Program/Facts` and `Program/Facts/Item` render the result.

Rejected: resolving `{program.{field}}` in Fluid. It needs three copies of the
built-in/type distinction and cannot skip unknown identifiers cleanly.
Rejected: one partial per type (two projects ship 14-15).

Guessed layout — a sketch, not a design:

```text
GUESSED  facts box (page, details CE)
+-----------------------------------------------+
| [ico] Degree          Bachelor of Science     |
| [ico] Credit points   180 ECTS                |
| [ico] Duration        6 semesters             |
| [ico] Location        Campus A, Campus B      |
+-----------------------------------------------+
card: title / image / card.fields (default: degree)
```

### Decided: credit points icon `tx-academicprograms-info-credit-points`

The credit points fact gets the icon identifier
`tx-academicprograms-info-credit-points`, a Font Awesome Free solid SVG
placed under `Icons/info/` as the icon consolidation of ACE-584 to ACE-594
lays it out. The icon task is applied after pull request #617 (ACE-591) has
merged; `main` does not carry that convention yet.

The decided convention is
`tx-<extension key without underscores>-<group>-<name>`, with category type
ids unchanged. Credit points are a built-in fact, not a
category type, so the group is `info`. Rejected: the pseudo category type id
`category_types.programs.credit_points` that two projects register. It would
collide with those registrations, and a duplicate icon registration
overwrites silently on v13 and v14.

### Decided: `Partials/Program/Categories.html` is removed in 3.0

This change removes `Partials/Program/Categories.html` as a breaking change
instead of deprecating it for removal in 4.0. Its only two callers, the page
template (by then its `Program/Page/Facts` section) and the details
template, render `Program/Facts` in this change, so upstream stops using the
partial in the same commit. The content-load sets of the three extensions
are removed in 3.0 as well, by
`ace-tbd-program-page-content-without-getcontent` for programs and by the
partner and project page template changes; this change does not touch them.

A deprecated partial cannot announce itself: Fluid renders an override of it
or ignores it without any log entry, so a deprecation phase would only delay
the same migration by a major version. Rejected: deprecating the partial in
3.x and removing it in 4.0. Also rejected: keeping it.

### Decided: facts follow the category type order

Category type facts appear in the order of the category types of the
`programs` group whenever the field list does not state an order for them
itself: with an empty list on the page and in the details element. That is
the registry order today and the priority order once
`ace-tbd-category-type-priority-order` has landed. There is one ordering rule
for category types everywhere, and the facts replace
`Program/Categories`, which already followed it.

`ProgramFactsBuilder` therefore takes the types in the order of
`CategoryCollection::getAllCategoriesByType()`, which the collection builds
from `CategoryTypeRegistry::getCategoryTypeIdentifierByGroup()`. It keeps no
type list of its own and never sorts types. The priority change sorts in the
registry, so whichever change lands second has nothing to wire in code; it
only turns the facts order test to priorities (task 2.7 here, its task 2.4
there).

A non-empty field list is honoured literally, types included: it is the
integrator's explicit order. Rejected: re-sorting the named types of a list
by priority. It would make the order of the list meaningless for types and
contradict the configured order scenario. Rejected: a facts order of its own,
for example alphabetic by label, which is the divergence this decision
removes.

## Risks / Trade-offs

- [Two delivery paths, plugin and page object] → a functional test for each,
  plus a PAGEVIEW fixture.
- [Settings only overridable through the aggregate set] → same trade-off as
  persons, documented.
- [Built-in text facts are RTE HTML] → rendered raw as today.
- [A project override of `Program/Categories` silently stops rendering] →
  The Breaking changelog names the replacement partials and the setting that
  reproduces the old order.
- [The facts no longer follow the category type priority once
  `ace-tbd-category-type-priority-order` lands] → resolved by the decision
  above: the default facts take the type order from the registry, and the
  facts order test is written against it. If the priority change has not
  landed, the test pins today's order and that change flips it to
  priorities.

## Migration Plan

No data migration. A project that overrides `Partials/Program/Categories.html`
moves the override to `Program/Facts/Item` (one row) or `Program/Facts` (the
box), and sets `plugin.tx_academicprograms.facts.fields` when it needs an
order other than the default. Other facts overrides are replaced at the
project's pace.

## Open Questions

None.
