## 1. Prerequisites

- [x] 1.1 Confirm `ace-723-list-filter-get-urls`,
  `ace-727-partner-list-pagination` and `ace-782-category-filter-route-aspect`
  are merged. Take the argument names from the merged code, not from this
  design.

## 2. Route files

- [x] 2.1 `academic-partners/Configuration/Routes/List.yaml` for `List` and
  `Map`, with a functional router test that imports it into a test site and, for
  each of the seven list combinations and three map combinations, generates
  a path and resolves it back to the same arguments. Show the "filter only"
  and "sorting only" cases fail with a single route using defaults.
- [x] 2.2 `academic-projects/Configuration/Routes/List.yaml` for both list
  plugins, with the same router test for the seven combinations. Show the cases of
  a missing route fail. (Reversing the route order fails nothing: TYPO3 sorts
  the routes for generation itself, see design.md.)
- [x] 2.3 `academic-programs/Configuration/Routes/List.yaml`, and
  `Configuration/Yaml/Routes.yaml` as an import of it, with the same router test
  for the three combinations and for the former path.
- [x] 2.4 Validity of the three files is covered by `ShippedYamlFilesTest` of
  `academic_base`. A unit test per extension compares the value lists of each
  file, per language, with the extension's sorting options (and states), and
  checks that every variable stays within one segment. Show it fails with a
  sorting option removed from the file.
- [x] 2.5 German and English site languages: the router tests run in both and
  assert the localised keys, and a value of the other language is a 404.
- [x] 2.6 The development instances import the three files and limit each
  enhancer to the seeded list pages.

## 3. Documentation

- [x] 3.1 `docs/architecture/list-route-enhancers.md`: the combination rule,
  the route order, the requirements trap, static and dynamic values, the
  former program file, linked from `docs/architecture/Index.md`.
- [x] 3.2 `Documentation/` of the three extensions: import and `limitToPages`
  example, a further language, and `Documentation/Changelog/3.0/Feature-ListRouteEnhancer.rst`
  in each, `Important-RouteEnhancerMoved.rst` in academic_programs.

## 4. File the issue

- [x] 4.1 ACE-623 is the demo site's own task, so ACE-802 was filed, the change
  renamed to `ace-802-list-route-enhancers`, and the commit is
  `[FEATURE] ACE-802: Ship list route enhancers` in TYPO3 Core
  format.

## 5. Definition of done

- [x] 5.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v13, and record the results.
- [x] 5.2 `composerUpdate`, then the same five suites for TYPO3 v14, and
  record the results.
- [x] 5.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 5.4 `docs/` and every affected extension's `Documentation/` changelog
  updated, `README.md` and `CONTRIBUTING.md` still only link.
- [ ] 5.5 Archive the change as the last commit of the pull request.
