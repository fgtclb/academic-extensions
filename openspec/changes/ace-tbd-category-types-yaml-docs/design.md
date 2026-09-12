## Context

What `typo3-category-types/Classes/Loader/CategoryTypeLoader.php` does on
main, package by package in active package order:

- only the `types` section is read; a `groups` section is ignored;
- a type is keyed `<group>.<identifier>`; `identifier` and `group` must be
  non-empty strings, or loading fails;
- `remove: true` unsets the key and skips the entry; a removal before the
  definition, or of a type nobody defines, has no effect;
- every type records the extension that last declared it;
- `useExisting: true` merges the entry onto the loaded type with a shallow
  `array_merge`; an override of a key that is not loaded yet fails with
  "Category type does not exist for override." (code 1678979375330).

`typo3-category-types/Tests/Unit/Loader/CategoryTypeLoaderTest.php` already
covers removal, removal before the definition, the single-value override and
the rejected orphan override, with fixture packages under
`Tests/Unit/Fixtures/Packages/`. `Documentation/Developers/Icons/Index.rst`
documents `inlineIcon`. The analysis assumed the override needed a new
functional test; the merge itself is already pinned by a unit test, so only
the icon-only case is new.

## Goals / Non-Goals

**Goals:**

- One page that answers "how do I change a shipped type" without reading the
  loader.
- Every statement on the page is backed by a test.

**Non-Goals:**

- Documenting the category ViewHelpers or the TCA integration; they have
  their own pages.

## Decisions

### Document what the loader does, including its sharp edges

The page states that `priority` and `groups` have no effect today, and that
identifiers must be unique across groups. Rejected: documenting the intended
behaviour ahead of its implementation; a project would configure it and
observe nothing.

### Decided: this page lands first and documents `priority` as without effect

This change lands before `ace-tbd-category-type-priority-order`, which depends
on it, and documents `priority` as read but without effect, because nothing
reads `CategoryType::getPriority()` on main and every statement on the page is
backed by a test. The priority change then rewrites that paragraph to the
direction it implements: higher value first, stable on load order for equal
values, matching the `priority` of the Symfony and TYPO3 service container.
Documenting the direction ahead of its implementation was rejected; a project
would configure it and observe nothing.

### The page sits next to the icons page

It goes under `Documentation/Developers/` and links the icons page for
`inlineIcon` instead of repeating it. Rejected: the extension's `README.md`,
which the repository keeps to a summary.

### The load order is stated as a dependency rule

The overriding extension declares the owning one in `require` of its
`composer.json` and in `depends` of its `ext_emconf.php`, which is what orders
the active packages. Rejected: advising a `suggest`, which does not order the
packages reliably.

### Test the icon-only override with a unit fixture

A fixture package overrides `icon` and `inlineIcon` only; the test asserts
that both change while `title` and `group` survive. The loader is unit-tested
with fixture packages already, so no functional test is needed.

## Risks / Trade-offs

- [The page goes stale when the loader changes] → each of the three follow-up
  changes lists updating this page as a task.

## Open Questions

None.
