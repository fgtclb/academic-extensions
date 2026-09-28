## Context

`pages.show_on_map` is a `check` column with `default => true`
(`Configuration/TCA/Overrides/pages.php`, palette `geocode`, next to the
coordinates), declared in `ext_tables.sql` as `tinyint(1) unsigned DEFAULT '1'
NOT NULL`. The partner model maps it (`Partner::getShowOnMap()`). No
controller, repository or template reads it, and `git log -S showOnMap` shows
it was never read since the initial commit.

The map action (`PartnerController::mapAction()`) sets
`PartnerDemand::setDrawableOnly(true)` after `ModifyPartnerDemandEvent`, so a
listener may widen the map's demand but not back onto partners without
coordinates (ACE-562). `PartnerRepository::findByDemand()` adds
`drawableCoordinatesConstraint()` for it.

The partial `Partials/Partner/Map.html` (ACE-769) renders a single `partner`
only when `{partner.drawable}` is true.

The column has no `l10n_mode`, so a translation of a partner page carries its
own value. The map draws a translated partner at the coordinates of its default
language record (`partnerMapPluginDrawsATranslatedPartnerAtItsDefaultCoordinates`).

Branch `2` has the same map action, demand and repository, and no map partial.

## Goals / Non-Goals

**Goals:**

- The map draws what the editor switched on, in the plugin and in the partial.
- One rule for all languages.

**Non-Goals:**

- The partner list, and any other plugin of the extension.
- A migration of stored values: the switch is on by default, and a page where
  an editor switched it off is exactly the page that has to leave the map.

## Decisions

### The switch is part of what the map can draw

`findByDemand()` adds `showOnMap = 1` to the constraint it adds for
`getDrawableOnly()`, and `drawableOnly` is documented as "the partners the map
can draw": coordinates and the switch. It stays set after the demand event, so
a listener cannot bring a switched-off partner back onto the map.

Rejected: a second demand flag. Every caller that sets one would have to set
the other, and there is exactly one caller.

### The partial checks both

`Partials/Partner/Map.html` renders a single partner only when
`{partner.drawable}` and `{partner.showOnMap}` are both true. A list of
partners is drawn as given, since it comes from a query that already applied
the rule.

### The default language decides

The switch describes the partner, not a translation of its page. The map
already draws a translated partner at its default coordinates. The column gets
`l10n_mode => 'exclude'`, so a translation shows the value of the default
record and cannot hold a value of its own that would be ignored.

To verify during implementation: which record the query evaluates for a
translated partner, on both core versions, with a translation whose stored
value differs from its default record. The test pins the result either way.

### An Important changelog entry, no upgrade wizard

The update changes what an existing site renders, but it only removes partners
an editor explicitly switched off. The entry names the query that lists them:
`SELECT uid, title FROM pages WHERE doktype = 40 AND show_on_map = 0 AND
sys_language_uid = 0 AND deleted = 0`.

## Risks / Trade-offs

- [A site used the switch as a note without meaning it] → the partner leaves
  the map after the update. The changelog entry and its query are the remedy.
- [An overridden `Partner/Map.html` partial] → keeps its own guard and draws a
  switched-off single partner until it adds the check. Named in the changelog.

## Open Questions

None.
