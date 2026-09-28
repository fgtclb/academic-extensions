## Why

The backport of the `main` change of the same name, ACE-771, archived there as
`openspec/changes/archive/2026-09-28-ace-771-partner-map-libraries`.

The partner map loads Leaflet 1.9.4 and the marker cluster plugin 1.5.3 as
minified copies whose global `L` was renamed to `LeafletObject` by replacing the
text. That replacement also changed the SVG path command `L` inside Leaflet, so
the lines and polygons Leaflet draws get an invalid path, the area a cluster
covers on hover among them. The popup of a marker is assembled as an HTML
string from the partner's title, so a title with characters such as `<` or `&`
is not shown as written. The same four files and the same module are on this
branch.

## What Changes

- `academic_partners` (`packages/fgtclb/academic-partners`): `leaflet` 1.9.4
  and `leaflet.markercluster` 1.5.3 become exact dependencies of
  `Build/package.json`, and the build writes `Resources/Public/JavaScript/leaflet.js`
  and `markerCluster.js` from them, as classic scripts that publish the globals
  the replaced builds published: `LeafletObject` and `leaflet` with
  `noConflict()` and writable members, and `Leaflet.markercluster`.
- The popup is built from elements, so the title is shown as written.
- The paths, the global, the stylesheets and the marker icon do not change.
- Behaviour is identical on TYPO3 v12 and v13.

Not taken over from `main`: the ES modules below `vendor/`, the import map
entries and the marker icon data attribute. TYPO3 v12 renders no frontend
import map, so the libraries stay classic scripts here, and the template and
its stylesheets keep loading what they loaded, the extension's own marker icon
included.

## Capabilities

### New Capabilities

- `academic-partners/map-libraries`: which map libraries the partner map loads
  on this branch, and what its marker popup shows.

### Modified Capabilities

None.

## Impact

- `Build/package.json` and its lock file, `Build/esbuild.mjs`, the new
  `Build/vendor.mjs` and `Build/vendor/leaflet-global.mjs`, the two classic
  scripts (now under `checkJsBuildClean`), `map.ts` and its build.
- A new test that evaluates the two built scripts.
- An `Important-*.rst` changelog entry in `Documentation/Changelog/2.4/`.
- No PHP, no template, no schema change.

## Non-goals

- Leaflet 2.0, which is an alpha, and which the cluster plugin does not support.
- ES modules and an import map on this branch.

Relates to ACE-769.
