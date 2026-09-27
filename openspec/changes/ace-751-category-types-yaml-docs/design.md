## Context

The backport analysis compared every file the `main` change touches with this
branch (2026-09-27):

- `Classes/Loader/CategoryTypeLoader.php` and
  `Configuration/TCA/Overrides/sys_category.php` are byte-identical, so every
  loader and TCA statement of the page holds here unchanged.
- `Classes/Domain/Model/CategoryType.php` has no `inlineIcon`, and the unit
  fixture `base_types` does not set it.
- `Documentation/Developers/CategoryTypes/Index.rst` differs from `main` only
  by the `inlineIcon` key and the `isInlineIcon()` getter.
  `Documentation/Developers/Icons/Index.rst` has no section on inlining.
- `Tests/Unit/Loader/CategoryTypeLoaderTest.php` differs by the `inlineIcon`
  assertions only. The fixture packages are identical too, apart from
  `inlineIcon` in `base_types` and in the new `icon_override`.
- Core: `SelectItemProcessor` does not exist on TYPO3 v12.4; the grouping is
  the protected `TcaSelectItems::groupAndSortItems()` there, with the same
  fallback to the group key for a group without a label. The package ordering
  by `require`/`depends` and by `suggest`/`suggests` (`after-resilient`) is
  the same on v12 and v13.

## Goals / Non-Goals

**Goals:**

- The same page as on `main`, minus what this branch cannot do.
- Every statement about the loader and the TCA the change adds is backed by a
  test on v12 and v13.

**Non-Goals:**

- Anything about `inlineIcon`.

## Decisions

### The page is derived from the final `main` page

It is the reviewed text of `main` with the `inlineIcon` key, getter, default
and paragraph taken out. The icon-only example names only `icon`; its
paragraph says instead that the icon identifier stays the same, which follows
from `getIconIdentifier()` building it from group and identifier (covered by
`CategoryTypeTest`).

### The group heading test is split per core version

`Tests/Functional/Core12/Configuration/SysCategoryTypeGroupTest.php` calls the
protected v12 method through an anonymous subclass of `TcaSelectItems`;
`Core13/` calls `SelectItemProcessor`. Both carry the `not-core-*` group of
the version they do not run on, and phpstan excludes the folder of the other
version. Rejected: one class with a version switch, which phpstan cannot
analyse on either version, and an ignore entry for it. Also rejected: compiling
a `sys_category` record through `FormDataCompiler`, as
`academic-partners/Tests/Functional/Backend/FormEngine/PartnerSelectOrderTest.php`
does, which covers both versions in one class but needs a record and a backend
user for a statement about one core method.

## Risks / Trade-offs

- [The two branches' pages drift] → the page is derived from `main`'s text in
  the same round; later changes on category types are `main` only.

## Open Questions

None.
