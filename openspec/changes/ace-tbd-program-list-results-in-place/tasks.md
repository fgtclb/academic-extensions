## 1. Tests first, on TYPO3 v13

- [ ] 1.1 Add a functional plugin test for the markup contract: the filter
  form carries no inline `onchange`, has a submit button, and form and
  results sit in a wrapper with the content element uid; the results region
  carries the count, and the status region is empty. Run it before the
  change and record which assertions fail.
- [ ] 1.2 Assert in the same class that submitting the form without
  JavaScript (`submitFrontendForm()`) still redirects to the filter URL and
  renders the filtered list.

## 2. Templates

- [ ] 2.1 Remove the inline handlers from `Program/DemandCategories.html` and
  `Program/DemandSorting.html`; add the submit button, the wrapper, the count
  attribute and the status region; add the labels (English and German, one
  line per `source`/`target`); run group 1 green on v13, then on v14.

## 3. Frontend module

- [ ] 3.1 Add `Resources/Private/TypeScript/frontend/program-list.ts` with an
  exported initialiser, `Configuration/JavaScriptModules.php` if not present,
  and load it with `f:asset.module`.
- [ ] 3.2 Add `Tests/JavaScript/program-list.test.ts` (jsdom, a stubbed
  `fetch`): a change posts the form, replaces the results and the selects of
  the matching list only, keeps focus, pushes `response.url`, and writes the
  status sentence; show each assertion red by removing its step once.
- [ ] 3.3 Cover `popstate`, an aborted request, a failed request and a
  response without the list element (falls back to `location.assign()`), and
  the hidden submit button.
- [ ] 3.4 Run `buildJs`, commit the output; `checkJsBuildClean`,
  `typecheckJs`, `lintTypescript` and `testJs` green.

## 4. Documentation

- [ ] 4.1 Describe the in-place update, the address bar, the announcement and
  the no-JavaScript form in `academic-programs/Documentation/`, and what an
  override adds.
- [ ] 4.2 Add `Documentation/Changelog/3.0/Feature-ProgramListUpdatesInPlace.rst`
  and an `Important-*.rst` for overrides of the list partials.
- [ ] 4.3 Update `docs/architecture/list-filter-urls.md` (the list requests its
  filter URLs without a reload) and the per-extension lists in
  `docs/development/frontend-assets.md` and `docs/testing/javascript-tests.md`;
  verify with `lintMarkdown -n`.

## 5. Definition of done

- [ ] 5.1 After `composerUpdate` for TYPO3 v13: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` green.
- [ ] 5.2 After `composerUpdate` for TYPO3 v14: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` green.
- [ ] 5.3 `lintMarkdown -n`, `checkRstRenderingAll`, and the JavaScript suites
  of 3.4 green.
- [ ] 5.4 `docs/` and the `Documentation/` changelog entries are part of the
  change; `README.md` and `CONTRIBUTING.md` still only summarize.
- [ ] 5.5 File the ACE issue after implementation, rename the change, commit
  in TYPO3 Core format, and archive the change as the last commit of the pull
  request.
