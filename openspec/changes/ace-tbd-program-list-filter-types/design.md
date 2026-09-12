## Context

See `proposal.md` for the motivation. Verified on `main` at `6bea855a6`:

- `Partials/Program/DemandCategories.html` iterates
  `{categories.allCategoriesByType}` and renders a select only for a type
  with categories (`f:if condition="{category}"`). `categories` is the result
  of `CategoryRepository::findAllApplicable('programs', …)` over the listed
  programs, assigned by `ProgramController::listAction()`.
- `Configuration/FlexForms/ProgramListSettings.xml` has `settings.hideFilter`,
  `settings.hideSorting`, `settings.sorting`, `settings.categories` and
  `settings.showHiddenRecords`, and nothing about filter types.
- `category_types` has no items provider for its types; `Classes/` has no
  `Backend/` directory. The `sys_category` `type` select builds its items
  inline in `Configuration/TCA/Overrides/sys_category.php`.
- Core passes the field's whole `config` to an `itemsProcFunc`, including a
  custom `itemsProcConfig`; core's own
  `TcaItemsProcessorFunctions` reads `['config']['itemsProcConfig']` on
  v13.4 and v14 alike. FlexForm fields take the same `config`.

## Goals / Non-Goals

**Goals:**

- One items provider in `category_types`, reusable for partners, projects and
  the future finder element.
- Order and subset decided per plugin, empty meaning the site-wide value,
  and today's behaviour when that is empty as well.

**Non-Goals:**

- Generic filter UX (listings analysis).
- Validating submitted filter values against the offered types.

## Decisions

### Items provider in `category_types`, group from `itemsProcConfig`

`FGTCLB\CategoryTypes\Backend\CategoryTypeItemsProcFunc::itemsForGroup(array &$params)`
reads `$params['config']['itemsProcConfig']['group']`, asks the
`CategoryTypeRegistry` for the types of that group and appends one item per
type: `label` the type title, `value` the identifier, `icon` the icon
identifier. An empty or unknown group appends nothing; the registry's
`\InvalidArgumentException` for an unknown group is caught there.

The class is `final readonly`, stateless, takes the registry through the
constructor and is made public with Symfony's `#[Autoconfigure(public: true)]`,
because core instantiates an `itemsProcFunc` through `makeInstance()`.

A provider in `academic_programs` was rejected: partners and projects need
the same field, and the registry belongs to `category_types`. A static item
list in the FlexForm was rejected: it cannot follow types a project adds or
removes.

### A FlexForm field that overrides the site setting

`settings.filter.categoryTypes` is a `select` with `renderType`
`selectMultipleSideBySide`, `itemsProcFunc` the provider and
`itemsProcConfig.group` `programs`, `size` 6, no `maxitems` limit beyond the
group size. The side-by-side select stores the order the editor chose. One
project uses different filters on different pages, which the site-wide
setting alone cannot express.

`plugin.tx_academicprograms.ignoreFlexFormSettingsIfEmpty` lists
`settings.filter.categoryTypes`, so Extbase drops an empty field before the
FlexForm is merged over the TypoScript settings
(`FrontendConfigurationManager`, the same on v13 and v14), and the site-wide
value applies without code of our own.

### Resolve the list with the shared resolver

`listAction()` hands the effective `settings.filter.categoryTypes` to the
`FilterTypeResolver` of `ace-tbd-list-filter-order-visible-labels` and
assigns its result as `filterTypes`; `DemandCategories.html` loops it as that
change describes. The resolver already drops identifiers without an entry in
`allCategoriesByType` (removed type) and types without categories, and an
empty list means every type with categories, as today. If this change is
applied first, it adds the resolver in the shape the listings change
describes rather than a resolution of its own.

Splitting the string in Fluid was rejected: the template would need to trim
and filter, and every override would copy that logic.

### Decided: one key for the site-wide and the per-element filter types

The three lists share one name, `settings.filter.categoryTypes`: the
site-wide setting of the listings change is the default, and this change's
FlexForm field overrides it for one element, with an empty field falling
back to the site value. Both changes share this change's items provider and
the listings change's resolver, and the finder element uses the same key.
One project needs different filters on different pages while most projects
set the list site-wide, so both levels are needed, and two keys for one
concept in programs were rejected.

## Risks / Trade-offs

- [Label of a project type] → the select label is still translated from
  `sys_category.programs.<identifier>`, so a project type needs that label in
  a `locallangXMLOverride`, as today.
- [Partners and projects later get the per-element field too] → the provider
  is already generic and the key is the same; only the FlexForm fields
  differ.

## Migration Plan

None. Projects replace their hard-coded filter list in the template override
by the field, keeping the override only for styling.

## Open Questions

None.
