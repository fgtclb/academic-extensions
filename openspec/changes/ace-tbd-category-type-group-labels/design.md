## Context

- `academic-programs/Configuration/CategoryTypes.yaml:1-4` and
  `academic-projects/Configuration/CategoryTypes.yaml:1-4` declare `groups:`
  with `identifier`, `title` and `icon`. `academic-partners` declares none.
- `CategoryTypeLoader::loadUncached()` reads only `types`
  (`typo3-category-types/Classes/Loader/CategoryTypeLoader.php:59`) and caches
  them as `CategoryType[]` under `CategoryTypes_Types`; `getFromCache()`
  filters the entry for `CategoryType` instances.
- `Classes/Domain/Model/CategoryTypeGroup.php` has `identifier`, `group` and
  `priority`, no title or icon, and no production caller.
- `Configuration/TCA/Overrides/sys_category.php:28` passes the bare group key
  as the item `group` and defines no `itemGroups`, so the backend shows the
  key.
- Type icons are registered in `Classes/ServiceProvider.php` under
  `category_types.<group>.<identifier>`.

## Goals / Non-Goals

**Goals:**

- Group titles in the select, project groups included, the same on v13 and
  v14.

**Non-Goals:**

- Removing the unused `group` property of the group model; a separate
  cleanup.

## Decisions

### Groups in a cache entry of their own

The loader reads `groups` into `CategoryTypeGroup` objects, which gain `title`
and `icon`, and caches them under `CategoryTypes_Groups`; the registry exposes
them next to the types.

Rejected: adding the groups to the existing entry. It changes the shape that
`getFromCache()` validates, and a stale entry written before the update would
read as "no groups" without any error. A separate key simply misses and loads.

### A later declaration replaces title and icon

Groups are processed in package order, and a non-empty `title` or `icon` of a
later declaration replaces the earlier one. No `useExisting` flag, because a
group has nothing else to merge. Rejected: failing on a second declaration,
which would make relabelling a shipped group impossible.

### Titles through itemGroups

The TCA override sets `itemGroups[<identifier>] = <title>` for every declared
group. Undeclared groups get no entry and keep the key as their heading.
Rejected: each extension writing its own `itemGroups` in a TCA override. It
duplicates the YAML declaration, and project groups declared in YAML would
still have no title.

### Group icons next to the type icons

`ServiceProvider` registers each declared group icon with the same provider
choice as the type icons, under `category_types.group.<identifier>`.

### Partners declares its group

`academic-partners/Configuration/CategoryTypes.yaml` gains a `groups:` entry
with a new label, so every shipped group has a title. Labels are written on
one line with two-space indentation, in English and German.

### Decided: a group `priority` reads higher first

The `priority` this change reads and keeps on a group follows the direction
decided for types in `ace-tbd-category-type-priority-order`: higher value
first, stable on load order for equal values. This change still does not
order the groups; a later ordering change applies that direction, so a
`priority` declared today already means what it will mean then.

### Decided: groups keep their first-seen order

This change does not order groups. They stay in the order in which they were
first seen while loading, which is package load order and the order that
`ace-tbd-category-type-priority-order` keeps for groups while it sorts the
types within a group. A group `priority` is read and kept on the group model
but has no effect yet. A later group sort uses the direction of the type
order, higher first. Rejected: sorting groups by `priority` in this change,
which widens a label change by a second sort key no project asked for.

### Select layout

Guessed layout — a sketch, not a design:

```text
sys_category > Type
[ Default                        v ]
  -- Study programs --             <- group title from YAML (today: programs)
     Degree
     Admission restriction
  -- Research projects --
     Funding partner
  -- Partners --                   <- new declaration in academic_partners
     Region
```

## Risks / Trade-offs

- [A group named `group` would share the icon namespace with its own types]
  → accepted; no such group exists, and the changelog names the identifier
  scheme.
- [Stale caches after the update] → the separate cache key loads on a miss.

## Open Questions

None.
