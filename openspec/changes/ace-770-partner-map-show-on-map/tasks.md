## 1. The map query

- [ ] 1.1 Functional test in `AcademicPartnersPluginTest` (or a class of its
  own): a partner with coordinates and `show_on_map = 0` is not drawn by the
  map, and the list on the same fixture still lists it. Show it red on the
  unchanged repository.
- [ ] 1.2 `PartnerRepository::findByDemand()`: add `showOnMap = 1` to the
  constraint of `getDrawableOnly()`, and document the flag as "the partners the
  map can draw" on `PartnerDemand` and at the call site in `mapAction()`.
- [ ] 1.3 A listener of `ModifyPartnerDemandEvent` that clears `drawableOnly`
  still cannot bring the partner back, because the flag is set after the event.
  Pin it with the existing listener test pattern, or state why it needs none.

## 2. Translations

- [ ] 2.1 Verify on v13 and v14 which record the map query evaluates for a
  translated partner whose translation stores the other value. Record the
  result in `design.md`.
- [ ] 2.2 `show_on_map` gets `l10n_mode => 'exclude'`. A test with a translated
  partner, switched off in the default language, not drawn in the other
  language.

## 3. The partial

- [ ] 3.1 `Partials/Partner/Map.html` renders a single partner only when it is
  drawable and switched on. Extend `AcademicPartnerPageMapTest` with a partner
  that has coordinates and the switch off, on FLUIDTEMPLATE and PAGEVIEW.
  Show it red without the new condition.

## 4. Documentation

- [ ] 4.1 `academic-partners` `Documentation/Configuration/Index.rst`, section
  of the partner map: the switch, and that it applies to the map only.
- [ ] 4.2 `Documentation/Changelog/3.0/Important-PartnerMapHonoursShowOnMap.rst`
  with the query that lists the affected pages, and the note on an overridden
  partial.
- [ ] 4.3 `docs/`: add the rule where the map query is documented, or state in
  the pull request why no page needs it.

## 5. Commit

- [ ] 5.1 Commit as `[BUGFIX] ACE-770: Honour "Show on map" on the map` in
  TYPO3 Core format, with `Resolves: ACE-770`.

## 6. Definition of done

- [ ] 6.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v13, all green.
- [ ] 6.2 `composerUpdate`, then the same five suites for TYPO3 v14, all
  green.
- [ ] 6.3 `functional` on PostgreSQL for both core versions (`-d postgres -j 8`),
  since the change adds a query constraint.
- [ ] 6.4 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 6.5 `docs/` and the `academic-partners` `Documentation/` changelog
  updated, `README.md` and `CONTRIBUTING.md` still only link.
- [ ] 6.6 Archive the change as the last commit of the pull request.
- [ ] 6.7 Backport analysis for branch `2` (`docs/workflow/backporting.md`):
  the map action, demand and repository are the same there, the partial does
  not exist. The backport is a change of its own on `2`.
