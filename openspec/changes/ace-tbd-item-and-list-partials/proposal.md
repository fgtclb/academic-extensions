## Why

The profile item and list partials of `academic_persons`
(`packages/fgtclb/academic-persons`) are single blocks. The item holds the
detail link, the name, the contracts and the image in one file, and the list
hard-wires the group header, the grid, the empty state and the pagination. A
project that needs the academic title in the name, a placeholder image or a
detail link outside the plugin copies the whole file and then misses every
upstream correction. Five projects do so today.

## What Changes

- `Profile/Item`, `Profile/List/ItemList`, `Profile/List/Pagination` and
  `Profile/List/AlphabetPagination` stay the entry points, with unchanged
  arguments.
- They delegate to small partials: detail link, name, image and contracts for
  the item, and group header, items, result count and empty state for the
  list. An integrator overrides one of those instead of the template.
- A caller without plugin settings can pass the detail page to the item
  explicitly.
- Every partial gets a stable BEM class. The existing classes stay.
- The name shows the academic title when the profile has one. This is the only
  visible change in the default output.

The behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-persons/profile-template-partials`: which parts of the profile
  item and list an integrator can override on their own, and what the default
  output is.

### Modified Capabilities

None.

## Impact

- `academic_persons`: the item and list partials, the card, selected-profiles
  and list templates, new partial files.
- `academic_contacts4pages` (`packages/fgtclb/academic-contact4pages`) renders
  the persons item and profits without a change of its own. Its partial root
  paths include the persons partials, so a project override has to be added to
  its paths as well.
- A functional test fixture extension that overrides one partial.
- No PHP, setting or schema change.

## Non-goals

- A new visual design, or dropping the Bootstrap classes.
- View modes such as a table (a separate change, which builds on this one).
- Image sizes, crop variants and placeholders (a separate candidate).
- Splitting the detail view, which is already built from partials.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`persons-display-07`). Five of the six analysed projects carry their own code
for this today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-item-and-list-partials` when the issue is filed after
implementation.
