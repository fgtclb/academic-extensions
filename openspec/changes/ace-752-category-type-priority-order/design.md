## Context

The `main` change decides where the order is made and why; this change
carries the same decisions to branch `2`. A file-level diff between the
branches at `7d6d8c581` (`main`) and `bf10487e4` (`2`) gave:

- `CategoryTypeLoader` is identical.
- `CategoryTypeRegistry` differs by four lines, all docblocks: the class has
  no `@api` tag on `2`, and the `toArray()` shape has no `inlineIcon`. The
  analysis had said one line; the difference is the same in kind.
- `CategoryType` has no `inlineIcon` on `2`, so the unit tests that compare a
  whole type drop that key.
- The consumers differ. There is no filter types items provider
  (`CategoryTypeItemsProcFunc`) and no program facts builder on `2`: the
  program page and the details plugin render `Program/Categories`, which
  iterates `getAllCategoriesByType()`. The list filters (`FilterTypeResolver`),
  the page module summary and the type select of a category exist on both
  branches.

## Goals / Non-Goals

**Goals:**

- The same ordering rule as on `main`, with the same two changelog files.

**Non-Goals:**

- Anything beyond the registry: no loader, template or TCA change.

## Decisions

### The registry change ports unchanged

`attach()` sorts every group by priority with the stable `uasort()`, highest
first, and rebuilds the flat list group by group in a `finally` block, exactly
as on `main`. `uasort()` is stable from PHP 8.0 on, and branch `2` requires
PHP 8.1.

### The functional test follows the templates of `2`

The test class and its fixture extension raise `location` above `degree`, as
on `main`. The program page and the details plugin are asserted on the list
`Program/Categories` renders inside the `academic-programs-detail` wrappers,
instead of the facts list of `main`. The fixture extension follows the shape
of the other fixture extensions of this branch: no version and no
`providesPackages` in its `composer.json`.

### The documentation leaves out what `2` does not have

The developer page drops the reference to the filter types select, and the
`docs/` page drops the program facts, since neither exists here. The two
changelog files stay byte-identical, which is why their `main` wording names
only consumers both branches have.

## Risks / Trade-offs

- [A package outside this repository already sets `priority`] → its types
  reorder after a cache flush. Named in the `Important-` changelog entry.

## Migration Plan

Nothing to migrate. A project removes the type order from its template
overrides and sets `priority` on `useExisting` overrides instead.

## Open Questions

None.
