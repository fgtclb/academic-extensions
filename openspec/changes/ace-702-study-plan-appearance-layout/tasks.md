## 1. Tests first, on TYPO3 v13

- [x] 1.1 Extend the functional content element test: with
  `frame_class = ruler-before` and `space_before_class = large` the output
  contains `frame-ruler-before`, `frame-space-before-large` and `id="c<uid>"`;
  run it before the change and record that none of them is present.
- [x] 1.2 Assert that a header with `header_layout = 2` appears exactly once
  as `h2`, and that `header_layout = 100` renders no header.
- [x] 1.3 Assert that `frame_class = none` renders no frame wrapper and still
  renders the study plan.

## 2. Implementation

- [x] 2.1 Switch the template to `<f:layout name="Default"/>` with a `Main`
  section, drop the explicit header render, and move the asset lines into the
  section.
- [x] 2.2 Run group 1 and the existing content element tests green on v13,
  then on v14.
- [x] 2.3 Cover what that check was for, by test rather than by hand. The
  `bk2k/bootstrap-package` half is verified by reading its
  `Layouts/ContentElements/Default.html`, which renders the same `Header` and
  `Main` sections inside a `bk2k:frame` with `id="c{data.uid}"`; the frame,
  spacing and anchor are asserted by the functional test on the
  EXT:fluid_styled_content layout. The interaction, by mouse **and** by
  keyboard, is asserted by a new `testJs` test against the markup the layout
  now produces. The DDEV instances were not started - stated in the pull
  request.

## 3. Documentation

- [x] 3.1 Add `Documentation/Changelog/3.0/Important-StudyPlanRendersThroughTheDefaultLayout.rst`,
  showing the new nesting of header and wrapper.
- [x] 3.2 Add `docs/architecture/content-element-rendering.md` and link it from
  `docs/architecture/Index.md`: the two rendering shapes, what the `Default`
  layout renders, and the `record` view variable TYPO3 v14 needs. The concept
  this change turns on is not in `docs/` at all today.

## 4. Node coverage of the interaction

- [x] 4.0 `academic-study-plan.ts` used a constructor parameter property, which
  node cannot strip, so no `testJs` test could import the module at all. Declare
  and assign the field instead, rebuild the artifact, and enforce the ban with
  eslint rules rather than with the prose of `AGENTS.md`. Map the module
  specifier in `Build/tsconfig.json`, as the comment there asks.

## 5. File the issue

- [x] 5.1 After implementation, file the ACE issue in YouTrack and verify the
  key. Filed as ACE-702, Type Bug, Version 2.4.0, relates to ACE-233.
- [x] 5.2 Rename the change to `ace-702-study-plan-appearance-layout`.
- [x] 5.3 Commit as `[BUGFIX] ACE-702: Honour the study plan appearance`
  in TYPO3 Core format, with the node coverage of group 4 as a second commit.

## 6. Backport

- [ ] 6.1 Backport: separate change on branch `2` after a backport analysis
  (`docs/workflow/backporting.md`); the template is the same there.

## 7. Definition of done

- [x] 7.1 After `composerUpdate` for TYPO3 v13: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` green.
- [x] 7.2 After `composerUpdate` for TYPO3 v14: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` green.
- [x] 7.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 7.3a The node suites, which this change now also touches:
  `lintTypescript`, `typecheckJs`, `testJs` and `checkJsBuildClean` green.
- [x] 7.4 `docs/` and the `Documentation/` changelog entry are part of the
  change; `README.md` and `CONTRIBUTING.md` still only summarize.
- [ ] 7.5 Archive the change as the last commit of the pull request.
