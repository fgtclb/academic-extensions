## 1. Prerequisite

- [ ] 1.1 Confirm `ace-tbd-list-filter-get-urls` is merged and the filter
  argument is the flat comma list this aspect maps.

## 2. The aspect

- [ ] 2.1 Add `CategoryFilterMapper` and register it as a routing aspect type;
  a functional test that `AspectFactory` builds it from a route enhancer
  configuration and throws without `group`. Show it fails before the
  registration exists.
- [ ] 2.2 Generate: functional tests for one uid, two uids in list order, a
  translated title in a second site language, a title that sanitises to an
  empty string, and the empty value with a German and an English locale.
  Show the translated-title test fails when the default-language title is
  used.
- [ ] 2.3 Resolve: functional tests for a round trip of every generated
  segment, a renamed category, a uid of another group, a hidden or deleted
  category and a malformed part. Show the "other group" test fails when the
  group check is removed.
- [ ] 2.4 Router test: a test site with an Extbase enhancer using the aspect
  resolves `/partner/filter/europa-12` to the filter argument `12` and
  generates the same path back, without a cache hash.

## 3. Documentation

- [ ] 3.1 `docs/architecture/`: a routing page (aspect, settings, why the uid
  stays in the segment), linked from `docs/architecture/Index.md`.
- [ ] 3.2 `typo3-category-types` `Documentation/` (Developers section) and
  `Documentation/Changelog/3.0/Feature-CategoryFilterRouteAspect.rst`.

## 4. File the issue

- [ ] 4.1 After implementation, verify ACE-623 is still the matching issue or
  file a new ACE issue in YouTrack, rename the change to
  `ace-<NNN>-category-filter-route-aspect`, and commit as
  `[FEATURE] ACE-<NNN>: Add a routing aspect for category filters` in TYPO3
  Core format.

## 5. Definition of done

- [ ] 5.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v13; record the results.
- [ ] 5.2 `composerUpdate`, then the same five suites for TYPO3 v14; record
  the results.
- [ ] 5.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 5.4 `docs/` and the `typo3-category-types` `Documentation/` changelog
  updated; `README.md` and `CONTRIBUTING.md` still only link.
- [ ] 5.5 Archive the change as the last commit of the pull request.
