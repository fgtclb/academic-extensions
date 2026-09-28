## 1. The map query

- [x] 1.1 Map plugin tests: a partner with coordinates and the switch off is
  not drawn, and the list still lists it. Shown red on the unchanged code.
- [x] 1.2 `PartnerRepository::findByDemand()` adds the switch under
  `drawableOnly`, documented on `PartnerDemand` and at the call site.

## 2. Translations

- [x] 2.1 The map query reads the switch of the page language, pinned in both
  directions by two map plugin tests with a German language. Shown red on the
  unchanged code.
- [x] 2.2 `show_on_map` gets `allowLanguageSynchronization`.
  `PartnerShowOnMapSynchronizationTest` from `main`, shown red without the
  synchronization.
- [x] 2.3 The coordinates wizard becomes
  `SynchronizePartnerTranslationsUpgradeWizard` and copies the switch as well,
  with the tests of `main`. Shown red without the switch group.

## 3. One partner

- [x] 3.1 `Partner::isShownOnMap()` and `setShowOnMap()`, with a unit test.

## 4. Documentation

- [x] 4.1 The configuration chapter: which partners the map draws.
- [x] 4.2 `Documentation/Changelog/2.4/Important-PartnerMapHonoursShowOnMap.rst`,
  and the entry of the coordinates change names the renamed wizard.

## 5. Commit

- [x] 5.1 Commit as `[BUGFIX] ACE-770: Honour "Show on map" on the map` in
  TYPO3 Core format, with `Resolves: ACE-770`.

## 6. Definition of done

- [x] 6.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v12 and v13, and `functional` on PostgreSQL with
  `-j 8` for both.
- [x] 6.2 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 6.3 `docs/`: nothing to change. The page on list plugin events that
  `main` extended does not exist on this branch.
- [ ] 6.4 Archive the change as the last commit of the pull request.
