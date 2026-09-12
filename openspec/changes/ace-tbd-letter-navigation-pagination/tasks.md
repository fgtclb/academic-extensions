## 1. Prerequisites

- [ ] 1.1 Confirm that `ace-tbd-list-links-keep-state`,
  `ace-tbd-visitor-filter-ui-routes` and
  `ace-tbd-letter-navigation-availability`, or their renamed successors, are
  merged on `main`. Re-read `listAction()`, both route files and both
  pagination partials in the merged sources, and note every difference to
  `design.md` in this file before coding.

## 2. Pagination under a letter

- [ ] 2.1 Add the fixture
  `Tests/Functional/Plugins/Fixtures/AcademicPersonsListPlugin/defaultLanguageOnly_letterPaginated.csv`:
  seven profiles, three of them with a last name that matches the letter on
  every DBMS (see the note in `ProfileRouteEnhancerTest`), and a list plugin
  with `paginationEnabled` 1, `resultsPerPage` 2 and the letter navigation
  enabled. Add the same fixture for the listanddetail plugin.
- [ ] 2.2 Add list and listanddetail tests: under the letter, page 1 renders
  two profiles of the letter and a page navigation with exactly two pages;
  page 2 renders the third; page 5 renders the third as well. Run them against
  the unchanged controller and record that they fail because all three
  profiles render and no page navigation is shown.
- [ ] 2.3 Add a test with pagination disabled that asserts all three profiles
  of the letter on one page and no page navigation; it passes before and after
  the change.
- [ ] 2.4 Remove the switch-off from `listAction()`. Verify group 2 and every
  existing list and listanddetail test pass, then restore the three lines on
  purpose, watch 2.2 go red, and remove them again.

## 3. Links under a letter

- [ ] 3.1 Add a test that the page 2 link under a letter carries the letter,
  and one that every letter link and the link back to all letters, rendered on
  page 2 of a letter, carries no page number. Show the first fails with the
  switch-off restored, because no page link is rendered.

## 4. Routes

- [ ] 4.1 Add `/{letter}/{localized_page}-{page}` with `requirements` to
  `List.yaml` and `ListAndDetail.yaml`.
- [ ] 4.2 Extend `Tests/Functional/Routing/ProfileRouteEnhancerTest.php`:
  generation of `/m/page-2` and `/m/seite-2` for both plugins, resolution of
  both to page 2 of the letter, and unchanged generation of the letter alone
  and the page alone. Record that generation fails without the route (the
  letter stays a query argument) and resolution answers not found.
- [ ] 4.3 Verify that `/{profile_name}` of ListAndDetail still resolves next to
  the new route, and that a link with letter, page and a filter resolves to
  the same list, with the value not in the path as a query argument.
- [ ] 4.4 Run the routing and plugin tests of groups 2 to 4 with `-d postgres`
  as well.

## 5. Documentation

- [ ] 5.1 In `Documentation/Configuration/RouteEnhancers/Index.rst`, describe
  the new route for both files, add it to the overlap table, add
  `/persons/m/page-2` to the URL examples, and replace the caveat that the
  letter and page routes are alternatives.
- [ ] 5.2 Add `Documentation/Changelog/3.0/Feature-PaginationUnderAnActiveLetter.rst`
  from `Build/Documentation/Templates/Changelog-Feature.rst`: letter results
  are paginated when pagination is enabled, the new route, and disabling
  pagination as the way back to one page per letter. Verify with
  `checkRstRenderingAll`.
- [ ] 5.3 Add the route to the overlap table in
  `docs/architecture/typoscript-and-site-sets.md`; verify with
  `lintMarkdown -n`.

## 6. File the issue

- [ ] 6.1 After implementation, file the ACE issue in YouTrack and verify the
  key with a GET request.
- [ ] 6.2 Rename the change to `ace-<NNN>-letter-navigation-pagination` and
  verify `openspec validate` passes under the new name.
- [ ] 6.3 Commit as `[FEATURE] ACE-<NNN>: Paginate the list under a letter`
  in TYPO3 Core format.

## 7. Definition of done

- [ ] 7.1 `composerUpdate -t 13`, then `lintPhp`, `cgl -n`, `phpstan`, `unit`
  and `functional` green for v13, and `functional -d postgres` for the tests
  of groups 2 to 4.
- [ ] 7.2 `composerUpdate -t 14`, then `lintPhp`, `cgl -n`, `phpstan`, `unit`
  and `functional` green for v14, and `functional -d postgres` for the tests
  of groups 2 to 4.
- [ ] 7.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 7.4 `docs/` and the `Documentation/` changelog entry are part of the
  commit; `README.md` and `CONTRIBUTING.md` still only summarise.
- [ ] 7.5 No backport: a new feature on `main`. State it in the pull request,
  together with anything else left out.
- [ ] 7.6 Archive the change as the last commit of the pull request.
