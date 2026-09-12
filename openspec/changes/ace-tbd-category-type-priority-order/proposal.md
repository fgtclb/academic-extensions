## Why

Every category type declared in a `CategoryTypes.yaml` carries a `priority`,
and nothing reads it. The order of the types within a group is package load
order plus YAML order. An override with `useExisting: true` keeps the type at
its original position, so a project cannot reorder the types that an
extension ships. Five projects therefore hard-code the order in template
overrides: of the program facts, of the list filter selects and of the backend
summary. One of them redeclared eight types and still had to hard-code the
order in a template.

## What Changes

- The types of each group are ordered by their `priority`, highest first. Types
  with the same priority keep today's order, that is load order plus YAML
  order.
- The flat list of all registered types follows the same rule within each
  group, so the backend type select of a category shows the configured order.
- A project reorders a shipped type with
  `{ identifier: degree, group: programs, useExisting: true, priority: 100 }`
  in its own `CategoryTypes.yaml`.
- Every consumer that lists the types of a group follows the order without a
  change of its own. For programs these are the facts on the program page and
  in the details plugin, the filter selects of the list plugin and the
  backend category summary of a program page.
- No shipped `CategoryTypes.yaml` sets `priority` today, so every type keeps
  priority `0` and the visible order does not change.

Affected extension: `category_types` (`packages/fgtclb/typo3-category-types`).
Consumers that pick the order up without a code change: `academic_programs`
(`packages/fgtclb/academic-programs`), `academic_partners`
(`packages/fgtclb/academic-partners`) and `academic_projects`
(`packages/fgtclb/academic-projects`).

The behaviour is identical on TYPO3 v13 and v14. The change is backported to
branch `2` and ships as a feature of 2.4, so its changelog entries live in
the 2.4 changelog folder on both branches.

## Capabilities

### New Capabilities

- `typo3-category-types/category-type-order`: the order in which the category
  types of a group are presented, and how an integrator changes it.

### Modified Capabilities

None.

## Impact

- The category type registry of `category_types` and its cached type list.
- Every frontend and backend output that iterates the types of a group.
- The program facts of `ace-tbd-program-facts-field-list`, which replace
  `Partials/Program/Categories.html` on `main` in 3.0, follow the same order
  when their field list is empty. Whichever change lands second extends the
  facts order test to priorities.
- A project whose own `CategoryTypes.yaml` already sets `priority` sees its
  types reordered after a cache flush.
- No database schema, TCA column or dependency change.
- A backport to branch `2`, as its own change after a backport analysis, with
  the same changelog files in `Documentation/Changelog/2.4/`.

## Non-goals

- A per content element order setting. Choosing and ordering the filter
  types of one list is `ace-tbd-program-list-filter-types`.
- Ordering the category records within one type. They keep `sorting`.
- Ordering the groups themselves. Group declarations are not parsed yet.
- New YAML syntax such as `position` or `after`.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`cross-cutting-15`, merged with `programs-studyplan-06`). Five of the six
analysed projects carry their own code for this today. No YouTrack issue is
filed yet; the change is renamed to `ace-<NNN>-category-type-priority-order`
when the issue is filed after implementation.

Depends on `ace-tbd-category-types-yaml-docs`, which documents the
`useExisting` override that carries the `priority`.
