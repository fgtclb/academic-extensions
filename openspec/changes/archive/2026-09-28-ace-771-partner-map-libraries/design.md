## Context

`Resources/Public/JavaScript/leaflet.js` is the minified `dist/leaflet.js` of
Leaflet 1.9.4 with every `L` replaced by `LeafletObject`, and `markerCluster.js`
is the minified `dist/leaflet.markercluster.js` of 1.5.3 with the same
replacement and without its source map comment. Verified 2026-09-28 by
reverting the replacement and comparing with the npm tarballs: identical. The
replacement also changed Leaflet's SVG path builder from `(i?"L":"M")` to
`(i?"LeafletObject":"M")`, so every polyline and polygon path is invalid.

`Partials/Partner/Map.html` (ACE-769) registers both as `f:asset.script` and
the module `@fgtclb/academic-partners/frontend/map.js` as `f:asset.module`.
`map.ts` reads `window.LeafletObject` and builds the popup as a string of
markup from the partner's title.

TYPO3 ships no Leaflet on v13 or v14 (checked: no file and no import map entry
in either vendor tree). `docs/development/frontend-assets.md` says a library
that has to be shipped is committed below
`Resources/Public/JavaScript/vendor/<library>/<version>/` with its licence,
published under a bare specifier by `Configuration/JavaScriptModules.php`, and
never imported by path.

Leaflet 1.9.4 ships `dist/leaflet-src.esm.js`, an ES module. The marker
cluster plugin 1.5.3 ships only UMD builds and ES sources that use the global
`L`. The newest Leaflet is 2.0.0-alpha.1, which the plugin does not support.

`Tests/JavaScript/map.test.ts` installs a global `LeafletObject` stub. The
resolve hook of the test harness has a list of stubbed library specifiers.

Branch `2` has the same classic files, the same `map.ts` and the same build.

## Goals / Non-Goals

**Goals:**

- The libraries come from their published packages, at a pinned version that
  the lock file and the directory name both show.
- The build writes them, so `checkJsBuildClean` guards them like every other
  artifact.
- The popup shows the title as written.

**Non-Goals:**

- Minifying this repository's own output.

## Decisions

### Build the vendor modules from the packages

`leaflet` and `leaflet.markercluster` become exact-version dependencies of
`Build/package.json`. A build pass writes:

- `vendor/leaflet/1.9.4/leaflet.js` from `leaflet/dist/leaflet-src.esm.js`,
  and `leaflet.css` with its `images/`, plus the licence file.
- `vendor/leaflet.markercluster/1.5.3/leaflet.markercluster.js`, an ES module
  bundled by esbuild from the plugin's sources with `leaflet` external and the
  global `L` injected from `Build/vendor/leaflet-global.mjs`: a copy of the
  Leaflet namespace, because a module namespace cannot take the members the
  plugin adds (`L.MarkerClusterGroup`, `L.DistanceGrid` and others). The copy
  holds the same classes, so what the plugin includes into `L.Marker` reaches
  every marker. Plus `MarkerCluster.css`, `MarkerCluster.Default.css` and the
  licence file.

Both modules are minified by esbuild, with the licence comments kept: the
build leaves our own output readable, but vendor code is not ours to read, and
the page should not load more than the release does (146 KB and 33 KB, as the
old copies). The pass lives in `Build/vendor.mjs`, called by `esbuild.mjs`.

Stylesheets, images and licences are copied as the packages ship them, except
that text files get LF line endings and every file mode `0644`. Leaflet ships
its stylesheet and licence with CRLF, and the cluster package ships its files
executable. Both would make `checkJsBuildClean` depend on the machine, since git
stores the mode and a machine's attributes decide the line endings.

`--list-outputs` names the directory of each library, `vendor/<library>/`, so
the gate deletes it and rebuilds it, and a directory a previous version left
behind shows up as a deletion.

Rejected: bundling both libraries into `map.js`. It hides the version and the
licence in our own module and contradicts the documented vendor rule.
Rejected: keeping the classic scripts with a correct global rename. It keeps a
global, and it needs a patch step that the packages do not need as modules.

### Bare specifiers in the import map

`Configuration/JavaScriptModules.php` maps `leaflet` and
`leaflet.markercluster` to their versioned files. `map.ts` imports them.
The types are ambient `declare module` blocks in the extension's
`Resources/Private/TypeScript/frontend/_dependencies.d.ts`, as
`academic_persons_edit` declares its vendor specifiers, with the small surface
the map calls.

To verify during implementation: that the frontend import map of TYPO3 v13
and v14 contains both specifiers on a page that renders the map, and that the
cluster module and the map module share one Leaflet instance.

### The popup is built from elements

The module creates an anchor with the link as `href` and a bold element whose
`textContent` is the title, and passes the anchor to `bindPopup()`.

### The local width rule moves into the extension's stylesheet

The vendored `leaflet.css` differed from the release in one rule:
`width: auto !important` on tiles and markers, where the release has
`width: auto`, so that a theme rule such as `img { width: 100% }` cannot
stretch them. The rule moves into `Scss/frontend/map.scss`, and the release's
stylesheet is used unchanged.

The vendored `markerCluster.css` was the release's `MarkerCluster.Default.css`
only. The partial now loads `MarkerCluster.css` as well, which carries the
animation of clusters that open and close.

### The marker icon of the extension stays

`Css/images/marker-icon.png` and `marker-icon-2x.png` are not Leaflet's: they
are a dark pin of this extension (32 by 40 pixels, drawn 41 pixels high, as
Leaflet sizes its default icon), which the old stylesheet made Leaflet use
through the background image of `.leaflet-default-icon-path`. The three images
are copied to `Resources/Public/Images/Map/`, and the partial writes the URL of
the icon as `data-academic-partners-marker-icon` on `#map`. The map module
takes the directory from it and gives every marker a `new Icon.Default({
imagePath })`: per marker, not on Leaflet's class, since another module of the
page may load the same Leaflet through the import map. Without the attribute,
as in an overridden template that lacks it, the markers get Leaflet's own icon.

Rejected: a rule for `.leaflet-default-icon-path`. In `map.scss` the build
would give the image a hashed name, and Leaflet only reads a URL that ends in
`marker-icon.png`. Registered inline by the partial, it would add a `<style>`
element to every map page, which not every site accepts, and it would need
different ViewHelper arguments on TYPO3 v13 and v14. The data attribute follows
the way the map already takes its configuration.

### The classic files stay, deprecated

The shipped partial stops loading them. The files stay where they are,
unchanged, until 4.0, so a project that loads them from its own template keeps
working. A `Deprecation-*.rst` names them and the removal version. An
`Important-*.rst` names what an installation sees: the map loads other files,
lines and cluster areas are drawn, the globals of the classic files are gone,
and a title with `<` or `&` is shown as written.

### Tests

The resolve hook stubs `leaflet` and `leaflet.markercluster` with a recording
stub module, the way it stubs CKEditor and CropperJS. `map.test.ts` moves from
the global stub to that module and reads the recording by importing the stub
by its path, which is the same module instance. New tests: the popup is an
anchor around a bold title, and a title with characters such as `&`, `<` and
`>` is shown as written.

A functional test asserts the import map entries on a page with the map, the
vendor stylesheets, the absence of every classic file, the marker icon
attribute, and that each file the import map or a vendor stylesheet link names
exists. That no global is set is checked in a browser on both core versions,
since the stubs cannot show it.

## Risks / Trade-offs

- [A project script relies on `window.LeafletObject`] → it has to load the
  deprecated classic files itself or import the module. Named in the
  Important entry, and the Deprecation entry shows how to publish a global.
- [An overridden `Partner/Map.html` that loads the classic scripts next to the
  new partial] → Leaflet is loaded twice, once as a global and once as a
  module. They do not share state. Named in the changelog.
- [Another extension or a theme maps the bare specifier `leaflet`] → the
  import map holds one entry per specifier, and the last package that maps it
  wins for every module of the page. Named in the changelog.

## Open Questions

None.
