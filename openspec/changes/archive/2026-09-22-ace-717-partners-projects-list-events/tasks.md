## 1. Test fixture

- [x] 1.1 **Two** fixture extensions, one per extension, with listeners for the
  four events (TYPO3's `#[AsEventListener]`, not Symfony's), registered through
  psr-4 as described in `docs/testing/fixture-extensions.md`; `composerUpdate`
  picks them up. Two rather than one because the partner tests never load
  `academic_projects` and the project tests never load `academic_partners` -
  one fixture listening to all four events would drag an extension into every
  run of the other. The reason is written into
  `docs/testing/fixture-extensions.md`.

## 2. Partners

- [x] 2.1 `ModifyPartnerDemandEvent`, dispatched in `listAction()` and in
  `mapAction()` before `setDrawableOnly(true)`. Functional test: the fixture
  listener restricts the demand to one region and the list renders only that
  region. Show it fails without the dispatch (the list is unfiltered).
- [x] 2.2 Map invariant: a listener that replaces the map demand with a fresh
  one still yields no partner without coordinates. Show it fails when the
  event is dispatched after `setDrawableOnly(true)`.
- [x] 2.3 `ModifyPartnerListEvent`, dispatched before `assignMultiple()`.
  Functional test: the listener replaces the result with a subset and
  assigns a variable an overridden fixture template renders. Show it fails
  without the dispatch.

## 3. Projects

- [x] 3.1 `ModifyProjectDemandEvent` and `ModifyProjectListEvent` in
  `listAction()`, with the same two functional tests for both project list
  plugins. Show each fails without the dispatch. **Deviation:** only the
  *demand* tests run against both plugins
  (`projectListAndSinglePage_endDates`); the list-event tests run against
  `ProjectList` alone. One `listAction()` serves both plugins and the list
  event is dispatched on the single code path after the query, so a second
  plugin would exercise the same line - what genuinely differs per plugin is
  the demand, and that is where the two-plugin fixture is spent.
- [x] 3.2 Regression: without listeners the existing plugin tests of both
  extensions pass unchanged.

## 4. Documentation

- [x] 4.1 `docs/architecture/`: where the list plugins dispatch events and why
  the map's restriction runs after the demand event; linked from
  `docs/architecture/Index.md`.
- [x] 4.2 An events page in the `Documentation/` of both extensions, and
  `Documentation/Changelog/3.0/Feature-ListPluginEvents.rst` in each.

## 5. File the issue

- [x] 5.1 After implementation, file the ACE issue in YouTrack, rename the
  change to `ace-717-partners-projects-list-events`, and commit in TYPO3 Core
  format. Filed as **ACE-717**; the commit subject is
  `[FEATURE] ACE-717: Dispatch list plugin events`, because the subject this
  task suggested is 66 characters with the tag and the limit is 52.

## 6. Definition of done

- [x] 6.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v13; record the results.
- [x] 6.2 `composerUpdate`, then the same five suites for TYPO3 v14; record
  the results.
- [x] 6.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 6.4 `docs/` and both extensions' `Documentation/` changelogs updated;
  `README.md` and `CONTRIBUTING.md` still only link.
- [x] 6.5 Archive the change as the last commit of the pull request.
