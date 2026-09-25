## Context

See `proposal.md` for the motivation. Verified on `main` at `6bea855a6`, and
again at `62e8ef142` before the implementation:

- `Partials/Program/DemandCategories.html` iterates
  `{categories.allCategoriesByType}` and renders a select only for a type
  with categories (`f:if condition="{category}"`). `categories` is the result
  of `CategoryRepository::findAllApplicable('programs', …)` over the listed
  programs, assigned by `ProgramController::listAction()`.
- **Corrected at `62e8ef142`:** `findAllApplicable()` does not return the
  categories of the listed programs only. It returns every category of every
  type of the group (`sys_language_uid` 0 or -1, no page restriction) and
  marks the ones no listed program carries as disabled, which the filter
  select renders as disabled options. A type is therefore offered today when
  it has any category at all. The first version of this change said "a type
  without categories among the listed programs is left out, as today"; a
  functional test against the unchanged code showed a `costs` select for a
  category on no listed program. The specs follow today's rule, which is also
  the only one that keeps "the same selects as before" for existing plugins.
- `Configuration/FlexForms/ProgramListSettings.xml` has `settings.hideFilter`,
  `settings.hideSorting`, `settings.sorting`, `settings.categories` and
  `settings.showHiddenRecords`, and nothing about filter types.
- `category_types` has no items provider for its types. `Classes/Backend/`
  exists by now (ACE-687), but holds the page module summary only. The
  `sys_category` `type` select builds its items inline in
  `Configuration/TCA/Overrides/sys_category.php`.
- `ace-tbd-list-filter-order-visible-labels` is not applied: there is no
  `FilterTypeResolver` and no `settings.filter` key in any list. The program
  list has a `settings.definitions.yaml` by now (ACE-726, ACE-733).
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

`FGTCLB\CategoryTypes\Backend\FormEngine\CategoryTypeItemsProcFunc::itemsForGroup(array &$params)`
reads `$params['config']['itemsProcConfig']['group']`, takes the types of
that group from the `CategoryTypeRegistry` and appends one item per type:
`label` the type title, `value` the identifier, `icon` the icon identifier.
An empty or unknown group appends nothing. It reads
`getGroupedCategoryTypes()[$group] ?? []`, as `PageCategorySummaryRenderer`
does, rather than catching the `\InvalidArgumentException` of
`getCategoryTypesByGroup()`.

The namespace `Backend\FormEngine` follows
`docs/architecture/backend-select-items.md`, where eight of the ten existing
providers live in `Classes/Backend/FormEngine/`. The title is passed on
untranslated: FormEngine translates every item label after the provider ran,
and a literal title, which `CategoryTypes.yaml` allows, stays as it is. It
dispatches no `ModifyTcaSelectFieldItemsEvent`: `category_types` does not
depend on `academic_base`, and the types are changed where they are
registered.

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
`itemsProcConfig.group` `programs`, `size` 6 and `maxitems` 999, so the group
size is the only limit. It sits right after `settings.hideFilter` on the
`Filter` sheet. The side-by-side select stores the order the editor chose. One
project uses different filters on different pages, which the site-wide
setting alone cannot express.

`plugin.tx_academicprograms.ignoreFlexFormSettingsIfEmpty` lists
`filter.categoryTypes` (core prepends `settings.`), so Extbase drops an
empty field before the FlexForm is merged over the TypoScript settings
(`FrontendConfigurationManager`, the same on v13 and v14), and the site-wide
value applies without code of our own.

### The site-wide setting of the program list

The constant `plugin.tx_academicprograms.filter.categoryTypes`, default empty,
is mapped to `settings.filter.categoryTypes` in `setup.typoscript` and
declared in `Sets/Full/settings.definitions.yaml` with the same default, like
the facts lists. The listings change owns this setting for the three lists; it
is added here for the program list only, because the field falls back to it
and the fallback needs something to fall back to. The listings change then
adds the same key to partners and projects and finds the program list done.

### Resolve the list with the shared resolver

`listAction()` hands the effective `settings.filter.categoryTypes` to the
`FilterTypeResolver` of `ace-tbd-list-filter-order-visible-labels` and
assigns its result as `filterTypes`; `DemandCategories.html` loops
`filterTypes.visible`. The resolver drops identifiers without an entry in
`allCategoriesByType` (removed type) and types without any category, and an
empty list means every type with a category, as today.

This change is applied first, so it adds the resolver in the shape the
listings change describes: `resolve(CategoryCollection, string, int = 0)`
returning a `final readonly` `FilterTypes` with `getVisible()` and
`getMore()`. The program list passes no visible count, so `more` stays empty
until the listings change adds the disclosure. The controller receives the
resolver through a `final` `injectFilterTypeResolver()`, next to the existing
`injectFilterRedirectExtensionService()`, so the constructor project
subclasses call stays unchanged.

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

- [A project controller or template does not hand `filterTypes` to the
  partial] → a subclass that overrides `listAction()`, or an override of
  `List.html` or `SortingAndFilters.html` that renders the partial with its
  own arguments instead of `{_all}`, would lose every filter. The partial
  falls back to the loop over `categories.allCategoriesByType` when
  `filterTypes` is missing, so such a project keeps today's filters and only
  the setting has no effect there; the changelog says how to pass it on.
- [A project overrides `DemandCategories.html`] → its override keeps looping
  `categories.allCategoriesByType` and ignores the field; the changelog says
  how to switch. `categories` is still assigned unchanged.
- [A type whose categories are all disabled is still offered] → today's
  behaviour, kept on purpose. Leaving such a type out changes the default
  output of every list and belongs to the filter behaviour of the listings
  analysis.

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
