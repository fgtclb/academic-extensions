## 1. Tests first

- [x] 1.1 Add a recording listener to `test_plugin_view_event` for the view
  event, the partner and project demand and list events, the persons profile
  and contract query events and the page title placeholder event.
- [x] 1.2 Add tests to `ModifyPluginViewEventTest` asserting one context per
  rendering for the partner list and map, the project list, the persons list
  with and without a letter filter, the persons detail and card and the
  selected profiles and contracts, and one for the pagination setting under a
  letter. Run the ten before the change and watch them fail.
- [x] 1.3 The editor: an editor request dispatches one write event, and the
  other context went to the form data factory, which is not public API. No
  listener can observe the change, so the existing editor tests are what
  covers it.

## 2. The shared context

- [x] 2.1 Change `dispatchModifyPluginViewEvent()` to take the context
  instead of the request and the settings, and update its docblock.
- [x] 2.2 Build the context once per action in the partner, project, program,
  jobs, contacts for pages and B-ITE jobs controllers and hand it to every
  event of the action.
- [x] 2.3 Persons: move the letter filter pagination switch before the query,
  build the persons context once per action and hand it to the repository,
  the page title provider and the view event.
- [x] 2.4 Persons edit: build the context once per action and hand it to the
  form data, the validation and every write event.
- [x] 2.5 The tests of 1.2 pass, and every other plugin test passes
  unchanged.

## 3. Documentation

- [x] 3.1 `docs/architecture/plugin-view-event.md`, `list-plugin-events.md` and
  `class-design.md` describe the context built once per action.
- [x] 3.2 `Important-*.rst` in the 3.0 changelog of `academic_base` (one
  context per rendering) and of `academic_persons` (the query events see the
  pagination switched off under a letter, the view event receives the persons
  context).

## 4. Definition of done

- [x] 4.1 After `composerUpdate` for TYPO3 v13: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` (SQLite and PostgreSQL) green.
- [x] 4.2 After `composerUpdate` for TYPO3 v14: the same.
- [x] 4.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 4.4 The commit message in TYPO3 Core format with ACE-767.
- [x] 4.5 Archive the change as the last commit of the pull request.
