## Why

The backport of the `main` change of the same name, ACE-770, archived there as
`openspec/changes/archive/2026-09-28-ace-770-partner-map-show-on-map`.

A partner page has the switch "Show on map", on by default, next to its
coordinates. Nothing has read it since the extension was created: the partner
map draws every partner with coordinates that its filter matches. The map
action, the demand, the repository and the coordinates wizard of this branch
are the ones `main` changed.

## What Changes

- `academic_partners` (`packages/fgtclb/academic-partners`): the partner map
  content element leaves out partners whose switch is off. The partner list is
  not affected.
- `Partner::isShownOnMap()` is the same rule for a template that renders a map
  for one partner.
- The switch is read from the record in the language of the page and is
  synchronized with the default record, as the coordinates are.
- The upgrade wizard that synchronized the coordinates is renamed and copies
  the switch onto existing translations as well. 2.4 is not released, so it is
  renamed rather than joined by a second one.
- Behaviour is identical on TYPO3 v12 and v13.

Not taken over from `main`: the map partial and its guard, which do not exist
on this branch, and the page of `docs/` on list plugin events, which does not
exist here either. The requirements go to the existing capability
`academic-partners/partner-map` of this branch rather than to a new one.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `academic-partners/partner-map`: gains the switch "Show on map", for the map,
  for a template that renders one partner, and for translations.

## Impact

- The map query of `PartnerRepository`, `Partner`, the TCA of `show_on_map`,
  the upgrade wizard and its tests, the map plugin test.
- An `Important-*.rst` entry in `Documentation/Changelog/2.4/`, and the entry
  of the coordinates change names the renamed wizard.

## Non-goals

- Hiding a partner from the partner list.

Relates to ACE-769.
