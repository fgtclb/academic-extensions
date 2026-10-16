## 1. Tests first, red on the unchanged code

- [x] 1.1 `MapTileSourceRegistrationTest` of `academic_partners`, the one of
  `main` and a check that the default tile URL is the one of the map script.
  Red without the service on TYPO3 12 (`.agent/tmp/ace-933-2/unit-red-12.log`)
  and 13 (`unit-red-13.log`), and the map script check red with another URL in
  the script (`unit-default-url-red-13.log`). The data sets of two placeholder
  labels and of a port out of range red on the first derivation
  (`unit-review-red-13.log`).
- [x] 1.2 `AcademicPartnersMapTileSourceTest`: the tile host on the generated
  and on the cached map page, a site with `inheritDefault: false`, and no host
  on the list page. Red on the unchanged code on 12 (`functional-red-12.log`)
  and 13 (`functional-red-13.log`).
- [x] 1.3 The same test with the registration in the map action only: green
  on 12 (`functional-actiononly-12.log`), red on the generating request on 13
  (`functional-actiononly-13.log`). With the processor only: green on 12
  (`functional-processoronly-12.log`) and 13 (`functional-processoronly-13.log`).
- [x] 1.4 A test per core version with the processor removed by TypoScript:
  the action allows the tiles on both pages on 12 and on the cached page only
  on 13. Red without the registration of the action on 12
  (`functional-review-noaction-12.log`) and 13
  (`functional-review-noaction-13.log`).

## 2. Implementation

- [x] 2.1 `MapTileSourceRegistration` for PHP 8.1, verified by 1.1 green on
  12 and 13.
- [x] 2.2 The data processor on `tt_content.academicpartners_map` and the
  registration in the map action, verified by 1.2 green on 12 and 13.

## 3. Documentation

- [x] 3.1 `Configuration/Index.rst`: a section on the tiles below the one on
  the Content Security Policy.
- [x] 3.2 `Changelog/2.4/Important-PartnerMapTilesUnderAContentSecurityPolicy.rst`,
  byte-identical with `main`, checked with `cmp`.
- [x] 3.3 `docs/architecture/content-security-policy.md`.

## 4. Definition of done

- [x] 4.1 `lintPhp`, then per core version after its own `composerUpdate`:
  `cgl -n`, `phpstan`, `unit` and `functional -j 4` on SQLite, green on 12
  with PHP 8.1 and on 13.
- [x] 4.2 `lintMarkdown -n`, the rendering of the manual of partners and
  `openspec validate --specs --strict`, green.
- [x] 4.3 Commit message in TYPO3 Core format with `Resolves: ACE-933`, and
  the archive of this change with the spec sync as the last commit.
