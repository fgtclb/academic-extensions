## 1. Prerequisites

- [ ] 1.1 Confirm that `ace-tbd-item-and-list-partials` and the change for
  candidate `persons-display-10` (list links keep the active state), or their
  renamed successors, are merged on `main`.

## 2. Settings and mode resolution

- [ ] 2.1 Declare `plugin.tx_academicpersons.viewMode.allowed` and
  `plugin.tx_academicpersons.table.columns` in
  `Configuration/Sets/Full/settings.definitions.yaml` and
  `Configuration/TypoScript/Default/constants.typoscript` with the same
  defaults, and map them in `setup.typoscript`. Verify with
  `SiteSetDeliveryTest`.
- [ ] 2.2 Add the mode resolution to `ProfileController` and assign
  `viewMode` and `viewModePartial` in the three actions. Cover default,
  enabled plus allowed, enabled plus not allowed, disabled plus requested, and
  a value failing the pattern.
- [ ] 2.3 Relabel the item of the value `list` to "Tiles" and "Kacheln" for
  all four FlexForms (one line per `source`/`target`, two-space
  indentation), keeping the stored value; verify the label in a backend
  FlexForm test on v13 and v14.

## 3. Tests first, then templates

- [ ] 3.1 Add list, selected-profiles and selected-contracts tests with the
  default mode `table`, asserting `<table` and the five header labels. Record
  that they fail today (the grid renders).
- [ ] 3.2 Add tests for the switch: links for both modes and `aria-current` on
  the active one. The request `viewMode=table` renders the table, and
  `viewMode=slider` renders the default. With the switch disabled,
  `viewMode=table` renders the grid. Record the failures.
- [ ] 3.3 Add a test that the page 2 link of a table list carries
  `viewMode`, and record its failure.
- [ ] 3.4 Add a fixture extension with a `contact` mode (TSconfig item,
  `Profile/ViewMode/Contact.html` and the allow-list). Test that it renders,
  and record its failure.
- [ ] 3.5 Move the grid into `Profile/ViewMode/List.html`, add `Table.html`,
  `Table/Cell.html` and `Switch.html`, and render
  `Profile/ViewMode/{viewModePartial}` in the three templates. Verify group 3
  and every existing plugin test pass.
- [ ] 3.6 Add a card test that the output is unchanged and that no view mode
  field is offered.
- [ ] 3.7 Hard-code the grid again on purpose and watch 3.1 go red; restore.

## 4. Routes

- [ ] 4.1 Add the `/view-mode/{viewMode}` routes (alone, with
  `{localized_page}-{page}`, with `{letter}`) with a `StaticValueMapper` and
  explicit `requirements` to `List.yaml` and `ListAndDetail.yaml`. Extend
  `Tests/Functional/Routing/ProfileRouteEnhancerTest.php` with generation and
  resolution of every new route, an unknown segment value (not found), the
  default mode (no segment) and an allowed mode missing from the map (query
  parameters). Show the resolution test fails without the routes.
- [ ] 4.2 Verify that `/{profile_name}` of ListAndDetail and the existing page
  and letter routes still resolve next to the new routes.
- [ ] 4.3 Verify with a fixture site configuration that a project mode can be
  added to the map by extending the shipped enhancer key. If it cannot,
  implement the site-aware mapper described in `design.md`, with tests for
  generation, resolution and the pattern check, and record the outcome in
  `design.md`.
- [ ] 4.4 If `ace-tbd-visitor-filter-ui-routes` is already merged, add the
  view mode variants of its filter routes here, with a routing test each.

## 5. Documentation

- [ ] 5.1 Add `Documentation/Changelog/3.0/Feature-ListViewModes.rst` from
  `Build/Documentation/Templates/`, including the steps for a project mode,
  the route map entry among them, and the relabelled item.
- [ ] 5.2 Document both settings, the switch, the view mode routes and the
  extension point in the configuration, route enhancer and template
  chapters of `Documentation/`; verify with `checkRstRenderingAll`.
- [ ] 5.3 Update `docs/architecture/typoscript-and-site-sets.md` if it
  enumerates the persons site settings or the route enhancers; verify with
  `lintMarkdown -n`.

## 6. File the issue

- [ ] 6.1 After implementation, file the ACE issue in YouTrack, verify its key
  and rename the change to `ace-<NNN>-list-view-modes`.
- [ ] 6.2 Commit as `[FEATURE] ACE-<NNN>: Render the list view modes` in TYPO3
  Core format.

## 7. Definition of done

- [ ] 7.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 13`, and `functional -d postgres` for the
  routing tests.
- [ ] 7.2 `composerUpdate`, then the same suites green with `-t 14`.
- [ ] 7.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 7.4 `docs/` and the `Documentation/` changelog updated as in group 5;
  `README.md` and `CONTRIBUTING.md` still only summarise.
- [ ] 7.5 No backport: a new feature. State it in the pull request, together
  with anything else left out.
- [ ] 7.6 Archive the change as the last commit of the pull request.
