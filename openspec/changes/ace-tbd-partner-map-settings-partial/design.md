## Context

See `proposal.md` for the motivation. `Resources/Private/TypeScript/frontend/map.ts`
hard-codes the tile URL and attribution (:54-60), zoom 6 and maxZoom 18
(:57, :62), `fitBounds` padding 50 (:107) and the fallback centre
51.1657 / 10.4515 (:109). The markup (`#map`, `#map-partners`, `data-lat`,
`data-lng`, `data-name`, `data-link`) exists only in
`Templates/Partner/Map.html`, next to the filter form. The map set
(`Configuration/Sets/Map/`) has a `config.yaml` and no settings definitions;
the plugin TypoScript is shared by all four content elements in
`Configuration/TypoScript/setup.typoscript`.

The partner page object already adds
`EXT:academic_partners/Resources/Private/Partials/` to its partial root paths
(`Configuration/TypoScript/Page/AcademicPartners.typoscript`), so the page
template `Pages/AcademicPartner.html` can render a partial of the extension.
`Partner::isDrawable()` exists since ACE-562.

The list and the map share `ListSettings.xml`; `ace-tbd-partner-list-pagination`
gives the map a `MapSettings.xml`. Whichever of the two changes lands first
introduces that file; the other adds its field to it.

`Tests/JavaScript/map.test.ts` stubs Leaflet and records the `fitBounds`
options and the `setView` centre, not the zoom.

## Goals / Non-Goals

**Goals:**

- Today's values as defaults; no visible change for an unconfigured site.
- One markup source for the plugin and any page template.

**Non-Goals:**

- Several maps on one page: the element ids stay `map` and `map-partners`.
- A consent layer for the external tile server.

## Decisions

### Site settings, passed as data attributes

Settings in `Configuration/Sets/Map/settings.definitions.yaml`, named like the
existing `academic_persons` settings (`plugin.tx_academicpersons.pagination.*`):
`plugin.tx_academicpartners.map.centerLatitude`, `.centerLongitude`, `.zoom`,
`.maxZoom`, `.padding`, `.tileUrl`, `.attribution`. The same names are
TypoScript constants in `constants.typoscript` with today's values, so a
site on the static template gets them too; `setup.typoscript` maps them to
`settings.map.*`. The partial writes them as `data-center-lat`,
`data-center-lng`, `data-zoom`, `data-max-zoom`, `data-padding`,
`data-tile-url` and `data-attribution` on `#map`. `map.ts` reads each one,
validates numbers with `Number.isFinite()` and falls back to today's value
per attribute.

The analysis proposed the key prefix `academicPartners.map.*`; the persons
convention is used instead so the two extensions read alike.

Rejected: an inline JSON configuration block. Data attributes keep the
configuration on the element it configures and survive an overridden
template that only changes the surrounding markup.

### A FlexForm layout field instead of a table column

`settings.map.layout` (`default` | `fullWidth`) in the map's FlexForm adds
`academic-partners-map--full-width` to the container. No other behaviour
changes; the theme styles the class.

Rejected: a `tt_content` column as one project uses it (without
`ext_tables.sql`). A FlexForm field covers the one content element without a schema change.

### The partial takes a list or one partner

`Partials/Partner/Map.html` accepts `partners` (the plugin passes its query
result) or `partner` (a page template passes the page's partner). With
`partner`, it renders nothing unless `{partner.drawable}` is true. It carries
the asset registrations, `#map` and `#map-partners`; `Templates/Partner/Map.html`
keeps the filter form and the empty state and renders the partial.

### Decided: the default maximum zoom stays 18

`plugin.tx_academicpartners.map.maxZoom` defaults to 18, today's hard-coded
value, and ACE-93 is resolved by configuration: a site that finds a single
partner zoomed in too far lowers the setting.

Rejected: a lower default such as 15. It changes the map of every site, and
this change promises no visible change for an unconfigured one. ACE-93 was
reported against the styling of the ACE demo, not as a defect of the default.
Its text was not re-read for this decision; if it describes the opposite
direction (zooming out too far), the setting still is the remedy, through
`zoom` and `padding`.

## Risks / Trade-offs

- [An overridden `Map.html` without the data attributes] → `map.ts` uses the
  defaults; the override keeps working. Named in the changelog.
- [Attribution contains HTML] → integrator-supplied configuration, rendered by
  Leaflet as HTML, as today's hard-coded value is; Fluid escapes it into the
  attribute.
- [The committed build changes] → `buildJs` output is committed in the same
  commit; `checkJsBuildClean` guards it.

## Open Questions

None.
