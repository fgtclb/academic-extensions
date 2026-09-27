## Why

The backport of the `main` change of the same name, ACE-752, archived there as
`openspec/changes/archive/2026-09-27-ace-752-category-type-priority-order`.

Every category type declared in a `CategoryTypes.yaml` carries a `priority`,
and nothing reads it. The order of the types within a group is package load
order plus YAML order, and an override with `useExisting: true` keeps the type
at its position, so a project cannot reorder the types an extension ships.
The projects that hard-code a type order in their templates run 2.x, which is
why the feature ships with 2.4 rather than waiting for 3.0.

## What Changes

- The types of each group are ordered by their `priority`, highest first. Types
  with the same priority keep today's order, that is load order plus YAML
  order.
- The flat list of all registered types follows the same rule within each
  group, group by group.
- A project reorders a shipped type with
  `{ identifier: degree, group: programs, useExisting: true, priority: 100 }`
  in its own `CategoryTypes.yaml`.
- Every consumer that lists the types of a group follows the order without a
  change of its own: the categories of a program page and of the program
  details plugin, the filter selects of the program, partner and project
  lists, the categories of a partner or project page, the page module category
  summary and the type select of a category.
- No shipped `CategoryTypes.yaml` sets `priority`, so the visible order does
  not change until a project sets one.

Affected extension: `category_types` (`packages/fgtclb/typo3-category-types`).
The behaviour is identical on TYPO3 v12 and v13. The two changelog files in
`Documentation/Changelog/2.4/` are byte-identical to those on `main`.

## Capabilities

### New Capabilities

- `typo3-category-types/category-type-order`: the order in which the category
  types of a group are presented, and how an integrator changes it.

### Modified Capabilities

None.

## Impact

- The category type registry of `category_types` and its cached type list.
- Every frontend and backend output that iterates the types of a group.
- A project whose own `CategoryTypes.yaml` already sets `priority` sees its
  types reordered after a cache flush.
- No database schema, TCA column or dependency change.

## Source

ACE-752, a subtask of the category types epic ACE-42. Depends on ACE-751,
which documents the `useExisting` override that carries the `priority`.
