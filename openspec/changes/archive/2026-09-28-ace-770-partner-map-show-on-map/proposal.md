## Why

A partner page has the switch "Show on map" (`pages.show_on_map`, on by
default), next to its coordinates. Nothing has ever read it: the partner map
draws every partner with coordinates that the filter matches, whatever the
switch says, and the map partial draws a single partner the same way. An editor
who switches a partner off the map still finds it there, with no hint why.

## What Changes

- `academic_partners` (`packages/fgtclb/academic-partners`): the partner map
  content element leaves out partners whose "Show on map" is switched off.
- The map partial `Partner/Map.html` renders no map for a single partner that
  is switched off, as it renders none for a partner without coordinates.
- The partner list, the partnerships list and the partnerships teaser are not
  affected: the switch is about the map.
- The switch is read from the record in the language of the page, as the map
  query reads every other column. It is synchronized with the default record,
  as the coordinates are, so a translation follows it unless an editor
  detaches it. The upgrade wizard that synchronized the coordinates copies the
  switch onto existing translations as well.
- Behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-partners/map-show-on-map`: which partners the partner map draws.

### Modified Capabilities

None.

## Impact

- A site where editors switched partners off sees those partners disappear from
  its maps after the update. That is the purpose, but it changes the rendered
  output, so it gets an `Important-*.rst` changelog entry with a query that
  lists the affected pages.
- The map query of `PartnerRepository`, the guard of `Partials/Partner/Map.html`
  and the TCA of `show_on_map`. No schema change, no new dependency.
- An overridden `Partner/Map.html` partial that renders a single partner keeps
  its own guard and has to add the switch itself. Named in the changelog.

## Non-goals

- Hiding a switched-off partner from the partner list.
- A site setting to ignore the switch.

## Source

Found in the review of ACE-769 (the partner map configuration, #787). The field
exists since the initial commit of the extension and was never read. Filed as
ACE-770 before this change was written, so the change carries the key from the
start. Affects `main` and `2`: a backport to `2` follows as a change of its own
on that branch.

Relates to ACE-769.
