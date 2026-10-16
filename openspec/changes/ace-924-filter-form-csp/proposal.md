## Why

The selects of the sorting and filter form of `academic_partners`
(`packages/fgtclb/academic-partners`) and `academic_projects`
(`packages/fgtclb/academic-projects`) submit the form from an inline
`onchange` handler. Once a site enforces the frontend Content Security Policy
of TYPO3, the browser blocks that handler, changing a select does nothing, and
the form has no button to fall back on (ACE-924).

## What Changes

- Both extensions allow exactly their handler text in the frontend policy of
  TYPO3, `script-src-attr` with `'unsafe-hashes'` and the hash of
  `this.form.submit()`, so every site that inherits the default policy lets the
  handler run. Script elements stay under the policy as before.
- Where the policy of a site allows inline scripts already, with
  `'unsafe-inline'`, a listener of `academic_base` (`packages/fgtclb/academic-base`)
  takes the new directive out again, so the site keeps its own inline handlers
  and the forms follow its configuration. A directive the site declares itself
  stays.
- The forms render a submit button inside `<noscript>` for visitors without
  JavaScript, with a translated label. Visitors with JavaScript see no change.
- A unit test fails for an inline handler its extension does not allow, and
  for an allowed hash no handler needs.
- The manuals, the upgrade guides, a 2.4 `Important` entry per extension and
  `docs/` describe what a site with an override of a partial or a policy of
  its own has to add. The one site that has to act allows inline handlers
  of its own in `script-src` by hash: it adds them to `script-src-attr`.

## Capabilities

### New Capabilities

- `academic-partners/list-form-submission`: how a visitor submits the sorting
  and filter form of the partner list and map, by changing a select under the
  enforced frontend policy of TYPO3, and without JavaScript.
- `academic-projects/list-form-submission`: the same for the form of both
  project lists, the active state select included.

### Modified Capabilities

None. The redirect of a submission, `list-filter-urls`, is unchanged.

## Impact

- `academic_partners` and `academic_projects`: a new
  `Configuration/ContentSecurityPolicies.php` each, the partial
  `SortingAndFilters.html`, the labels `filter.submit` in English and German,
  a functional test each, the manual pages of the lists and of the styling,
  the label tables, `Upgrade/From23To24.rst`, `Upgrade/From23To30.rst` and the
  2.4 entry `Important-FilterFormWorksUnderAContentSecurityPolicy.rst`.
- `academic_base`: the mutation the two policy files use, the listener and its
  unit test.
- `packages-dev/monorepo-shared`: the new `InlineEventHandlerTest`.
- `academic_programs`: the 2.4 entry of branch `2`, which must be identical on
  both branches, its row in `Upgrade/From23To24.rst`, and the note in its 3.0
  documentation that 3.0 no longer allows the handler.
- `AGENTS.md`, `docs/architecture/content-security-policy.md` (new),
  `docs/architecture/Index.md`, `docs/architecture/frontend-javascript-loading.md`,
  `docs/architecture/dependency-injection.md` and `docs/testing/unit-tests.md`.
- TYPO3 v13 and v14 behave the same.

## Non-goals

- Replacing the handlers by a JavaScript module, as `academic_programs` did in
  3.0. The handlers stay.
- Other sources a strict policy blocks, such as the map tiles of the partner
  map, which `img-src` of the default policy blocks. A follow-up handles it.
- A policy the web server sends.
- `academic_programs` and `academic_persons_edit` on branch `2`, which are
  part of the backport.
