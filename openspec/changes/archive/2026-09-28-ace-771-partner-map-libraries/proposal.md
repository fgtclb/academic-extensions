## Why

The partner map loads Leaflet and the marker cluster plugin as two minified
classic scripts that were copied into the extension and edited by hand: their
global `L` was renamed to `LeafletObject` by replacing the text. That
replacement also hit the SVG path command `"L"` inside Leaflet, so every line
and polygon Leaflet draws gets an invalid path, among them the area a cluster
covers when the pointer rests on it. Nobody can tell from the files which
release they are, and updating them means repeating the edit by hand.

The popup of each marker is assembled as an HTML string from the partner's
title. A title with characters such as `<` or `&` is therefore not
shown as it is written.

## What Changes

- `academic_partners` (`packages/fgtclb/academic-partners`): Leaflet 1.9.4 and
  `leaflet.markercluster` 1.5.3, the newest stable releases, become pinned npm
  dependencies of the asset build. The build writes them as ES modules below
  `Resources/Public/JavaScript/vendor/<library>/<version>/`, with their
  licence and stylesheets, and the import map of the extension publishes them
  under the bare specifiers `leaflet` and `leaflet.markercluster`.
- The map module imports both instead of reading a global. No global is set
  any more.
- The popup is built from elements, so the title is shown as written.
- The map partial loads the new stylesheets and no classic scripts.
- The classic files `Resources/Public/JavaScript/leaflet.js`,
  `markerCluster.js`, `Resources/Public/Css/leaflet.css` and
  `markerCluster.css` stay unchanged for projects that load them themselves.
  They are deprecated and removed in 4.0.
- Behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-partners/map-libraries`: which map libraries the partner map
  loads, and what its marker popup shows.

### Modified Capabilities

None.

## Impact

- `Build/package.json` and its lock file, `Build/esbuild.mjs` (or the file
  that lists the build passes), `Configuration/JavaScriptModules.php`,
  `Partials/Partner/Map.html`, `map.ts` and its committed build, the new
  vendor files (all under `checkJsBuildClean`), a `_dependencies.d.ts` for the
  types of the bare specifiers, the marker images copied to
  `Resources/Public/Images/Map/`, and the JavaScript test harness (the stubs
  of the resolve hook).
- A project script that used the global `LeafletObject` from the scripts the
  map partial loaded has to load the deprecated classic files itself, or
  import the modules. Named in the changelog.
- No PHP, no schema change.

## Non-goals

- Leaflet 2.0. It is an alpha release, and the marker cluster plugin supports
  Leaflet `^1.3.1` only.
- Changing what the map shows: markers, clusters, popup content and styling
  stay as they are.
- Removing the classic files in 3.x.

## Source

Found in the review of ACE-769 (the partner map configuration, #787), filed as
ACE-771. The same files are on branch `2`, and a backport follows as a change
of its own on that branch. The change is published together with its
implementation.

Relates to ACE-769.
