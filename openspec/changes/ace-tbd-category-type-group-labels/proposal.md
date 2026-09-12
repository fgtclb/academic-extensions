## Why

The type select of a category groups the types per extension, but each group
shows its raw key: `programs`, `projects`, `partners`. `academic_programs` and
`academic_projects` already declare a title and an icon for their group in
`CategoryTypes.yaml`, and one project declares groups of its own, yet the
loader reads only the types. ACE-364 asks for the group titles.

## What Changes

- `category_types` (`packages/fgtclb/typo3-category-types`) reads the
  `groups:` section of `CategoryTypes.yaml`: identifier, title and icon. A
  `priority` is read and kept for a later ordering change.
- The type select shows the group title as the heading of its types. A group
  without a declaration keeps showing its identifier.
- A later package can redeclare a group to change its title or icon,
  following package load order.
- A declared group icon is registered under the icon identifier
  `category_types.group.<identifier>`.
- `academic_partners` (`packages/fgtclb/academic-partners`) declares its
  `partners` group with a title, the one shipped group without a declaration.

The behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `typo3-category-types/category-type-groups`: how declared category type
  groups are named in the backend and made available as icons.

### Modified Capabilities

None.

## Impact

- Loader, registry and group model of `category_types`, the `sys_category`
  TCA override and the icon registration.
- A second cache entry for the groups.
- `academic_partners`: `CategoryTypes.yaml` and two labels (English and
  German).
- `Feature-` changelog entries in `category_types` and `academic_partners`.

## Non-goals

- Ordering groups or types. Groups keep their first-seen order, and a group
  `priority` has no effect yet; ordering types is
  `ace-tbd-category-type-priority-order`.
- Showing group icons in the type select; the option groups of a select carry
  a label only.
- Inline SVG rendering for group icons.
- A backport to branch `2`.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`cross-cutting-17`). Two of the six analysed projects carry their own code for
this today. The change implements the existing issue ACE-364 and is renamed to
`ace-364-category-type-group-labels` once that issue is verified in YouTrack
before implementation starts.

Implements ACE-364.
