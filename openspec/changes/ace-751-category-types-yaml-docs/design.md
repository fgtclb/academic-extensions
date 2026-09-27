## Context

What `typo3-category-types/Classes/Loader/CategoryTypeLoader.php` does on
`main`, package by package in active package order, re-read on 2026-09-27:

- only the `types` section is read; a `groups` section is ignored;
- a type is keyed `<group>.<identifier>`; `identifier` and `group` must be
  non-empty strings, or loading fails;
- `remove: true` unsets the key and skips the entry; a removal before the
  definition, or of a type nobody defines, has no effect;
- every type records the extension that last declared it;
- `useExisting: true` merges the entry onto the loaded type with a shallow
  `array_merge`; an override of a key that is not loaded yet fails with
  "Category type does not exist for override." (code 1678979375330);
- a redeclaration without `useExisting` builds the type from the new entry
  alone, and the assignment to the existing array key keeps its position.

`Configuration/TCA/Overrides/sys_category.php` builds one type select over
every group: the item value is the bare identifier, the item `group` is the
raw group key with no `itemGroups` label, and `typeicon_classes` is keyed by
the bare identifier.

The loader file is byte-identical on `main` and `2`; `2` has no `inlineIcon`.

The proposal assumed that nothing documented `useExisting`. ACE-664
(2026-09-15, after the analysis) added
`Documentation/Developers/CategoryTypes/Index.rst` on both branches, with the
key list, a relabel through `useExisting`, a removal and the `priority` key as
without effect. This change therefore extends that page rather than creating
`Developers/CategoryTypesYaml/`.

`typo3-category-types/Tests/Unit/Loader/CategoryTypeLoaderTest.php` already
covers removal, removal before the definition, removal of an undefined type,
the single-value override that keeps `inlineIcon`, the extension key after an
override and the rejected orphan override. Not covered: an override of
`icon` and `inlineIcon`, a redeclaration without `useExisting`, an override
that comes before its definition, and an override of a type an earlier
package removed.

## Goals / Non-Goals

**Goals:**

- The existing page answers "how do I change a shipped type" without reading
  the loader, the icon-only case first.
- Every statement about the loader and the TCA the change adds is backed by a
  test; the load order advice rests on core's package ordering, read in
  `PackageManager` and `PackageArtifactBuilder`.

**Non-Goals:**

- Documenting the category ViewHelpers or the TCA integration; they have
  their own pages.

## Decisions

### Extend the existing page, do not add a second one

Everything the proposal listed belongs to the section "Changing a type
another extension declares" and the key list of the ACE-664 page. Rejected: a
new `CategoryTypesYaml` page, which would repeat half of the existing one and
leave two places to update when the follow-up changes land.

### Document what the loader does, including its sharp edges

The page states that the `groups:` section has no effect today and that an
identifier must be unique across groups. Rejected: documenting the intended
behaviour ahead of its implementation; a project would configure it and
observe nothing. The follow-up changes are not named on the rendered page:
their names are internal and change when they are filed; each of them lists
updating this page as a task.

### `priority` stays as ACE-664 wrote it

The page already says that nothing sorts by `priority`, which is still true.
`ace-tbd-category-type-priority-order` rewrites that paragraph when it lands.

### The load order is stated as a dependency rule

The overriding extension declares the owning one in `require` of its
`composer.json` and in `depends` of its `ext_emconf.php` if it ships one. The
page does not describe which installation mode reads which file.

An extension removing a type of an extension that is not always installed
names it in `suggest` and `suggests` instead. The analysis had rejected
`suggest` as not ordering the packages reliably; that is not so. Both
`PackageManager` and the composer mode `PackageArtifactBuilder` sort through
`resolvePackageDependencies()`, which takes the suggestions of a package into
account whenever the suggested package is installed.

### Unit tests for the loader, one functional test for the TCA

The loader is unit-tested with fixture packages already, so the icon-only
override, the redeclaration, the early override and the override of a removed
type are unit tests. The
statement about the stored identifier and the unused `groups:` section is
about the `sys_category` TCA, which needs the loaded registry, so it gets a
functional test against the existing `test_category_types_group` fixture. The
group heading is asserted through core's `SelectItemProcessor`, which falls
back to the group key for a group without a label on v13 and v14 alike, so the
test pins what an editor sees rather than only the missing `itemGroups`
entry.

## Risks / Trade-offs

- [The page goes stale when the loader changes] → each of the three follow-up
  changes lists updating this page as a task, and their references are
  pointed at this page when this change is renamed.

## Open Questions

None.
