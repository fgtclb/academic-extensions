## Context

See `proposal.md` for the motivation. Verified on `main`:

- The `DemandCategories` partials of partners, projects and programs
  (`Partials/<Partner|Project|Program>/DemandCategories.html:8-32`) loop
  `{categories.allCategoriesByType}`, which is keyed by type identifier in
  registry order and contains every registered type, empty ones included
  (`CategoryCollection::getAllCategoriesByType()`); the partial skips empty
  types.
- Each filter cell uses the fixed classes `col-12 col-md-6 col-lg-4 col-xl-3`
  and one "all" label per extension (`sys_category.<group>.allOptions`).
- `categories` comes from `CategoryRepository::findAllApplicable()` in
  `PartnerController::listAction()` and `mapAction()`,
  `ProjectController::listAction()` and `ProgramController::listAction()`.
- None of the three extensions has a `settings` block in its TypoScript or a
  `settings.definitions.yaml`. `academic_jobs` has one
  (`Sets/Full/settings.definitions.yaml`), and
  `docs/architecture/typoscript-and-site-sets.md` requires a site setting
  default to equal the constant it mirrors.
- `PartnerController`, `ProjectController` and `ProgramController` are not
  final and take their dependencies as promoted constructor properties;
  ace-demo subclasses `PartnerController`. The partner map template renders
  the same `SortingAndFilters` partial as the list.

## Goals / Non-Goals

**Goals:**

- The same setting names in all three extensions.
- Defaults reproduce today's markup byte for byte.
- No constructor signature changes.

**Non-Goals:**

- A shared partial across extensions.

## Decisions

### Three settings under `settings.filter`

`plugin.tx_academic<partners|projects|programs>.settings.filter`:
`categoryTypes` (comma list, empty = all), `visibleCount` (int, 0 = all),
`hideDisabledOptions` (bool, 0). Each is a constant in `constants.typoscript`,
mapped in `setup.typoscript`, and declared in a new
`settings.definitions.yaml` next to the aggregate set with the identical
default, as `academic_jobs` does. The `settings.filter` namespace is meant to
be reused by later filter features.

Rejected: a FlexForm field per content element. Every project sets this
site-wide; a per-element value would have to be repeated on every list.

### Decided: TypoScript and site settings only

The three `settings.filter.*` settings are TypoScript constants and site
settings only; this change adds no FlexForm field. Every project that asked
configures them site-wide, the `settings.filter` namespace is shared with the
active-filter change (`ace-tbd-list-active-filters-reset-count`), and a
FlexForm override can be added later without breaking anything.

### Decided: one key for the filter types, overridable per program element

`settings.filter.categoryTypes` is the only name for the filter type
selection. This change delivers it as the site-wide default for all three
lists; `ace-tbd-program-list-filter-types` adds a FlexForm field of the same
name to the program list as a per-element override, where an empty field
falls back to the site value, and the program finder element uses the same
key. Both changes resolve the value through `FilterTypeResolver`, which reads
the merged `settings`, so the override needs no second code path. One project
uses different filters on different pages while most set the list
site-wide; one key with an override serves both, where two names would ship
two keys for one concept.

### A stateless resolver in category_types

`FGTCLB\CategoryTypes\Filter\FilterTypeResolver` (`final readonly`, next to
`CategoryFilterNormalizer`) takes the category collection, the comma list and
the visible count and returns a `final readonly` `FilterTypes` value object
with the ordered identifiers split into `visible` and `more`. It drops unknown
identifiers and types without categories, so the split counts only filters
that render. It is autowired by the extension's `Services.yaml` and not part
of the public API.

The controllers receive it through an `inject*()` method and assign
`filterTypes`. Rejected: a constructor parameter, which breaks every project
subclass of the non-final controllers, including ace-demo's.

### Partials loop the resolved types

The partials loop `filterTypes.visible`, then, when `filterTypes.more` is not
empty, a native `<details>` with a `<summary>` label `filter.moreFilters`
(English and German) around the loop of `filterTypes.more`. `<details>` gets
`open` when one of those types has a selected value. The "all" label reads
`sys_category.<group>.allOptions.<type>` with the generic key as the
`f:translate` default. `hideDisabledOptions` is passed to the filter form
field.

Rejected: one shared filter partial in `academic_base`. It couples the markup
of three extensions to one contract and a template path dependency, while
the settings already remove the reason for most overrides.

Guessed layout — a sketch, not a design:

```text
+--------------------------------------------------------------+
| Region [All regions  v]   (visibleCount = 1)                 |
| > More filters                                               |
|   Partner type [All partner types v]  SDG [All SDGs v]       |
+--------------------------------------------------------------+
```

## Risks / Trade-offs

- [Three extensions and category_types must agree on the names] → One
  change, one set of tests per extension against the same names.
- [The `<details>` wrapper changes markup] → Only when `visibleCount` is
  greater than 0; the default path is pinned by a regression test.
- [A site uses both site sets and the static template] → Identical defaults
  in both declarations, as the site set documentation requires.

## Migration Plan

None. Opt-in settings.

## Open Questions

None.
