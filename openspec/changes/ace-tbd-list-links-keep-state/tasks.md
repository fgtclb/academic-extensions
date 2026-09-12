## 1. Sequencing

- [ ] 1.1 Decide with the maintainer whether this change lands with, or
  directly before, `ace-tbd-list-view-modes` or
  `ace-tbd-visitor-filter-demand-query`, because its red test needs a second
  visitor value; record the decision in this file.

## 2. Controller

- [ ] 2.1 Introduce the constant of visitor-settable demand properties and
  derive the property mapping allow-list from it; verify the existing list
  and listanddetail functional tests stay green on v13 and v14.
- [ ] 2.2 Assign `activeListArguments` from the mapped demand; add a
  functional test where the request carries a foreign query parameter and an
  editor-only sorting value, asserting neither appears in any pagination or
  letter link.

## 3. Links

- [ ] 3.1 Add the stateless merge ViewHelper with an optional `overrides`
  argument and a unit test for override, removal and an empty set; show the
  test fails when the override is ignored.
- [ ] 3.2 Build the links of `Pagination.html` and `AlphabetPagination.html`
  from `activeListArguments`; add a functional list test with the second
  visitor value (view mode or filter) that asserts the page 2 link and a
  letter link carry it, and show it fails against the unchanged partials.

## 4. Documentation

- [ ] 4.1 Document `activeListArguments` and the ViewHelper for template
  overrides in `Documentation/Templates/`.
- [ ] 4.2 Add `Documentation/Changelog/3.0/Feature-ListLinksKeepState.rst`
  that names the two partials, so projects with overrides know to adopt the
  new arguments.
- [ ] 4.3 Describe the visitor-settable property list in `docs/` under
  `docs/architecture/` and link it from `docs/architecture/Index.md`.

## 5. File the issue

- [ ] 5.1 After implementation, file the ACE issue in YouTrack and verify the
  key with a GET request.
- [ ] 5.2 Rename the change to `ace-<NNN>-list-links-keep-state` and verify
  `openspec validate` passes under the new name.
- [ ] 5.3 Commit as `[TASK] ACE-<NNN>: Keep list state in navigation links` in
  TYPO3 Core format.

## 6. Definition of done

- [ ] 6.1 `composerUpdate -t 13`, then `lintPhp`, `cgl -n`, `phpstan`, `unit`
  and `functional` green for v13.
- [ ] 6.2 `composerUpdate -t 14`, then `lintPhp`, `cgl -n`, `phpstan`, `unit`
  and `functional` green for v14.
- [ ] 6.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 6.4 `docs/` and the `Documentation/` changelog entry are part of the
  commit.
- [ ] 6.5 Archive the change as the last commit of the pull request.
