## 1. Map module

- [ ] 1.1 Extend the Leaflet stub in `Tests/JavaScript/map.test.ts` to record
  the zoom, the tile layer options and the `fitBounds` padding.
- [ ] 1.2 `testJs`: `#map` with `data-zoom="10"`, `data-center-lat` and
  `data-center-lng` and no partners calls `setView` with those values; with
  no data attributes it uses 51.1657 / 10.4515 / 6. Show the first test fails
  on the unchanged module (the hard-coded centre is used).
- [ ] 1.3 `testJs`: `data-max-zoom`, `data-padding`, `data-tile-url` and
  `data-attribution` reach Leaflet; a non-numeric value falls back to the
  default. Show the fallback test fails when `Number()` is used unchecked.
- [ ] 1.4 Read the attributes in `map.ts`; `buildJs` and commit the build;
  `lintTypescript`, `typecheckJs` and `checkJsBuildClean` green.

## 2. Settings, FlexForm and partial

- [ ] 2.1 `Configuration/Sets/Map/settings.definitions.yaml`, constants and
  setup mapping; a functional rendering test that a site setting reaches the
  `data-zoom` attribute. Show it fails before the mapping exists.
- [ ] 2.2 `settings.map.layout` in the map's FlexForm (`MapSettings.xml`,
  created here if `ace-tbd-partner-list-pagination` has not landed); a
  rendering test for the full width class and for its absence by default.
- [ ] 2.3 `Partials/Partner/Map.html`; `Templates/Partner/Map.html` renders
  it. The existing `partnerMapPlugin*` functional tests stay green unchanged
  (regression half).
- [ ] 2.4 A rendering test of the partial with `partner` for a drawable and a
  non-drawable partner; show the second fails when the guard is removed.

## 3. Documentation

- [ ] 3.1 `docs/development/frontend-assets.md`: the map's data attribute
  contract; `docs/architecture/typoscript-and-site-sets.md`: the map
  settings.
- [ ] 3.2 `academic-partners` `Documentation/Configuration/` for the settings,
  the layout field and the partial, and
  `Documentation/Changelog/3.0/Feature-ConfigurablePartnerMap.rst`.

## 4. File the issue

- [ ] 4.1 After implementation, file the ACE issue in YouTrack, rename the
  change to `ace-<NNN>-partner-map-settings-partial`, and commit as
  `[FEATURE] ACE-<NNN>: Make the partner map configurable` in TYPO3 Core
  format.

## 5. Definition of done

- [ ] 5.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v13; record the results.
- [ ] 5.2 `composerUpdate`, then the same five suites for TYPO3 v14; record
  the results.
- [ ] 5.3 `testJs`, `lintTypescript`, `typecheckJs`, `checkJsBuildClean`,
  `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 5.4 `docs/` and the `academic-partners` `Documentation/` changelog
  updated; `README.md` and `CONTRIBUTING.md` still only link.
- [ ] 5.5 Archive the change as the last commit of the pull request.
