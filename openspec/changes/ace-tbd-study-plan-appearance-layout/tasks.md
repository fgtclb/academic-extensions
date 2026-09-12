## 1. Tests first, on TYPO3 v13

- [ ] 1.1 Extend the functional content element test: with
  `frame_class = ruler-before` and `space_before_class = large` the output
  contains `frame-ruler-before`, `frame-space-before-large` and `id="c<uid>"`;
  run it before the change and record that none of them is present.
- [ ] 1.2 Assert that a header with `header_layout = 2` appears exactly once
  as `h2`, and that `header_layout = 100` renders no header.
- [ ] 1.3 Assert that `frame_class = none` renders no frame wrapper and still
  renders the study plan.

## 2. Implementation

- [ ] 2.1 Switch the template to `<f:layout name="Default"/>` with a `Main`
  section, drop the explicit header render, and move the asset lines into the
  section.
- [ ] 2.2 Run group 1 and the existing content element tests green on v13,
  then on v14.
- [ ] 2.3 Check the element in the `core-13` and `core-14` instances
  (bootstrap_package): frame, spacing and anchor applied, filter and dialogs
  working by mouse and keyboard.

## 3. Documentation

- [ ] 3.1 Add `Documentation/Changelog/3.0/Important-StudyPlanRendersThroughDefaultLayout.rst`,
  showing the new nesting of header and wrapper.
- [ ] 3.2 Confirm `docs/` needs no change and state it in the pull request.

## 4. File the issue

- [ ] 4.1 After implementation, file the ACE issue in YouTrack and verify the
  key.
- [ ] 4.2 Rename the change to `ace-<NNN>-study-plan-appearance-layout`.
- [ ] 4.3 Commit as `[BUGFIX] ACE-<NNN>: Render the study plan in the Default
  layout` in TYPO3 Core format.

## 5. Backport

- [ ] 5.1 Backport: separate change on branch `2` after a backport analysis
  (`docs/workflow/backporting.md`); the template is the same there.

## 6. Definition of done

- [ ] 6.1 After `composerUpdate` for TYPO3 v13: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` green.
- [ ] 6.2 After `composerUpdate` for TYPO3 v14: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` green.
- [ ] 6.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 6.4 `docs/` and the `Documentation/` changelog entry are part of the
  change; `README.md` and `CONTRIBUTING.md` still only summarize.
- [ ] 6.5 Archive the change as the last commit of the pull request.
