## Why

The server half of the 3.x fix for the partner map, which
`ace-708-partner-map-client-guards` deliberately left out. That change taught
the frontend module to refuse a partner it cannot draw; this one stops such a
partner from reaching the page at all, and repairs what is already stored.

`PartnerController::mapAction()` feeds the map from
`PartnerRepository::findByDemand()`, which carries no geocode constraint.
`Partner` types both coordinates as non-nullable `float` defaulting to `0`, so a
partner that was never geocoded arrives in the template as the valid pair `0/0`.
The module drops it now, but the record is still delivered, still counted in the
list the template iterates, and a map with nothing left to draw still renders an
empty canvas centred on Germany with the Leaflet assets loaded. A detail page
drawing one partner's own location has no query to constrain at all.

## What Changes

- The map shows only partners that have a location. **The list is unchanged**.
- With nothing to draw, the plugin renders a message instead of an empty canvas,
  and loads no Leaflet assets.
- `Partner::isDrawable()` is the same rule for a template that renders one
  partner rather than a query result.
- The coordinate columns become `allowLanguageSynchronization`, and an upgrade
  wizard brings existing translations in line.
- `GeocodeCommand` writes through the DataHandler, because only that runs the
  synchronization, and fails when the write is refused.

Behaviour is identical on TYPO3 v12 and v13.

## Capabilities

### Modified Capabilities

- `academic-partners/partner-map`: gains which partners reach the page, what the
  plugin renders when none does, and that a translation is drawn at its default
  record's place.

### New Capabilities

None.

## Impact

- Five classes changed, two added (`Service\GeocodeWriteContext`,
  `Upgrades\SynchronizePartnerCoordinatesUpgradeWizard`).
- `Partner/Map.html`, the TCA of both coordinate columns, one label in two
  languages.
- The first rendering test of a partners plugin on this branch, a DataHandler
  test for the TCA declaration, a wizard test, and a geocoding command test with
  a stub for the geocoding service.
- `docs/architecture/translation-synchronization.md` and a
  `Documentation/Changelog/2.4/Important-*.rst`.
- No database schema change.

## Non-goals

- **Changing the column type.** The pair `0/0` is matched as a string in the
  spelling the command writes, because a numeric comparison is not portable
  while the column is `VARCHAR(20)` - Extbase's QOM has no cast, `column + 0` is
  a type error on PostgreSQL, and `CAST` names its target type differently per
  platform. A hand-entered `0.0` therefore reaches the module, which drops it.
  The 3.x line tracks the column type as ACE-566 and has not done it either.
- **`show_on_map`.** Still read by no query and no template, so unticking it
  changes nothing. Untouched here.

## Source

Backport of the server half of ACE-562 of `main`, re-derived. The file level
diff found `PartnerRepository`, `PartnerDemand`, `PartnerController` and
`Partner` identical between the branches apart from exactly those additions.
Three things did not carry over and are re-derived rather than copied: this
branch already requires EXT:install and already loads it for every functional
test, so that part of the `main` change does not apply; `Map.html` here loads
its script as a classic `f:asset.script` rather than as a module; and this
branch has no `docs/architecture/translation-synchronization.md`, so the page is
written for it.
