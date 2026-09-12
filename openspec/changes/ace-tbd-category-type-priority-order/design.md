## Context

See `proposal.md` for the motivation. Verified on `main` at `6bea855a6`:

- `CategoryType` takes `priority` from YAML (constructor, `fromArray()`,
  `__set_state()`, `getPriority()`), and no code reads it.
- `CategoryTypeRegistry::attach()` appends to `$registry` and to
  `$groupedRegistry[<group>][<identifier>]`. `getCategoryTypesByGroup()`,
  `getCategoryTypeIdentifierByGroup()`, `getGroupedCategoryTypes()`,
  `getCategoryTypes()` and `toArray()` return insertion order.
- `CategoryCollectionFactory` hands `getCategoryTypeIdentifierByGroup()` to
  `CategoryCollection::setTypeIdentifiers()`, and
  `CategoryCollection::getAllCategoriesByType()` builds its result in that
  order. The programs templates `Partials/Program/Categories.html`,
  `Partials/Program/DemandCategories.html`, `Partials/Program/Item.html` and
  `Backend/Partials/PageLayout/Doktype20.html` all iterate that result.
- `CategoryTypeLoader::loadUncached()` keys the loaded types by
  `<group>.<identifier>`. A `useExisting` override `array_merge()`s onto the
  existing entry and assigns it to the same key, so the type keeps its
  position. A `priority` in the override already reaches the model.
- The loader caches the result of `getCategoryTypes()` and attaches it again
  from the cache. The order therefore survives the cache in both paths.
- `Configuration/TCA/Overrides/sys_category.php` builds the items of the
  `type` select from `getCategoryTypes()`.
- No `CategoryTypes.yaml` under `packages/fgtclb/` sets `priority` (academic
  partners, programs and projects checked).
- The unit test `attachedTypesAreReturnedInAttachmentOrder` pins the
  insertion order for types of priority `0`, which this change keeps.

## Goals / Non-Goals

**Goals:**

- One place that owns the order, so every consumer follows it.
- No visible change while no package sets a priority.

**Non-Goals:**

- Changing the loader's merge or remove semantics.
- Any change in `academic_programs`, `academic_partners` or
  `academic_projects`.

## Decisions

### Sort in the registry, not in the loader

`attach()` sorts the affected groups after inserting and rebuilds the flat
`$registry` from them: groups in the order they were first seen, and within a
group by priority descending. PHP's `usort()` is stable since 8.0, so equal
priorities keep their insertion order without an explicit tiebreaker.

Sorting in the loader was rejected. The registry is public API and a test or
a third-party package can `attach()` without the loader; the registry is the
only place every consumer passes through. Sorting in each getter was rejected
because it repeats the work on every call, while `attach()` runs once per
request, or once per cache fill.

The existing duplicate check in `attach()` stays, so a duplicate still
throws before anything is sorted.

### Decided: higher priority first

Higher `priority` first, stable on load order for equal values. This follows
the `priority` of Symfony tagged services, which the TYPO3 service container
uses, and of the FormEngine node registry. With every default at `0`, a
single `useExisting` override with a `priority` moves one type to the front,
whatever the others declare. No shipped `CategoryTypes.yaml` sets `priority`,
so nothing upstream moves, and the order at priority `0` stays pinned by
`attachedTypesAreReturnedInAttachmentOrder`. Lower first, the convention of
`sorting` columns, was rejected: moving one type to the front would need a
negative value or overrides of every other type.

### Decided: backport to branch `2` as a 2.4 feature

The change is backported to branch `2` and ships as a feature of 2.4, after a
file-level diff of the registry and the loader between the branches. With all
priorities at `0` and a stable `usort()` the order is unchanged, so the
backport changes nothing for anyone who does not opt in, and the projects
that hard-code a type order in templates run 2.x. The claim that the loader
behaves the same on `2` comes from the analysis and is re-checked by that
diff. Because the feature ships with 2.4, its changelog files live in
`typo3-category-types/Documentation/Changelog/2.4/` on `main` and on branch
`2`, not in `3.0/`.

### Decided: no installed package is known to set `priority`

No record of the six analysed projects mentions `priority` in a
`CategoryTypes.yaml`. The project that redeclares eight program types gets its
order from declaration order plus `remove: true`, which stays unchanged at
priority `0`. The change therefore treats the reorder as affecting no known
installation. The analysis holds only an excerpt of that project's
`CategoryTypes.yaml`, so the complete file is read before the wording of the
`Important-` entry is final (task 3.2).

### The flat list follows the grouped order

`getCategoryTypes()` feeds the backend `type` select and the icon
registration. Keeping it in grouped order makes the select follow the
configured order. Types of one group never interleave with those of another
group anyway, because each package declares one group.

### No new order syntax

`priority` is already part of the schema and the model. A `position` or
`before`/`after` key would need a dependency resolver and a second concept,
for a need that a number covers.

### The program facts follow this order

`ace-tbd-program-facts-field-list` replaces `Program/Categories` with a facts
field list on `main` and decides that its category type facts follow the
category type order wherever the list does not order them itself (an empty
list, the default). It reads the order from
`getCategoryTypeIdentifierByGroup()` through the category collection and
never sorts types, so sorting in `attach()` reaches the facts without a
change there. Whichever of the two changes lands second extends the facts
order test to priorities (task 2.4). A field list that names types keeps its
own order; that is the integrator's explicit choice, not a second rule.

## Risks / Trade-offs

- [A package outside this repository already sets `priority`] → its types
  reorder after a cache flush. Named in an `Important-` changelog entry.
  Within this repository no package sets it.
- [An override has to load after the owning extension] → the existing loader
  requirement, documented by `ace-tbd-category-types-yaml-docs`; an override
  of an unknown type still throws.
- [The TCA of `sys_category` is cached] → the new select order shows after a
  cache flush, like every change to a `CategoryTypes.yaml`.

- [`Partials/Program/Categories.html` is removed on `main` in 3.0 by
  `ace-tbd-program-facts-field-list`] → resolved there: its facts follow
  this order, see "The program facts follow this order". The program page
  facts scenario holds on both branches before and after that change.

## Migration Plan

Nothing to migrate. A project removes the type order from its template
overrides and sets `priority` on `useExisting` overrides instead.

## Open Questions

None.
