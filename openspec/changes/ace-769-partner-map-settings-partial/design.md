## Context

See `proposal.md` for the motivation. `Resources/Private/TypeScript/frontend/map.ts`
hard-codes the tile URL and attribution (:54-60), zoom 6 and maxZoom 18
(:57, :62), `fitBounds` padding 50 (:107) and the fallback centre
51.1657 / 10.4515 (:109). The markup (`#map`, `#map-partners`, `data-lat`,
`data-lng`, `data-name`, `data-link`) exists only in
`Templates/Partner/Map.html`, next to the filter form. The map set
(`Configuration/Sets/Map/`) has a `config.yaml` and no settings definitions, and
the plugin TypoScript is shared by all four content elements in
`Configuration/TypoScript/setup.typoscript`.

The partner page object already adds
`EXT:academic_partners/Resources/Private/Partials/` to its partial root paths
(`Configuration/TypoScript/Page/AcademicPartners.typoscript`), so the page
template `Pages/AcademicPartner.html` can render a partial of the extension.
`Partner::isDrawable()` exists since ACE-562.

The map has its own `MapSettings.xml` since ACE-727, with the filter fields in
a single `ROOT` sheet. The layout field is a second sheet, so the file moves to
`<sheets>` with the filter fields under `sDEF`, where their stored values
already are.

`Tests/JavaScript/map.test.ts` stubs Leaflet and records the `fitBounds`
options and the `setView` centre, not the zoom. The module runs once, when it
is evaluated, so a second test cannot start it on other markup.

A `PAGEVIEW` page object assigns the constants as `settings` and ignores the
`settings` of the object, so a value a page template needs goes through a data
processor option (`docs/architecture/page-type-rendering.md`).

## Goals / Non-Goals

**Goals:**

- Today's values as defaults, so an unconfigured site sees no change.
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
site on the static template gets them too, and `setup.typoscript` maps them to
`settings.map.*`. The partial writes them as `data-academic-partners-center-lat`,
`-center-lng`, `-zoom`, `-max-zoom`, `-padding`, `-tile-url` and
`-attribution` on `#map`, following the `data-<extension>-*` rule of
`docs/development/frontend-assets.md`. The attributes the map already had
(`data-lat` and the others on the partners) keep their names, since moving
them needs a deprecation of its own. `map.ts` reads each one,
validates numbers with `Number.isFinite()` and a range, and falls back to
today's value per attribute. An empty attribute counts as absent, because
`Number('')` is `0`. The centre is the one exception: latitude and longitude
fall back together, since half a centre is a place nobody configured. The
maximum zoom goes to the map as well as to the tile layer, so `fitBounds` is
capped by the map itself. The module exports its initialiser, as the study plan
module does, so each test starts it on its own markup.

The settings types are `string` for the centre and `int` with `min: 0` for
zoom, maximum zoom and padding. A `number` for the centre was rejected in
review: the number field of the site settings editor steps by 0.01 unless the
definition sets a `step`, identically on v13 and v14, so the browser refuses to
save a coordinate with four decimals, and a finer `step` runs into the float
arithmetic of the server side check for some values. The module checks the
range of the centre instead.

The analysis proposed the key prefix `academicPartners.map.*`, but the persons
convention is used instead so the two extensions read alike.

Rejected: an inline JSON configuration block. Data attributes keep the
configuration on the element it configures and survive an overridden
template that only changes the surrounding markup.

### A FlexForm layout field instead of a table column

`settings.map.layout` (`default` | `fullWidth`) in the map's FlexForm adds
`academic-partners-map--full-width` to the container. No other behaviour
changes, the theme styles the class.

Rejected: a `tt_content` column as one project uses it (without
`ext_tables.sql`). A FlexForm field covers the one content element without a schema change.

### The partial takes a list or one partner

`Partials/Partner/Map.html` accepts `partners` (the plugin passes its query
result) or `partner` (a page template passes the page's partner), and `map`,
the map settings. With `partner`, it renders nothing unless
`{partner.drawable}` is true. It carries the asset registrations, `#map` and
`#map-partners`, and `Templates/Partner/Map.html` keeps the filter form and the
empty state and renders the partial with `settings.map`.

The partner page gets the settings through the `partner-data` processor: its
option `map`, filled from the constants inside the doktype condition, becomes
the variable `mapSettings`. That works on `FLUIDTEMPLATE` and `PAGEVIEW` alike.
Rejected: mirroring the `PAGEVIEW` path `settings.plugin.tx_academicpartners.map`
in the `settings` of a `FLUIDTEMPLATE`, which works as well but contradicts the
documented convention for page templates.

One project already ships a `Partials/Partner/Map.html` of its own that takes
`partners`. It overrides the new partial, receives the argument it expects, and
keeps working.

### Decided: the default maximum zoom stays 18

`plugin.tx_academicpartners.map.maxZoom` defaults to 18, today's hard-coded
value, and a site that finds a single partner zoomed in too far lowers the setting.

Rejected: a lower default such as 15. It changes the map of every site, and
this change promises no visible change for an unconfigured one.

ACE-93, re-read before implementing, is a styling task of the ACE demo and is
Done. Its line on the map says the map zooms *out* too far ("Map zoomt zu weit
raus"), the opposite of the premise the analysis started from. For a map with
partners `fitBounds` decides the zoom, and only `padding` influences it. For a
map without partners `zoom` and the centre do. A minimum zoom is not part of
this change. ACE-571, also named by the analysis, is about the card height of
the partner list on mobile devices and has nothing to do with the map.

## Risks / Trade-offs

- [An overridden `Map.html` without the data attributes] → `map.ts` uses the
  defaults, and the override keeps working. Named in the changelog.
- [Attribution contains HTML] → integrator-supplied configuration, rendered by
  Leaflet as HTML, as today's hard-coded value is, and Fluid escapes it into the
  attribute.
- [The committed build changes] → `buildJs` output is committed in the same
  commit, and `checkJsBuildClean` guards it.

## Open Questions

None.
