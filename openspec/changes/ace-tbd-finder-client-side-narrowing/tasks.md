## 1. Server-side payload

- [ ] 1.1 Render `data-program-categories`, the translated count pattern and
  an empty, visually hidden `role="status"` region from the finder action
  and template; add a functional plugin test asserting the JSON for a
  three-program fixture, the pattern attribute and the empty region, and
  show it fails without them.
- [ ] 1.2 Assert in the same test that a hidden program and a program outside
  the storage are absent from the map; prove the assertion by building the map
  from an unrestricted query once and watching it go red.

## 2. Frontend module

- [ ] 2.1 Add `Resources/Private/TypeScript/frontend/program-finder.ts` (no
  `enum`, `namespace`, parameter properties or decorators) with an exported
  initialiser, add `Configuration/JavaScriptModules.php`, and load the module
  from the finder template with `f:asset.module`.
- [ ] 2.2 Add `Tests/JavaScript/program-finder.test.ts` (jsdom): with three
  programs, selecting degree A disables the topic only program B carries,
  clearing restores it, and the count reads two; remove the disable step, watch
  the test fail, restore it.
- [ ] 2.3 Cover the preselected value: a fixture with a `selected` option
  disables the excluded topic on initialisation; prove it by skipping the
  initial pass once.
- [ ] 2.4 Cover the live region in the same test file: after a change the
  `role="status"` element carries the count sentence of the button, and it
  is still empty after initialisation; remove the region update, watch the
  first assertion fail, restore it. Check the announcement once by hand with
  a screen reader and record the result in the pull request.
- [ ] 2.5 Run `buildJs`, commit the output, and verify `checkJsBuildClean`,
  `typecheckJs`, `lintTypescript` and `testJs` are green.

## 3. Documentation

- [ ] 3.1 Describe the narrowing, the count with its live region and the
  no-JavaScript behaviour in the finder section of
  `academic-programs/Documentation/`, and add
  `Documentation/Changelog/3.0/Feature-ProgramFinderNarrowsOptions.rst`.
- [ ] 3.2 Check `docs/development/frontend-assets.md` and
  `docs/testing/javascript-tests.md` for per-extension lists and add
  `academic_programs` wherever one exists; verify with `lintMarkdown -n`.

## 4. File the issue

- [ ] 4.1 After implementation, file the ACE issue in YouTrack, relate it to
  ACE-91, and verify the key.
- [ ] 4.2 Rename the change to `ace-<NNN>-finder-client-side-narrowing`.
- [ ] 4.3 Commit as `[FEATURE] ACE-<NNN>: Narrow the program finder options`
  in TYPO3 Core format.

## 5. Definition of done

- [ ] 5.1 After `composerUpdate` for TYPO3 v13: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` green.
- [ ] 5.2 After `composerUpdate` for TYPO3 v14: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` green.
- [ ] 5.3 `lintMarkdown -n`, `checkRstRenderingAll` and the node suites of
  2.5 green.
- [ ] 5.4 `docs/` and the `Documentation/` changelog entry are part of the
  change; `README.md` and `CONTRIBUTING.md` still only summarize.
- [ ] 5.5 Archive the change as the last commit of the pull request.
