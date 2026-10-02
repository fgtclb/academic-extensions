## 1. Prerequisites

- [x] 1.1 Confirm `ace-779-visitor-filter-demand-query` and
  `ace-734-list-links-keep-state` are merged, and verify the demand property
  names and `filterOptions` in the merged sources.
- [x] 1.2 Check whether `ace-735-list-view-modes` is merged. If it is, this
  change adds the view mode variants of its routes (3.2); if not, record in
  the pull request that `ace-735-list-view-modes` adds them.

## 2. Form

- [x] 2.1 Add the `Profile/List/Filter.html` partial and render it from the
  list template when a filter is enabled; add functional tests for the form
  with one filter, with none and with a preselected value, and show the form
  test fails today because no form is rendered.
- [x] 2.2 Add the non-cacheable `filter` action to the list and listanddetail
  plugin registration and redirect to the list URL; add a functional test
  that posts a choice and asserts a 303 to the filtered URL, and one that
  posts a value outside the options and asserts a redirect to the unfiltered
  list.
- [x] 2.3 Assert in a functional test that the rendered form contains no
  `<script` and no `on*=` attribute.
- [x] 2.4 Flush the cache tag `profile_list_view` when a function type or an
  organisational unit is saved, hidden or deleted, so the form offers what
  the records say, and cover it in a functional test (ACE-779 left the
  options cached until the page cache expires).

## 3. Slugs and routes

- [x] 3.1 Add the `slug` columns to both TCA files (`eval => unique`, see
  design.md), check with `DefaultTcaSchema` on v13 and v14 which column core
  derives, and add a TCA functional test for the slug generation.
- [x] 3.2 Add the filter routes with `requirements` to `List.yaml` and
  `ListAndDetail.yaml`: each filter alone, both filters, each with a page,
  each with a letter (no page variant), and, per 1.2, each of these with the
  view mode segment. Add
  `Tests/Functional/Routing/ProfileFilterRouteTest.php`, next to the view mode
  route test, with generation and resolution of every new route, a German
  key and slug, a record stored in a folder of another site, with an unknown slug (not found) and with a
  record without a slug (query parameters). Show the resolution test fails
  without the route.
- [x] 3.3 Verify that `/{profile_name}` of ListAndDetail still resolves next to
  the filter routes.
- [x] 3.4 Add the repeatable upgrade wizard that fills empty slugs of both
  tables through the DataHandler. Add a functional wizard test: records
  without a slug get the slug a save would generate, an existing slug stays
  unchanged, two records with the same name get distinct slugs, and once
  every record has a slug the wizard is no longer necessary. Show it fails
  with the write removed.

## 4. Documentation

- [x] 4.1 Document the form, the routes and "extend the enhancer key" in
  `Documentation/Configuration/RouteEnhancers/Index.rst`, and the slug
  wizard in the upgrade chapter of `Documentation/`.
- [x] 4.2 Add `Documentation/Changelog/3.0/Feature-VisitorFilterFormAndRoutes.rst`,
  naming the slug wizard as the one upgrade step.
- [x] 4.3 Add the POST-redirect pattern and the `requirements` rule to the
  route enhancer section of `docs/architecture/typoscript-and-site-sets.md`.

## 5. File the issue

- [x] 5.1 File the ACE issue in YouTrack (relating it to ACE-18 and ACE-623)
  and verify the key with a GET request: ACE-798.
- [x] 5.2 Rename the change to `ace-<NNN>-visitor-filter-ui-routes` and verify
  `openspec validate` passes under the new name.
- [x] 5.3 Commit as `[FEATURE] ACE-<NNN>: Add the visitor filter form` in
  TYPO3 Core format.

## 6. Definition of done

- [x] 6.1 `composerUpdate -t 13`, then `lintPhp`, `cgl -n`, `phpstan`, `unit`
  and `functional` green for v13, and `functional -d postgres` for the
  routing, TCA and wizard tests.
- [x] 6.2 `composerUpdate -t 14`, then `lintPhp`, `cgl -n`, `phpstan`, `unit`
  and `functional` green for v14, and `functional -d postgres` for the
  routing, TCA and wizard tests.
- [x] 6.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 6.4 `docs/` and the `Documentation/` changelog entry are part of the
  commit.
- [ ] 6.5 Archive the change as the last commit of the pull request.
