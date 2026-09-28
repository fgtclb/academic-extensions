## 1. The map query

- [x] 1.1 Functional test in `AcademicPartnersPluginTest` (or a class of its
  own): a partner with coordinates and `show_on_map = 0` is not drawn by the
  map, and the list on the same fixture still lists it. Show it red on the
  unchanged repository.
- [x] 1.2 `PartnerRepository::findByDemand()`: add `showOnMap = 1` to the
  constraint of `getDrawableOnly()`, and document the flag as "the partners the
  map can draw" on `PartnerDemand` and at the call site in `mapAction()`.
- [x] 1.3 A listener of `ModifyPartnerDemandEvent` that clears `drawableOnly`
  still cannot bring the partner back, because the flag is set after the event.
  Pin it with the existing listener test pattern, or state why it needs none.

## 2. Translations

- [x] 2.1 Verify on v13 and v14 which record the map query evaluates for a
  translated partner whose translation stores the other value. Result: the
  record of the page language, on both, recorded in `design.md`. The planned
  rule was changed (Stefan).
- [x] 2.2 `show_on_map` gets `allowLanguageSynchronization`. Plugin tests pin
  that the record of the page language decides, in both directions.
  `PartnerShowOnMapSynchronizationTest` pins that switching the default record
  off switches off a new and an existing translation, shown red without the
  synchronization.
- [x] 2.3 The coordinates wizard becomes
  `SynchronizePartnerTranslationsUpgradeWizard` and copies the switch as well,
  each group on its own, with tests for a translation out of step in one group,
  in both, and detached in one group. Shown red without the switch group and
  without the detach rule.

## 3. The partial

- [x] 3.1 `Partials/Partner/Map.html` renders a single partner only when it is
  drawable and switched on, through `Partner::isShownOnMap()`. Extend
  `AcademicPartnerPageMapTest` with a partner that has coordinates and the
  switch off, on FLUIDTEMPLATE and PAGEVIEW. Show it red without the new
  condition.

## 4. Documentation

- [x] 4.1 `academic-partners` `Documentation/Configuration/Index.rst`, section
  of the partner map: the switch, and that it applies to the map only.
- [x] 4.2 `Documentation/Changelog/3.0/Important-PartnerMapHonoursShowOnMap.rst`
  with the query that lists the affected pages, and the note on an overridden
  partial.
- [x] 4.3 `docs/`: the rule is added to the restriction the map keeps after
  the demand event, `docs/architecture/list-plugin-events.md`.

## 5. Commit

- [x] 5.1 Commit as `[BUGFIX] ACE-770: Honour "Show on map" on the map` in
  TYPO3 Core format, with `Resolves: ACE-770`.

## 6. Definition of done

- [x] 6.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v13, all green.
- [x] 6.2 `composerUpdate`, then the same five suites for TYPO3 v14, all
  green.
- [x] 6.3 `functional` on PostgreSQL for both core versions (`-d postgres -j
  8`), since the change adds a query constraint.
- [x] 6.4 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 6.5 `docs/` and the `academic-partners` `Documentation/` changelog
  updated, `README.md` and `CONTRIBUTING.md` still only link.
- [x] 6.6 Archive the change as the last commit of the pull request.
- [x] 6.7 Backport analysis for branch `2` (`docs/workflow/backporting.md`):
  the map action, demand and repository are the same there, the partial does
  not exist. The backport is a change of its own on `2`.
