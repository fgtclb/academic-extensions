## 1. Tests first, red on the unchanged code

- [x] 1.1 `MapTileSourceRegistrationTest` of `academic_partners`: the source of
  a tile URL, the URLs without one and the registered mutation. Red without the
  service on TYPO3 13 (`.agent/tmp/ace-933/unit-red-13.log`).
- [x] 1.2 `AcademicPartnersMapTileSourceTest`: the default tile host on the
  generated and on the cached map page, the host of a site setting, a site with
  `inheritDefault: false`, and no host on the list page. Red on the unchanged
  code on 13 (`functional-red-13.log`) and 14 (`functional-red-14b.log`).
- [x] 1.3 The same test against a registration in the map action only: red on
  the generating request on 13 and 14 (`functional-actiononly-13.log`,
  `functional-actiononly-14.log`), green on the cached one
  (`functional-actiononly-cachedonly-13.log`).
- [x] 1.4 Review round: the unit cases of two placeholder labels and of a
  port outside the range, red on the first service on 14
  (`r2/unit-red-old-service-14.log`). The functional cases of the page cache
  entry, of the order of the plugin TypoScript, the plugin object and the
  extension TypoScript, red with the processor without the plugin object
  (`r2/func-red-no-element-14.log`) and with the plugin object first
  (`r2/func-red-element-first-14.log`), and the case without the processor,
  red without the registration in the action (`r2/func-red-noaction-14.log`),
  then the same on 13.

## 2. Implementation

- [x] 2.1 `MapTileSourceRegistration`, verified by 1.1 green on 13 and 14.
- [x] 2.2 The data processor on `tt_content.academicpartners_map`, published,
  and the registration in the map action, verified by 1.2 and 1.4 green on 13
  and 14, and the processor alone green on 13
  (`functional-processoronly-13.log`).

## 3. Documentation

- [x] 3.1 `Integrator/PartnerMap.rst`: a section on the Content Security
  Policy, and the note in `Configuration/Index.rst`.
- [x] 3.2 `Changelog/2.4/Important-PartnerMapTilesUnderAContentSecurityPolicy.rst`,
  true on TYPO3 12 and 13 where the tile URL is fixed, without references that
  fail on branch `2`.
- [x] 3.3 `docs/architecture/content-security-policy.md`, and the counts and
  the published processor in `docs/architecture/dependency-injection.md`.

## 4. Definition of done

- [x] 4.1 `lintPhp`, then per core version after its own `composerUpdate`:
  `cgl -n`, `phpstan`, `unit` and `functional -j 4` on SQLite, green.
- [x] 4.2 `lintMarkdown -n`, the rendering of the manual of partners and
  `openspec validate --specs --strict`, green.
- [x] 4.3 Commit message in TYPO3 Core format with `Resolves: ACE-933`, and
  the archive of this change with the spec sync as the last commit.
