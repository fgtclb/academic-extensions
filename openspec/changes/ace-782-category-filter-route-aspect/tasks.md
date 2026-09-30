## 1. Prerequisite

- [x] 1.1 Confirm `ace-723-list-filter-get-urls` is merged and the filter
  argument is the flat comma list this aspect maps.

## 2. The aspect

- [x] 2.1 Add `CategoryFilterMapper` and register it as a routing aspect type;
  a functional test that `AspectFactory` builds it from a route enhancer
  configuration and throws without `group`. Show it fails before the
  registration exists.
- [x] 2.2 Generate: functional tests for one uid, two uids in list order, a
  translated title in a second site language, a title that sanitises to an
  empty string, and the empty value with a German and an English locale.
  Show the translated-title test fails when the default-language title is
  used.
- [x] 2.3 Resolve: functional tests for a round trip of every generated
  segment, a renamed category, a uid of another group, a hidden or deleted
  category and a malformed part. Show the "other group" test fails when the
  group check is removed.
- [x] 2.4 Router test in `academic_partners`, whose list excludes its demand
  from the cache hash: a test site with an Extbase enhancer using the aspect
  generates `/home/filter/americas-2` without a cache hash, renders the
  filtered list from it in English and German, redirects the filter form to
  it and answers a category of another group with a 404. Show that two filter
  paths get a page cache entry each when the aspect is static mappable, and a
  cHash when the list stops excluding its demand.

## 3. Documentation

- [x] 3.1 `docs/architecture/`: a routing page (aspect, settings, why the uid
  stays in the segment), linked from `docs/architecture/Index.md`.
- [x] 3.2 `typo3-category-types` `Documentation/` (Developers section) and
  `Documentation/Changelog/3.0/Feature-CategoryFilterRouteAspect.rst`.

## 4. File the issue

- [x] 4.1 ACE-623 is the demo site's task, solved in the demo project, so
  ACE-782 was filed and relates to it. The change is renamed to
  `ace-782-category-filter-route-aspect`, and the commit is
  `[FEATURE] ACE-782: Add category filter route aspect` in TYPO3
  Core format.

## 5. Definition of done

- [x] 5.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v13, and record the results.
- [x] 5.2 `composerUpdate`, then the same five suites for TYPO3 v14, and record
  the results.
- [x] 5.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 5.4 `docs/` and the `typo3-category-types` `Documentation/` changelog
  updated; `README.md` and `CONTRIBUTING.md` still only link.
- [ ] 5.5 Archive the change as the last commit of the pull request.
