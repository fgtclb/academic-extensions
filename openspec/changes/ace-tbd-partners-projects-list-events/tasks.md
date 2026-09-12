## 1. Test fixture

- [ ] 1.1 A fixture extension with listeners for the four events (TYPO3's
  `#[AsEventListener]`, not Symfony's), registered through psr-4 as described
  in `docs/testing/fixture-extensions.md`; `composerUpdate` picks it up.

## 2. Partners

- [ ] 2.1 `ModifyPartnerDemandEvent`, dispatched in `listAction()` and in
  `mapAction()` before `setDrawableOnly(true)`. Functional test: the fixture
  listener restricts the demand to one region and the list renders only that
  region. Show it fails without the dispatch (the list is unfiltered).
- [ ] 2.2 Map invariant: a listener that replaces the map demand with a fresh
  one still yields no partner without coordinates. Show it fails when the
  event is dispatched after `setDrawableOnly(true)`.
- [ ] 2.3 `ModifyPartnerListEvent`, dispatched before `assignMultiple()`.
  Functional test: the listener replaces the result with a subset and
  assigns a variable an overridden fixture template renders. Show it fails
  without the dispatch.

## 3. Projects

- [ ] 3.1 `ModifyProjectDemandEvent` and `ModifyProjectListEvent` in
  `listAction()`, with the same two functional tests for both project list
  plugins. Show each fails without the dispatch.
- [ ] 3.2 Regression: without listeners the existing plugin tests of both
  extensions pass unchanged.

## 4. Documentation

- [ ] 4.1 `docs/architecture/`: where the list plugins dispatch events and why
  the map's restriction runs after the demand event; linked from
  `docs/architecture/Index.md`.
- [ ] 4.2 An events page in the `Documentation/` of both extensions, and
  `Documentation/Changelog/3.0/Feature-ListPluginEvents.rst` in each.

## 5. File the issue

- [ ] 5.1 After implementation, file the ACE issue in YouTrack, rename the
  change to `ace-<NNN>-partners-projects-list-events`, and commit as
  `[FEATURE] ACE-<NNN>: Dispatch events in partner and project lists` in
  TYPO3 Core format.

## 6. Definition of done

- [ ] 6.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v13; record the results.
- [ ] 6.2 `composerUpdate`, then the same five suites for TYPO3 v14; record
  the results.
- [ ] 6.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 6.4 `docs/` and both extensions' `Documentation/` changelogs updated;
  `README.md` and `CONTRIBUTING.md` still only link.
- [ ] 6.5 Archive the change as the last commit of the pull request.
