## 1. Prerequisites

- [ ] 1.1 Confirm `ace-tbd-list-filter-get-urls`,
  `ace-tbd-partner-list-pagination` and `ace-tbd-category-filter-route-aspect`
  are merged; take the argument names from the merged code, not from this
  design.

## 2. Route files

- [ ] 2.1 `academic-partners/Configuration/Routes/List.yaml` for `List` and
  `Map`; a functional router test that imports it into a test site and, for
  each of the seven list combinations and three map combinations, generates
  a path and resolves it back to the same arguments. Show the "filter only"
  and "sorting only" cases fail with a single route using defaults.
- [ ] 2.2 `academic-projects/Configuration/Routes/List.yaml` for both list
  plugins; the same router test for the seven combinations. Show one case
  fails when the routes are ordered least specific first.
- [ ] 2.3 `academic-programs/Configuration/Routes/List.yaml`; the same router
  test for the three combinations.
- [ ] 2.4 A plain `Yaml::parseFile()` test for the three files, and a test that
  the sorting value lists of each file equal the extension's sorting options.
  Show the latter fails with a sorting option removed from the file.
- [ ] 2.5 German and English site languages: the router tests run in both and
  assert the localised keys.

## 3. Documentation

- [ ] 3.1 `docs/architecture/` routing page: the combination rule, the
  requirements trap and the import; linked from `docs/architecture/Index.md`.
- [ ] 3.2 `Documentation/` of the three extensions: import and `limitToPages`
  example; `Documentation/Changelog/3.0/Feature-ListRouteEnhancer.rst` in
  each.

## 4. File the issue

- [ ] 4.1 After implementation, verify ACE-623 is still the matching issue or
  file a new ACE issue in YouTrack, rename the change to
  `ace-<NNN>-list-route-enhancers`, and commit as
  `[FEATURE] ACE-<NNN>: Ship route enhancers for the lists` in TYPO3 Core
  format.

## 5. Definition of done

- [ ] 5.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v13; record the results.
- [ ] 5.2 `composerUpdate`, then the same five suites for TYPO3 v14; record
  the results.
- [ ] 5.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 5.4 `docs/` and every affected extension's `Documentation/` changelog
  updated; `README.md` and `CONTRIBUTING.md` still only link.
- [ ] 5.5 Archive the change as the last commit of the pull request.
