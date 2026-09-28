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
- `ProgramController::listAction()` and `finderAction()` build the filter
  options with `CategoryRepository::findAllApplicable()`, which disables every
  category of the group that no listed program carries itself. The filter
  select renders a disabled option with the `disabled` attribute, or leaves it
  out when `hideDisabledOptions` is set (verified 2026-09-28 on `02fd5ba88`).

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
ends a loop. The result maps each requested uid to its descendant uids. A uid
of 0 or less is not walked, because `parent = 0` would select every root
category of the group.

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

### Offer a parent whose subcategory is carried

Decided 2026-09-28: with the option on, the list and the finder build their
options with `CategoryRepository::findAllApplicableWithSubcategories()`. It
runs the query of `findAllApplicable()`, then enables every ancestor of an
enabled category, walking the parents of the rows that query returned. The
query returns every visible category of the group, so the walk needs no
further query and sees the same tree as `findDescendantUids()`: a hidden
category, or one of a type outside the group, ends it. The walk keeps the uids
it has seen, so a loop ends as well. It reads the parents before the rows are
overlaid with a translation (found in the review): the parent of a category
object is the one of its translation in a translated frontend, and an editor
can give a translation another parent, while the downward walk reads the
default language.

The finder preselects only an option it enables, so with the option on a
preselected parent whose subcategory is carried is selected as well. The
archived finder requirement on preselection is modified accordingly.

Keeping the form unchanged was rejected: when editors assign only the specific
category, which is what this change asks of them, the parent is carried by no
program, renders disabled, and a visitor cannot select it. The option would
then only work for preselected categories and hand-written URLs.

### Plugin setting, not a site setting

`settings.filter.includeSubcategories` is a `check` field with
`renderType` `checkboxToggle` and default `0` in `ProgramListSettings.xml`,
mapped by `DemandFactory` onto `ProgramDemand`. Whether a tree is used
hierarchically differs per list.

Decided 2026-09-28: the program finder, merged since this change was written,
gets the same field in `ProgramFinderSettings.xml`. It changes only which
options the finder enables. The finder submits to the list on its target page,
whose own field decides the result, and the documentation says to switch both
on together.

### Decided: land independently, no shared constraint helper

This change lands on its own. `findDescendantUids()` in `category_types` is
the only shared piece; no shared constraint builder is introduced, because
upstream does not implement OR within a category type. Where a project needs
OR within a type, it does so through a listener on the demand events of the
listings and programs changes. Such a listener regroups the selections per
type, and that composes with the per-selection `logicalOr()` of this change
inside the outer `logicalAnd()`, so the order in which the changes land does
not matter. The map of the client-side finder narrowing
(`ace-tbd-finder-client-side-narrowing`) is not merged yet. It reads the
finder field this change ships and adapts its map there.

## Risks / Trade-offs

- [Deep or wide trees] → one statement per level and one `OR` term per
  descendant; degree trees are two or three levels. Measured in the
  functional test on PostgreSQL as well.
- [A project listener regroups selections for OR within a type] → it
  receives the widened per-selection constraints and keeps working; the spec
  here does not change.
- [A project relies on exact matches] → the option is off by default.
- [The finder and its target list disagree] → the finder may offer "Bachelor"
  while the list on the target page matches exactly and shows nothing. Both
  fields are documented together, and the finder does not read the list's
  settings, because the target page can hold several lists.

## Migration Plan

None. Projects enable the option, assign only the specific category, and
drop their uid blacklists, title comparisons and whitelists.

## Open Questions

None.
