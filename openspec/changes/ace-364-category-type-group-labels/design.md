## Context

- `academic-programs/Configuration/CategoryTypes.yaml:1-4` and
  `academic-projects/Configuration/CategoryTypes.yaml:1-4` declare `groups:`
  with `identifier`, `title` and `icon`. `academic-partners` declares none.
  The two declared icon files, `Resources/Public/Icons/CategoryGroups/Programs.svg`
  and `.../Projects.svg`, do not exist and never did: no commit since the one
  that introduced the declarations (`ccef425d1`) adds them.
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

### A later declaration replaces what it declares

Groups are processed in package order. A non-empty `title` or `icon` of a
later declaration replaces the earlier one, and so does a declared
`inlineIcon` or `priority`; what the later declaration leaves out is kept.
No `useExisting` flag, because a group has no other keys that would need an
explicit merge. Rejected: failing on a second declaration, which would make
relabelling a shipped group impossible.

### Titles through itemGroups

The TCA override sets `itemGroups[<identifier>] = <title>` for every declared
group. Undeclared groups get no entry and keep the key as their heading.
Rejected: each extension writing its own `itemGroups` in a TCA override. It
duplicates the YAML declaration, and project groups declared in YAML would
still have no title.

### Group icons next to the type icons

`ServiceProvider` registers each declared group icon with the same provider
choice as the type icons, under `category_types.group.<identifier>`: the core
detection, and `CurrentColorSvgIconProvider` for an SVG only when the group
declares `inlineIcon: true`. A group without an icon registers none.

### A group entry needs an identifier

A `groups:` entry without a non-empty `identifier` is rejected with an
exception, as a type without one is. `title`, `icon`, `inlineIcon` and
`priority` are optional. A declared group that no type uses is kept: its item
group label has no effect, because FormEngine leaves out a group without
items, and its icon is registered.

### Shipped group icons

The icon files the programs and projects declarations point to were never
shipped (decided by the maintainer on 2026-09-27): the three shipped groups
get Font Awesome Free 7.3.1 solid icons, drawn for inlining and declared with
`inlineIcon: true`, at `Resources/Public/Icons/CategoryGroups/<Group>.svg`.
The drawings are the ones the icon consolidation in review (ACE-584 to
ACE-594) chose for the same groups and plugins: `book-open-reader` for
programs, `microscope` for projects, `building-flag` for partners. Each
extension lists them in its `LICENSE-font-awesome.txt`. Rejected: pointing
the groups at `Extension.svg`, and dropping the icon from the shipped
declarations.

### Partners declares its group

`academic-partners/Configuration/CategoryTypes.yaml` gains a `groups:` entry
with a new label and icon, so every shipped group has a title. Labels are written on
one line with two-space indentation, in English and German.

### Decided: a group `priority` reads higher first

The `priority` this change reads and keeps on a group follows the direction
decided for types in `ace-752-category-type-priority-order`: higher value
first, stable on load order for equal values. This change still does not
order the groups; a later ordering change applies that direction, so a
`priority` declared today already means what it will mean then.

### Decided: groups keep their first-seen order

This change does not order groups. They stay in the order in which they were
first seen while loading, which is package load order and the order that
`ace-752-category-type-priority-order` keeps for groups while it sorts the
types within a group. A group `priority` is read and kept on the group model
but has no effect yet. A later group sort uses the direction of the type
order, higher first. Rejected: sorting groups by `priority` in this change,
which widens a label change by a second sort key no project asked for.

### Select layout

Guessed layout — a sketch, not a design:

```text
sys_category > Type
[ Default                        v ]
  -- Academic Programs --          <- group title from YAML (today: programs)
     Degree
     Admission restriction
  -- Academic Projects --
     Funding partner
  -- Academic Partners --          <- new declaration in academic_partners
     Region
```

## Risks / Trade-offs

- [A group named `group` would share the icon namespace with its own types]
  → accepted; no such group exists, and the changelog names the identifier
  scheme.
- [Stale caches after the update] → the separate cache key loads on a miss.
- [The type select lists the groups of `itemGroups` first, in declaration
  order, and appends the groups it only finds in the items, so an undeclared
  group moves behind the declared ones there] → FormEngine behaviour on v13
  and v14, accepted and documented; every shipped group is declared.

## Open Questions

None.
