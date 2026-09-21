## 1. Backport analysis

- [x] 1.1 Diff every touched file between `main` and `2` before planning
  anything (`docs/workflow/backporting.md`). Result: the template differs by
  the two `f:asset` lines this branch does not have; the TypoScript is the
  same; the rendering test and its fixtures do not exist here at all.
- [x] 1.2 Verify the FSC `Layouts/Default.html` in the installed **v12** tree
  rather than inferring it from v13. It is byte-identical.
- [x] 1.3 Read this branch's own `AGENTS.md` for the rules that are inverted
  here. The relevant one: seven node suites, not eight — there is no `testJs`.

## 2. Tests first

- [x] 2.1 Create `Tests/Functional/ContentElement/` with the fixture, the
  rendering TypoScript and the test class, and record which tests fail before
  the change. Five of seven do.
- [x] 2.2 Cover all four fields of the `frames` palette — `layout`,
  `frame_class`, `space_before_class`, `space_after_class` — the anchor with
  the study plan nested inside it, the bare anchor of "No frame", `linkToTop`
  of the `appearanceLinks` palette, and the header rendered once at the chosen
  heading level.

## 3. Implementation

- [x] 3.1 Switch the template to `<f:layout name="Default"/>` with a `Main`
  section and drop the explicit header render. No asset lines move here.
- [x] 3.2 Run the tests green on v12, then on v13.

## 4. Documentation

- [x] 4.1 Add `Documentation/Changelog/2.4/Important-StudyPlanRendersThroughTheDefaultLayout.rst`
  with the new nesting and the CSS migration.
- [x] 4.2 Add `docs/architecture/content-element-rendering.md`, written for
  this branch: no `record` view variable, and the layout identical on both
  supported core versions. Link it from `docs/architecture/Index.md`.

## 5. Definition of done

- [x] 5.1 After `composerUpdate` for TYPO3 v12: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` green, plus the study plan suite on
  `-d postgres`.
- [x] 5.2 After `composerUpdate` for TYPO3 v13: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` green.
- [x] 5.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 5.4 `docs/` and the `Documentation/` changelog entry are part of the
  change; `README.md` and `CONTRIBUTING.md` still only summarize.
- [x] 5.5 Commit as `[BUGFIX] ACE-702: Honour the study plan appearance` in
  TYPO3 Core format, with no attribution of any kind. The key was filed and
  verified against YouTrack before the subject was written.
- [x] 5.6 Archive the change as the last commit of the pull request.
