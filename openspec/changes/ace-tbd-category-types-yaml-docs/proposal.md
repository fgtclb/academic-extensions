## Why

`category_types` lets a later package change a category type that another
package ships: `useExisting: true` merges the given keys onto the loaded type,
and `remove: true` drops it. Neither is documented; only `inlineIcon` is, on
the icons page. Projects therefore redeclare complete types to swap an icon.
One project redeclares twelve of them, and its labels broke when the upstream
label keys moved.

## What Changes

- A new page `Documentation/Developers/CategoryTypesYaml/` in `category_types`
  (`packages/fgtclb/typo3-category-types`), linked from the developer index:
  - every key of a type (`identifier`, `group`, `title`, `icon`,
    `inlineIcon`, `priority`, `remove`, `useExisting`) with its default;
  - the `groups:` section and its keys;
  - examples: an icon-only override (`useExisting: true`, `icon`,
    `inlineIcon: false`), a relabel and a removal;
  - the load order requirement: the overriding extension must require the
    owning one, otherwise loading fails with "Category type does not exist for
    override.";
  - what the loader does not do today: `priority` and the `groups:` section
    are read by nothing, and an identifier must not repeat across groups; each
    point names the change that addresses it.
- One loader unit test pinning the icon-only override.

Nothing changes at runtime, on TYPO3 v13 or v14.

## Capabilities

### New Capabilities

None. The change documents existing behaviour and sets `skip_specs: true`.

### Modified Capabilities

None.

## Impact

- One documentation page and a toctree entry in `category_types`.
- One unit test with a fixture package.
- No code, configuration or dependency change.

## Non-goals

- A PSR-14 event to modify category types; the YAML keys already do it, and a
  second mechanism would compete with them.
- Changing the loader. The sharp edges it has are documented as they are, and
  fixed by `ace-tbd-category-type-identifier-collision`,
  `ace-tbd-category-type-group-labels` and
  `ace-tbd-category-type-priority-order`.
- A changelog entry: nothing an installation observes changes.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`cross-cutting-14`). All six analysed projects carry their own code for this
today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-category-types-yaml-docs` when the issue is filed after
implementation.

Relates to ACE-547.
Relates to ACE-575.
