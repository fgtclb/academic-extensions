## 1. Event and trait

- [ ] 1.0 Verify that `ace-tbd-single-action-context-interface` and
  `ace-tbd-extension-point-policy` have landed; stop otherwise.
- [ ] 1.1 Add the `final` `ModifyPluginViewEvent`, with an `@api` docblock
  tag, to `academic-base/Classes/Event/` and a unit test for its getters.
- [ ] 1.2 Add the dispatching trait to `academic-base/Classes/Controller/`,
  building the `academic_base` context from request and settings; unit test
  with a stub dispatcher asserting one dispatch with the given view.

## 2. Test harness

- [ ] 2.1 Add a fixture extension (psr-4 autoload in the root
  `composer.json`, then `composerUpdate`) with a listener that records each
  call (plugin, action, view) and assigns `probe`, plus a template override for
  one plugin that outputs `{probe}`.
- [ ] 2.2 Functional frontend test with a data provider over all seventeen
  rendering paths, asserting exactly one recorded call per rendering and the
  plugin and action names; the persons list case asserts `probe` in the
  output.

## 3. Dispatch in the controllers

- [ ] 3.1 Call the trait method on every rendering path of the persons,
  jobs, partners, programs (both controllers), projects, contacts for pages
  and BITE jobs controllers, after the action's own assignments, where the
  removed specific event was dispatched; the data provider rows turn green
  one by one, and removing any single call turns its row red again.
- [ ] 3.2 Cover the early returns (job detail without a job, selected
  profiles and selected contracts without a selection); shown red by
  removing the call on the early path only.
- [ ] 3.3 In the new job form, dispatch before the `validations` assignment;
  a test listener assigning `validations` leaves the configured validations
  in the output, shown red by moving the dispatch after the assignment.
- [ ] 3.4 Remove `ModifyJobControllerNewActionViewEvent` and the persons
  list, detail, selected profiles and selected contracts events, with their
  dispatches and unit tests; the detail action takes the title format from
  the setting or the default directly. Functional test with a fixture
  listener whose parameter is typed against the removed persons list event
  class (a line-scoped phpstan ignore, which is exactly what the `Breaking-`
  entry warns about): the container builds, the persons list renders, and
  the listener is not called; shown red by keeping the dispatch. The
  existing detail page title tests stay green, and one with the setting
  `pageTitleFormat` is shown red by ignoring the setting.

## 4. Documentation

- [ ] 4.1 Document the event on the extension point page of `academic_base`
  created by `ace-tbd-extension-point-policy`, with a listener example using
  TYPO3's `#[AsEventListener]`.
- [ ] 4.2 Add `academic-base/Documentation/Changelog/3.0/Feature-ModifyPluginViewEvent.rst`
  and a short `Feature-PluginViewEvent.rst` pointing to it in the 3.0
  changelog of persons, jobs, partners, programs, projects, contacts for pages
  and BITE jobs.
- [ ] 4.3 Add `academic-jobs/Documentation/Changelog/3.0/Breaking-RemovedModifyJobControllerNewActionViewEvent.rst`
  and `academic-persons/Documentation/Changelog/3.0/Breaking-RemovedProfileViewEvents.rst`
  from `Build/Documentation/Templates/Changelog-Breaking.rst`, in the style
  of a TYPO3 core hook-to-event transition: the removed classes, the impact
  (no longer dispatched, no fatal error, reported by phpstan), the affected
  installations, and a migration example to the generic event that links
  `Feature-ModifyPluginViewEvent.rst`, plus the replacements of the persons
  data setters.
- [ ] 4.4 Update the extension point section of
  `docs/architecture/class-design.md`; drop the two removed selection events
  from its `strict_types` table and recount that section; update the fixture
  extension list in `docs/testing/fixture-extensions.md`.

## 5. File the issue

- [ ] 5.1 After implementation, file the ACE issue in YouTrack, rename the
  change to `ace-<NNN>-generic-plugin-view-event`, and commit in TYPO3 Core
  format as `[!!!][FEATURE] ACE-<NNN>: Add a view event to every plugin`.

## 6. Definition of done

- [ ] 6.1 `lintPhp` green.
- [ ] 6.2 After `-t 13 -s composerUpdate`: `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 13`.
- [ ] 6.3 After `-t 14 -s composerUpdate`: `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 14`. Nothing is written, so a PostgreSQL run is
  not required.
- [ ] 6.4 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 6.5 `docs/` and the `Documentation/Changelog/3.0/` entries are part of
  the change; `README.md` and `CONTRIBUTING.md` still only summarize and
  link.
- [ ] 6.6 Archive the change as the last commit of the pull request.
