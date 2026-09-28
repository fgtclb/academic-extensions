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

The column has no `l10n_mode` and no synchronization, so a translation of a
partner page carries its own value. The coordinates are synchronized (ACE-562),
so a translated partner is drawn where its default record is
(`partnerMapPluginDrawsATranslatedPartnerAtItsDefaultCoordinates`).

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

### The record of the page language decides, synchronized

Planned as "the default language decides", with `l10n_mode => 'exclude'`. The
premise was checked during implementation and is false: on TYPO3 v13 and v14,
on a site with `fallbackType: fallback`, the map query in German evaluates the
constraint on the German record. A translation switched off leaves the partner
out of the German map, and a translation left on keeps it there while the
default record is switched off.

Stefan decided (2026-09-28): keep what the query does, and synchronize the
column. It gets `behaviour.allowLanguageSynchronization`, as
`geocode_latitude` and `geocode_longitude` have, so a translation follows its
default record and an editor can detach it for one language. DataHandler
synchronizes a translation that existed before the change as well, since a
field without a stored state counts as synchronized.

What synchronization does not repair is the stored value: it takes effect when
the default record is saved. Nothing read the switch before, so a translation
made while its default record was switched off can still hold that `0`, and the
partner would leave the map of that language with the update. The upgrade wizard
of ACE-562, which copied the coordinates, becomes
`SynchronizePartnerTranslationsUpgradeWizard` (identifier
`academicPartners_synchronizePartnerTranslations`) and copies the switch as
well. 3.0 is not released, so the wizard is renamed rather than joined by a
second one (Stefan). Each group, the coordinate pair and the switch, is compared
and detached on its own, so a translation out of step in one and detached in the
other is handled either way round.

Rejected: `l10n_mode => 'exclude'`. The query would still read the
translation, and a translation whose value differs today would keep it until
the default record is saved again, invisible in the backend. Rejected: leaving
the column as it is. A translation would copy the value once and never follow
the default record again.

### An Important changelog entry

The update changes what an existing site renders, but it only removes partners
an editor explicitly switched off. The entry names the query that lists them,
in every language, and the wizard:
`SELECT uid, sys_language_uid, title FROM pages WHERE doktype = 40 AND
show_on_map = 0 AND deleted = 0 AND t3ver_wsid = 0`.

### One property for a template

`Partner::isShownOnMap()` combines `isDrawable()` and the switch. The partial
checks it, and a site package template that guards a map for one partner uses
it instead of `{partner.drawable}`, which the changelog of ACE-562 recommended
before the partial existed. That changelog now shows the partial.

## Risks / Trade-offs

- [A site used the switch as a note without meaning it] → the partner leaves
  the map after the update. The changelog entry and its query are the remedy.
- [An overridden `Partner/Map.html` partial, or a site package template that
  guards with `{partner.drawable}`] → draws a single partner whose switch is
  off until it checks `{partner.shownOnMap}`. Named in the changelog.

## Open Questions

None.
