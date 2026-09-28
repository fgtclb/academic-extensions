## Context

`main` (ACE-771, #789) builds both libraries from their npm packages as ES
modules below `Resources/Public/JavaScript/vendor/<library>/<version>/`,
publishes them through the import map of the extension, and points Leaflet at
the extension's marker icon through a data attribute.

This branch differs in the one place that decides the approach: the frontend
of TYPO3 v12 has no import map and no `f:asset.module`. The template loads
`leaflet.js`, `markerCluster.js` and `frontend/map.js` with `f:asset.script`,
and the build bundles every script of the extension as an IIFE. `map.ts` reads
`window.LeafletObject`.

`Resources/Public/JavaScript/leaflet.js`, `markerCluster.js`,
`Resources/Public/Css/leaflet.css` and `markerCluster.css` are byte-identical to
the classic files `main` deprecated: the releases 1.9.4 and 1.5.3, with `L`
renamed by text replacement (checked by
reverting the rename and comparing with the npm tarballs). TYPO3 v12 and v13
ship no Leaflet.

## Goals / Non-Goals

**Goals:**

- The same releases, built from their packages, at the paths the template loads,
  under the gate.
- The popup shows the title as written.

**Non-Goals:**

- Any change a template, a stylesheet or a script of a project would notice.

## Decisions

### Classic scripts that publish their globals from an entry

`Build/vendor.mjs` writes both as IIFEs from a small entry each.
`Build/vendor/leaflet-classic.mjs` imports `leaflet-src.esm.js` and publishes a
plain copy of the namespace as `window.LeafletObject` and `window.leaflet`, with
`noConflict()`, as the UMD build did. `markercluster-classic.mjs` imports the
plugin's `src/index.js`, with `Build/vendor/leaflet-global.mjs` injected as its
`L`, so the plugin extends `window.LeafletObject`, and publishes
`window.Leaflet.markercluster` as its UMD build did. Both run in strict mode.
Both are minified with their licence comments kept, as large as the copies were.
`--list-outputs` names both files.

Rejected in review: esbuild's `globalName`. It published a namespace whose
members are getters, so a project plugin could no longer replace one, and it
lost `noConflict()` and the two aliases.

Rejected: the ES modules of `main`. They need an import map, which v12 does not
render. Rejected: bundling Leaflet into `map.js`. It would load Leaflet twice
for a project template that also loads `leaflet.js`, and change what a project
script reading `window.LeafletObject` finds.

### The popup is built from elements

As on `main`: an anchor with the link as `href` and the title in bold through
`textContent`.

### Tests

`map.test.ts` gains a partner whose title holds markup characters, and asserts
that each popup is an anchor around a bold title and that the title is shown as
written. It was red with the string popup.

`map-libraries.test.ts` runs the two built scripts in a jsdom window with
scripts enabled (`runClassicScripts()` of the harness) and asserts the globals
and aliases, a Leaflet the page already has as `L` left alone, the cluster group
on the same global, writable members, `noConflict()` and that
`SVG.pointsToPath()` writes `M1 2L3 4`. On the old copies only the last is red,
with `M1 2LeafletObject3 4`. On an earlier build with `globalName` the aliases,
the writable members and `noConflict()` were red. It reads the built files
rather than a source, which `docs/testing/javascript-tests.md` now explains.

## Risks / Trade-offs

- [A project relies on something of the UMD globals that is not listed here]
  → the test asserts the globals, `noConflict()`, the writable members and the
  plugin alias. The key sets of the old and the new `LeafletObject` were
  compared once in review and are identical.

## Open Questions

None.
