## 1. Event and trait

- [x] 1.0 Verify that `ace-442-single-action-context-interface` and
  `ace-749-extension-point-policy` have landed; stop otherwise.
- [x] 1.1 Add the `final` `ModifyPluginViewEvent`, with an `@api` docblock
  tag, to `academic-base/Classes/Event/` and a unit test for its getters.
- [x] 1.2 Add the dispatching trait to `academic-base/Classes/Controller/`,
  building the `academic_base` context from request and settings; unit test
  with a stub dispatcher asserting one dispatch with the given view.

## 2. Test harness

- [x] 2.1 Add a fixture extension (found by `sbuerk/fixture-packages`, its
  PSR-4 root in its own `composer.json`, then `composerUpdate`) with a
  listener that records each call (extension, plugin, action) and assigns
  `probe`, plus template overrides for the persons list, the partner map and
  the job form that output `{probe}`.
- [x] 2.2 Functional frontend test with a data provider over every plugin
  and action that renders a view (eighteen actions, some of them registered
  for more than one plugin) and the three early returns, asserting exactly
  one recorded call per rendering and the plugin and action names; the persons
  list case asserts `probe` in the output.

## 3. Dispatch in the controllers

- [x] 3.1 Call the trait method on every rendering path of the persons,
  jobs, partners, programs (both controllers, the finder included), projects,
  contacts for pages and BITE jobs controllers, after the action's own assignments, where the
  removed specific event was dispatched; the data provider rows turn green
  one by one, and removing any single call turns its row red again.
- [x] 3.2 Cover the early returns (job detail without a job, selected
  profiles and selected contracts without a selection); shown red by
  removing the call on the early path only.
- [x] 3.3 In the new job form, dispatch before the `validations` assignment;
  a test listener assigning `validations` leaves the configured validations
  in the output, shown red by moving the dispatch after the assignment.
- [x] 3.4 Remove `ModifyJobControllerNewActionViewEvent` and the persons
  list, detail, selected profiles and selected contracts events, with their
  dispatches and unit tests; the detail action takes the title format from
  the setting or the default directly. Functional test with a fixture
  listener whose parameter is typed against the removed persons list event
  class (a line-scoped phpstan ignore, which is exactly what the `Breaking-`
  entry warns about): the container builds, the persons list renders, and
  the listener is not called; shown red by keeping the dispatch. The
  existing detail page title tests stay green, and one with the setting
  `pageTitleFormat` is shown red by ignoring the setting.
- [x] 3.5 Carry the removal into what ACE-442 wrote about the persons
  context: the persons `Breaking-PluginControllerActionContextInterfaceExtendsBase.rst`
  and `Deprecation-PersonsPluginControllerActionContext.rst`, which say the
  persons events keep declaring the persons interface throughout 3.x; the
  event table of the plugin action context section in the persons
  `Developers/Index.rst`; the subsection on classes implementing the plugin
  action context in the persons `Upgrade/Index.rst`; the paragraph on the
  persons context in `docs/architecture/list-plugin-events.md`, which says
  the persons actions hand the same object to their event and to the query
  event; a MODIFIED delta for the requirement "Listeners typed against the
  persons context keep working" of `academic-persons/plugin-action-context`,
  whose scenario is the detail plugin; and `AcademicPersonsPluginActionContextTest` with
  the fixture extension `test_plugin_action_context`, whose listeners listen
  to the list and detail events. After the removal, only the profile title
  placeholder event declares the persons context; the test moves its checks
  to that event, to the profile query event, which receives the persons
  context object in the list, and to the generic view event.
- [x] 3.6 Carry the removal into what describes a listener of the persons
  list event elsewhere: the fixture listener `ReplaceListDemandListener` of
  `test_profile_query_constraints` and the two tests that use it (view modes,
  navigation state), which assert what a replaced demand does not change and
  have nothing to assert once no listener can replace it; the sections on the
  letter navigation and the navigation state in the persons
  `Developers/Index.rst`; the unreleased 3.0 entries
  `Feature-LetterNavigationAvailability.rst` and
  `Important-PaginatedSelectionKeepsItsOrder.rst`, corrected in place; the
  comments of `ProfileRepository`, `ProfileRepositoryShowHiddenRecordsTest`
  and the persons context class; `docs/architecture/database-queries.md`;
  and the phpstan baselines.

## 4. Documentation

- [x] 4.1 Document the event on the extension point page of `academic_base`
  created by `ace-749-extension-point-policy`, with a listener example using
  TYPO3's `#[AsEventListener]`: a row for the new event, the five removed
  rows dropped, the persons context row and the `ProfileDemand` row of the
  types table corrected, and the view event moved from planned to offered in
  the table of the minimum set.
- [x] 4.2 Add `academic-base/Documentation/Changelog/3.0/Feature-ModifyPluginViewEvent.rst`
  and a short `Feature-PluginViewEvent.rst` pointing to it in the 3.0
  changelog of persons, jobs, partners, programs, projects, contacts for pages
  and BITE jobs.
- [x] 4.3 Add `academic-jobs/Documentation/Changelog/3.0/Breaking-RemovedModifyJobControllerNewActionViewEvent.rst`
  and `academic-persons/Documentation/Changelog/3.0/Breaking-RemovedProfileViewEvents.rst`
  from `Build/Documentation/Templates/Changelog-Breaking.rst`, in the style
  of a TYPO3 core hook-to-event transition: the removed classes, the impact
  (no longer dispatched, no fatal error, reported by phpstan), the affected
  installations, and a migration example to the generic event that links
  `Feature-ModifyPluginViewEvent.rst`, plus the replacements of the persons
  data setters.
- [x] 4.4 Update the extension point section of
  `docs/architecture/class-design.md`; drop the two removed selection events
  from its `strict_types` table and recount that section; update the fixture
  extension list in `docs/testing/fixture-extensions.md`.

## 5. File the issue

- [x] 5.1 After implementation, file the ACE issue in YouTrack (ACE-750),
  rename the change to `ace-750-generic-plugin-view-event`, and commit in
  TYPO3 Core format as `[!!!][FEATURE] ACE-750: Add a plugin view event` (the
  subject planned first was longer than 52 characters).

## 6. Definition of done

- [x] 6.1 `lintPhp` green.
- [x] 6.2 After `-t 13 -s composerUpdate`: `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 13`.
- [x] 6.3 After `-t 14 -s composerUpdate`: `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 14`. Nothing is written, so a PostgreSQL run is
  not required.
- [x] 6.4 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 6.5 `docs/` and the `Documentation/Changelog/3.0/` entries are part of
  the change; `README.md` and `CONTRIBUTING.md` still only summarize and
  link.
- [ ] 6.6 Archive the change as the last commit of the pull request.
