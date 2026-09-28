## 1. Map module

- [x] 1.1 Extend the Leaflet stub in `Tests/JavaScript/map.test.ts` to record
  the tile layer, the map options, the `fitBounds` padding and the `setView`
  centre and zoom. Export the initialiser of `map.ts`, so each test starts the
  module on its own markup.
- [x] 1.2 `testJs`: `#map` with `data-academic-partners-zoom="10"` and a
  centre in `data-academic-partners-center-lat` and `-center-lng`, without
  partners, calls `setView` with those values. With no data attributes it uses
  51.1657 / 10.4515 / 6. Shown red on the unchanged module (the hard-coded
  centre and zoom).
- [x] 1.3 `testJs`: the maximum zoom, the padding, the tile URL and the
  attribution reach Leaflet. An unusable value falls back to the default, a
  negative padding as well when there are partners to fit, and a written zero
  is kept for the centre, the zoom, the maximum zoom and the padding. Shown red
  with an unchecked `Number()` and with the range check removed.
- [x] 1.4 Read the attributes in `map.ts`, run `buildJs` and commit the build.
  `lintTypescript`, `typecheckJs` and `checkJsBuildClean` green.

## 2. Settings, FlexForm and partial

- [x] 2.1 `Configuration/Sets/Map/settings.definitions.yaml`, constants and
  setup mapping, with functional tests that the definitions carry today's values,
  that a site on the set and a site on the static template render them, and
  that site settings reach every data attribute. Shown red before the mapping
  existed.
- [x] 2.2 `settings.map.layout` in a second sheet `layout` of `MapSettings.xml`,
  the filter fields staying in `sDEF`, with rendering tests for the full width class
  and its absence, and `PluginFlexFormTest` for the sheets and the default.
  Shown red before the field existed.
- [x] 2.3 `Partials/Partner/Map.html`, rendered by `Templates/Partner/Map.html`. The existing `partnerMapPlugin*` functional tests stay green unchanged
  (regression half).
- [x] 2.4 The `partner-data` processor takes the option `map` and adds
  `mapSettings`. A rendering test of the partial with `partner` on a
  `FLUIDTEMPLATE` and a `PAGEVIEW` partner page, for a drawable and a
  non-drawable partner. Shown red with the guard removed and with the option
  removed.

## 3. Documentation

- [x] 3.1 `docs/development/frontend-assets.md`: the map's data attribute
  contract. `docs/architecture/typoscript-and-site-sets.md`: the map set
  declares its settings. `docs/architecture/page-type-rendering.md`: the
  processor option.
- [x] 3.2 `academic-partners` `Documentation/Configuration/` for the settings,
  the layout field and the partial, and
  `Documentation/Changelog/3.0/Feature-ConfigurablePartnerMap.rst`.

## 4. File the issue

- [x] 4.1 After implementation, file the ACE issue in YouTrack and verify the
  key: ACE-769.
- [x] 4.2 Rename the change to `ace-769-partner-map-settings-partial`.
- [x] 4.3 Commit as `[FEATURE] ACE-769: Make the partner map configurable` in
  TYPO3 Core format.

## 5. Definition of done

- [x] 5.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v13, all green.
- [x] 5.2 `composerUpdate`, then the same five suites for TYPO3 v14, all
  green.
- [x] 5.3 `testJs`, `lintTypescript`, `typecheckJs`, `checkJsBuildClean`,
  `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 5.4 `docs/` and the `academic-partners` `Documentation/` changelog
  updated, `README.md` and `CONTRIBUTING.md` still only link.
- [x] 5.5 Archive the change as the last commit of the pull request.
