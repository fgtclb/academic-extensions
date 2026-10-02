## 1. Server-side payload

- [x] 1.1 Render `data-academic-programs-finder-programs`, the translated
  count patterns `data-academic-programs-finder-count-one` and
  `data-academic-programs-finder-count-other`, the marker
  `data-academic-programs-finder-select` on each category select, the count
  span in the button and an empty, visually hidden `role="status"` region from
  the finder action and template. A functional plugin test asserts the JSON for
  a three-program fixture, the patterns, the marked selects, the empty region
  and the import map entry, and fails without them.
- [x] 1.2 Assert in the same test that a hidden program and a program outside
  the storage are absent. Proven by building the list from an unrestricted
  query once. A listener that removes a program removes it from the list as
  well, proven by building it from the programs before the event.
- [x] 1.3 With the finder field `settings.filter.includeSubcategories` on, list
  the visible ancestors of each assigned category as well, so the module
  enables a parent whose subcategory is carried, as the server does. Both
  states of the field are asserted, and the test fails without the ancestors.

## 2. Frontend module

- [x] 2.1 Add `Resources/Private/TypeScript/frontend/program-finder.ts` (no
  `enum`, `namespace`, parameter properties or decorators) with an exported
  initialiser, add `Configuration/JavaScriptModules.php`, and load the module
  from the finder template with `f:asset.module`.
- [x] 2.2 Add `Tests/JavaScript/program-finder.test.ts` (jsdom) on the data of
  the functional fixture: selecting a degree disables the topic only programs
  of the other degree carry, clearing restores it, and the count follows.
  Proven by removing the disable step. The guards for options the server
  disabled, for the selected option, for unmarked selects and for a missing
  count pattern are proven the same way.
- [x] 2.3 Cover the preselected value: a fixture with a `selected` option
  disables the excluded topic on initialisation. Proven by skipping the initial
  pass once.
- [x] 2.4 Cover the live region in the same test file: after a change of the
  count the `role="status"` element carries the count sentence, it is empty
  after initialisation, and a change that keeps the count writes nothing.
  Proven by removing the region update. Checked in a browser on both cores
  through the accessibility tree, not with a screen reader.
- [x] 2.5 Run `buildJs`, commit the output, and verify `checkJsBuildClean`,
  `typecheckJs`, `lintTypescript` and `testJs` are green.

## 3. Documentation

- [x] 3.1 Describe the narrowing, the count with its live region, the relation
  to `hideDisabledOptions` and the no-JavaScript behaviour in the finder section
  of `academic-programs/Documentation/`, correct the finder paragraph of the
  developer page, and add
  `Documentation/Changelog/3.0/Feature-ProgramFinderNarrowsOptions.rst`.
- [x] 3.2 Add `academic-programs` to the list of extensions with sources in
  `docs/development/frontend-assets.md`, with a section on data handed over as
  one JSON attribute. `docs/testing/javascript-tests.md` has no list of
  extensions. Verified with `lintMarkdown -n`.

## 4. The issue

- [x] 4.1 ACE-91 names this change as its follow-up, so it implements ACE-91
  and no issue of its own is filed. ACE-91 relates to the project issues that
  asked for it.
- [x] 4.2 Rename the change to `ace-91-finder-client-side-narrowing`.
- [x] 4.3 Commit as `[FEATURE] ACE-91: Narrow the program finder options` in
  TYPO3 Core format.

## 5. Definition of done

- [x] 5.1 After `composerUpdate` for TYPO3 v13: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` green.
- [x] 5.2 After `composerUpdate` for TYPO3 v14: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` green.
- [x] 5.3 `lintMarkdown -n`, `checkRstRenderingAll` and the node suites of
  2.5 green.
- [x] 5.4 `docs/` and the `Documentation/` changelog entry are part of the
  change. `README.md` and `CONTRIBUTING.md` still only summarize.
- [x] 5.5 Archive the change as the last commit of the pull request.
