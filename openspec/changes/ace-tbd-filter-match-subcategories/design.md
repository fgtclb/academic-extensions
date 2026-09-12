## Context

See `proposal.md` for the motivation. Verified on `main` at `6bea855a6`:

- `ProgramRepository::findByDemand()` adds one
  `$query->contains('categories', $category->getUid())` per category of the
  demand's `FilterCollection` and joins all constraints with `logicalAnd()`.
- `DemandFactory` fills that collection from the plugin's `settings.categories`
  through `CategoryRepository::getByDatabaseFields()`, or from the submitted
  form through `CategoryFilterNormalizer::toUidList()` and
  `CategoryRepository::findByGroupAndUidList()`. Both repositories are the
  one of `category_types`; `academic_programs` has no category repository.
- The `CategoryRepository` queries restrict `sys_category.type` to the types
  of the group with `quoteArrayBasedValueListToStringList()`, restrict
  `sys_language_uid` to `0` and `-1`, and order by `sorting`.
  `getCategoryRootline()` walks upwards and already guards against a loop.
- `ProgramDemand` has no flag for this, and `ProgramListSettings.xml` has no
  such field.

## Goals / Non-Goals

**Goals:**

- One descendant lookup per request, however many categories are selected.
- Unchanged results for every plugin that does not enable the option.

**Non-Goals:**

- A changed combination of selections (listings analysis).
- The same option for partners and projects; the lookup is reusable for them.

## Decisions

### Resolve descendants in `category_types`

`CategoryRepository::findDescendantUids(string $group, int ...$uids): array`
walks the tree downwards level by level: one statement per level, selecting
`uid` and `parent` where `parent` is in the current level, with
`quoteArrayBasedValueListToIntegerList()`, the group's type restriction and
`sys_language_uid` in `0` and `-1`, ordered by `uid`. Each statement is built
and executed on its own query builder. Uids already seen are dropped, which
ends a loop. The result maps each requested uid to its descendant uids.

The default frontend restrictions stay in place, so a hidden or deleted
subcategory is not part of the subtree. That matches what the visitor can see.

A recursive CTE was rejected: the repository rules ask for query builder
statements that behave identically on SQLite, MariaDB, MySQL and PostgreSQL,
and a CTE through Doctrine DBAL needs raw SQL. Walking the rootline of every
program was rejected because it costs one query per program and category.

### Widen each selection with `logicalOr()`

With `ProgramDemand::getIncludeSubcategories()` true, the repository replaces
each `contains('categories', $uid)` with
`logicalOr(contains('categories', $uid), contains('categories', $descendant), …)`
and keeps the outer `logicalAnd()`.

A single `in('categories.uid', $uids)` was rejected: Extbase joins a property
path once per query, so with two selections both `in()` constraints would
have to match the same category row, which turns AND into an impossible
condition.

### Plugin setting, not a site setting

`settings.filter.includeSubcategories` is a `check` field with
`renderType` `checkboxToggle` and default `0` in `ProgramListSettings.xml`,
mapped by `DemandFactory` onto `ProgramDemand`. Whether a tree is used
hierarchically differs per list, and the future finder element
(`programs-studyplan-10`) needs the same field.

### Decided: land independently, no shared constraint helper

This change lands on its own. `findDescendantUids()` in `category_types` is
the only shared piece; no shared constraint builder is introduced, because
upstream does not implement OR within a category type. Where a project needs
OR within a type, it does so through a listener on the demand events of the
listings and programs changes. Such a listener regroups the selections per
type, and that composes with the per-selection `logicalOr()` of this change
inside the outer `logicalAnd()`, so the order in which the changes land does
not matter. The finder field `settings.filter.includeSubcategories` and the
map adaptation of the client-side finder narrowing follow what this change
ships.

## Risks / Trade-offs

- [Deep or wide trees] → one statement per level and one `OR` term per
  descendant; degree trees are two or three levels. Measured in the
  functional test on PostgreSQL as well.
- [A project listener regroups selections for OR within a type] → it
  receives the widened per-selection constraints and keeps working; the spec
  here does not change.
- [A project relies on exact matches] → the option is off by default.

## Migration Plan

None. Projects enable the option, assign only the specific category, and
drop their uid blacklists, title comparisons and whitelists.

## Open Questions

None.
