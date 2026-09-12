## Why

Everything about the partner map is compiled into its frontend module: the
tile server and attribution, the initial and maximum zoom, the padding
around the partners and the fallback centre over Germany. A map with a
single partner therefore zooms to street level, and a site outside Germany
or with its own tile server has to copy and rebuild the script. The map
markup exists only inside the plugin template, next to the filter form, so a
partner page cannot show the partner's own location without copying it.

## What Changes

- `academic_partners` (`packages/fgtclb/academic-partners`) makes the map
  configurable per site through site set settings of the map set:
  - fallback centre latitude and longitude, initial zoom, maximum zoom,
    padding around the partners;
  - tile URL template and attribution.
- The defaults are today's values, so an unconfigured site sees no change.
- A map layout choice in the map content element: "content width" (default)
  or "full width", which adds a modifier class for the theme to style.
- The map markup moves into a reusable partial that takes a list of partners.
  The map plugin renders it as before; a partner page template can render it
  for the single partner of the page when that partner has coordinates.
- Behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-partners/map-configuration`: site-wide map settings, the layout
  choice and the reusable map partial.

### Modified Capabilities

None.

## Impact

- The frontend module `map.ts` and its committed build (the
  `checkJsBuildClean` gate sees a changed `map.js`).
- `Templates/Partner/Map.html` renders the new partial; an overridden
  `Map.html` keeps working and simply does not get the new attributes, so its
  map uses the defaults.
- A new `settings.definitions.yaml` in the map set, TypoScript constants and
  setup, a FlexForm field, labels.
- No schema change, no new dependency.

## Non-goals

- Rendering the map on the partner page by default; the partner page
  template stays as it is, the partial makes it possible.
- Marker icons, cluster styling or a different map library.
- A new `tt_content` column for full width.
- Backporting to branch `2`: the ES module exists only on `main`.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`listings-14`). Three of the six analysed projects carry their own code for
this today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-partner-map-settings-partial` when the issue is filed after
implementation.

Relates to ACE-93 and ACE-571.
