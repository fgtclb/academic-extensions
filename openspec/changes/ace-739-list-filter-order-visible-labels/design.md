## Context

See `proposal.md` for the motivation. Verified on `main`:

- The `DemandCategories` partials of partners and projects
  (`Partials/<Partner|Project>/DemandCategories.html:8-32`) loop
  `{categories.allCategoriesByType}`, which is keyed by type identifier in
  registry order and contains every registered type, empty ones included
  (`CategoryCollection::getAllCategoriesByType()`); the partial skips empty
  types.
- Each filter cell uses the fixed classes `col-12 col-md-6 col-lg-4 col-xl-3`
  and one "all" label per extension (`sys_category.<group>.allOptions`).
- `categories` comes from `CategoryRepository::findAllApplicable()` in
  `PartnerController::listAction()` and `mapAction()`,
  `ProjectController::listAction()` and `ProgramController::listAction()`.
- **Since `ace-736-program-list-filter-types`:** `category_types` ships
  `FilterTypeResolver` and `FilterTypes` in the shape described below, and
  the program list already reads `settings.filter.categoryTypes` - constant
  `plugin.tx_academicprograms.filter.categoryTypes`, site setting, FlexForm
  override - and loops `filterTypes.visible` in its `DemandCategories`
  partial. What is left for the program list is `visibleCount`,
  `hideDisabledOptions`, the disclosure and the per-type label. Note that
  `findAllApplicable()` returns every category of the group, those on no
  listed record disabled, so "a type without categories" means a type
  without any category.
- **Corrected on 2026-09-26, `main` at `4541ae3df`:** the premise that none of
  the three extensions has a `settings` block or a `settings.definitions.yaml`
  no longer holds. `academic_partners` maps `pagination.numberOfLinks` into
  `settings` and declares it in `Sets/List/settings.definitions.yaml`;
  `academic_programs` declares its settings, `filter.categoryTypes` among them,
  in `Sets/Full/settings.definitions.yaml`. Only `academic_projects` has
  neither. The constants of both are `plugin.tx_<extension>.<key>`, mapped to
  `settings.<key>` in `setup.typoscript`, not `plugin.tx_….settings.…`.
  `docs/architecture/typoscript-and-site-sets.md` requires a site setting
  default to equal the constant it mirrors.
- `Projects (selected)` runs the same list action and template as `Projects`,
  so both project elements render the filter form.
- The filter templates pass `extensionName: 'academic_<group>'`. TYPO3 v14
  strips the underscore and reads `_LOCAL_LANG` from `plugin.tx_academic<group>`
  and `plugin.tx_academic<group>_<plugin>`; TYPO3 v13 lowercases the name as
  given and reads `plugin.tx_academic_<group>` only. Measured with a functional
  probe and in both development instances. v14 also renamed
  `SYS.locallangXMLOverride` to `LANG.resourceOverrides` and ignores the old
  key.
- `PartnerController`, `ProjectController` and `ProgramController` are not
  final and take their dependencies as promoted constructor properties;
  ace-demo subclasses `PartnerController`. The partner map template renders
  the same `SortingAndFilters` partial as the list.

## Goals / Non-Goals

**Goals:**

- The same setting names in all three extensions.
- Defaults reproduce today's markup; only the whitespace between tags may
  change.
- No constructor signature changes.

**Non-Goals:**

- A shared partial across extensions.

## Decisions

### Three settings under `settings.filter`

`settings.filter` of `plugin.tx_academic<partners|projects|programs>`:
`categoryTypes` (comma list, empty = all), `visibleCount` (int, 0 = all),
`hideDisabledOptions` (bool, 0). Each is a constant
`plugin.tx_academic<…>.filter.<key>` in `constants.typoscript`, mapped in
`setup.typoscript`, and a site setting of the same path with the identical
default. The `settings.filter` namespace is meant to be reused by later filter
features.

The site settings are declared with each extension's **aggregate** set:
the partner list and map read them, both project elements read them, and
`academic_programs` already declares `filter.categoryTypes` there. A set
declares settings only for itself, so a component set would leave the other
reader without them. Rejected: declaring them in every component set that
reads them - the core tolerates a duplicate declaration (the last one wins),
but two copies of one description drift.

Rejected: a FlexForm field per content element for the partner and project
lists. Every project sets this site-wide; a per-element value would have to be
repeated on every list. The program list has one for `categoryTypes`, see
below.

### Decided: TypoScript and site settings only

The three `settings.filter.*` settings are TypoScript constants and site
settings only; this change adds no FlexForm field. Every project that asked
configures them site-wide, the `settings.filter` namespace is shared with the
active-filter change (`ace-tbd-list-active-filters-reset-count`), and a
FlexForm override can be added later without breaking anything.

### Decided: one key for the filter types, overridable per program element

`settings.filter.categoryTypes` is the only name for the filter type
selection. This change delivers it as the site-wide default for all three
lists; `ace-736-program-list-filter-types` adds a FlexForm field of the same
name to the program list as a per-element override, where an empty field
falls back to the site value, and the program finder element uses the same
key. Both changes resolve the value through `FilterTypeResolver`, which reads
the merged `settings`, so the override needs no second code path. One project
uses different filters on different pages while most set the list
site-wide; one key with an override serves both, where two names would ship
two keys for one concept.

### A stateless resolver in category_types

`FGTCLB\CategoryTypes\Filter\FilterTypeResolver` and `FilterTypes` exist
since `ace-736-program-list-filter-types` in the shape this change described.
This change adds `resolveFromSettings()`, which reads `filter.categoryTypes`
and `filter.visibleCount` from the plugin settings and treats a value neither
TypoScript nor a site setting delivers as not set. The three list actions call
it; the program finder keeps calling `resolve()` with its own default and no
count. Rejected: a private reader per controller, three copies of the same
settings parsing.

The controllers receive it through a `final` `inject*()` method and assign
`filterTypes`; the partner and project actions resolve the categories their
list event handed back. Rejected: a constructor parameter, which breaks every
project subclass of the non-final controllers, including ace-demo's.

### Partials loop the resolved types

The partials loop `filterTypes.visible`, then, when `filterTypes.more` is not
empty, a native `<details>` with a `<summary>` label `filter.moreFilters`
(English and German) around the loop of `filterTypes.more`. `<details>` is one
`col-12` cell of the form's row and holds a `row` of its own, so the cells
inside keep their grid classes. It gets `open` when one of those types has a
selected value, found by a loop that sets a variable - `f:variable` inside
`f:for` reaches the enclosing scope on Fluid 4 and 5. The "all" label reads
`sys_category.<group>.allOptions.<type>` with the generic key as the
`f:translate` default; no extension ships a label per type, so the default
output is unchanged. A site adds one through `_LOCAL_LANG` at
`plugin.tx_academic<group>` or `plugin.tx_academic<group>_<plugin>`, or a
language file override under the key of its core version. The filter templates
pass the extension name in UpperCamelCase, so the `_LOCAL_LANG` path is the
same on v13 and v14 (Stefan, 2026-09-26). The other templates keep the
underscored name; their fix is a change of its own, scheduled right after this
one.
`hideDisabledOptions` is passed to the filter form field. Without
`filterTypes` the partial falls back to the loop of before, as the program
partial of `ace-736-program-list-filter-types` does.

### Decided while implementing

- **The program finder follows the list in `hideDisabledOptions` and the "all"
  label, not in `visibleCount`.** It reads the same `settings.filter` block and
  renders the same form field; a disabled option in a finder is as useless as
  in a list. A disclosure contradicts a finder that is compact by design.
- **A filter whose options are all left out still renders**, with its "all"
  option only. Leaving it out was rejected: which categories are disabled
  changes with every filter the visitor sets, so filters would come and go
  and move in and out of the disclosure while the visitor narrows the list.
- **The tests share a new testing-helper trait**,
  `CategoryFilterFormAssertionTrait`, which reads the filters of a form by
  their field name, split by where they render. Four test classes in three
  extensions need the same reading.

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
  greater than 0 and smaller than the number of offered filters; the default
  path and a covering count are pinned by regression tests.
- [A site uses both site sets and the static template] → Identical defaults
  in both declarations, as the site set documentation requires.

## Migration Plan

The settings are opt-in. What is not: on TYPO3 v13, a TypoScript label
override of the filter form under `plugin.tx_academic_<group>._LOCAL_LANG` no
longer reaches it, because the templates now pass the UpperCamelCase extension
name. Sites copy such overrides to `plugin.tx_academic<group>._LOCAL_LANG`, as
the `Important-` changelog entries say; copy, because other templates still
read the old path until ACE-740.

## Open Questions

None.
