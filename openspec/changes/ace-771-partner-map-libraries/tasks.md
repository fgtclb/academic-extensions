## 1. The build

- [x] 1.1 Add `leaflet` 1.9.4 and `leaflet.markercluster` 1.5.3 as exact
  versions to `Build/package.json`, update the lock file with the `npm` suite.
- [x] 1.2 A build pass that writes the vendor files of both libraries below
  `packages/fgtclb/academic-partners/Resources/Public/JavaScript/vendor/`,
  with the licence files and the stylesheets and images, and lists them in
  `--list-outputs`. `checkJsBuildClean` green.
- [x] 1.3 Verify that the built cluster module imports `leaflet` rather than
  carrying a copy of it.

## 2. The module and its tests

- [x] 2.1 Stub `leaflet` and `leaflet.markercluster` in the resolve hook with a
  recording stub, and move `map.test.ts` onto it. The existing tests stay green
  unchanged in what they assert.
- [x] 2.2 New tests: the popup is an anchor around a bold title, and a title
  with characters such as `&`, `<` and `>` is its text as written. Shown red
  with the string popup.
- [x] 2.3 `map.ts` imports both libraries and builds the popup from elements.
  `_dependencies.d.ts` declares the specifiers. `buildJs`, `lintTypescript`,
  `typecheckJs` and `testJs` green.

## 3. Delivery

- [x] 3.1 `Configuration/JavaScriptModules.php` publishes `leaflet` and
  `leaflet.markercluster`.
- [x] 3.2 `Partials/Partner/Map.html` registers the vendor stylesheets and no
  classic script. A functional test asserts the stylesheets, the import map
  entries on a page with the map, and the absence of the classic scripts, on
  v13 and v14. Shown red with the partial and the import map of `main`. The
  width rule of the old `leaflet.css` moves into `Scss/frontend/map.scss`.
- [x] 3.3 Check a rendered map in a browser on a v13 and a v14 instance:
  markers, clusters, the cluster area on hover, the popup. Done on both: import
  map with `?bust=`, no global, 18 tiles, 5 markers, the popup, a cluster of 5,
  the coverage path `M638 253L644 252L636 247z`, no console message.
- [x] 3.4 The marker icon of the extension stays: the images are copied to
  `Resources/Public/Images/Map/`, the partial names the icon in
  `data-academic-partners-marker-icon`, and the module gives every marker a
  default icon from that directory. A `testJs` test and a functional test pin
  it, both shown red without it.

## 4. Documentation

- [x] 4.1 `docs/development/frontend-assets.md`: replace the paragraph on the
  mapping library shipped straight in `Resources/Public/JavaScript/` with how
  the vendor modules are built from their packages.
- [x] 4.2 `Documentation/Changelog/3.0/Important-PartnerMapLibrariesAsModules.rst`
  and `Deprecation-PartnerMapClassicLibraryFiles.rst`, removal in 4.0.
- [x] 4.3 The configuration chapter: the files the map partial loads.

## 5. Publish

- [x] 5.1 Commit this change together with the implementation.
- [x] 5.2 Commit as `[TASK] ACE-771: Build partner map libraries from npm` in
  TYPO3 Core format, with `Resolves: ACE-771`.

## 6. Definition of done

- [x] 6.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v13, all green.
- [x] 6.2 The same for TYPO3 v14.
- [x] 6.3 `testJs`, `lintTypescript`, `typecheckJs`, `checkJsBuildClean`,
  `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 6.4 Archive the change as the last commit of the pull request.
- [ ] 6.5 Backport analysis for branch `2`: the same files and build exist
  there. The backport is a change of its own on `2`.
