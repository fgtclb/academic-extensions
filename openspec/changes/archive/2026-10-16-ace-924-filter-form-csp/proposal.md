## Why

The selects of the sorting and filter forms of `academic_partners`
(`packages/fgtclb/academic-partners`), `academic_projects`
(`packages/fgtclb/academic-projects`) and `academic_programs`
(`packages/fgtclb/academic-programs`) submit the form from an inline
`onchange` handler. Once a site enforces the frontend Content Security Policy
of TYPO3, the browser blocks that handler, changing a select does nothing, and
the form has no button to fall back on (ACE-924).

This backports the `main` change `ace-924-filter-form-csp`, archived there as
`openspec/changes/archive/2026-10-16-ace-924-filter-form-csp/`, and adds
`academic_programs`, whose list on `main` already left the handlers behind in
3.0.

## What Changes

- The three extensions allow exactly their handler text in the frontend policy
  of TYPO3, `script-src-attr` with `'unsafe-hashes'` and the hash of
  `this.form.submit()`, so every site that inherits the default policy lets the
  handler run. Script elements stay under the policy as before.
- A site whose policy allows inline scripts already, and declares no
  `script-src-attr` itself, gets none from the extensions, so its own inline
  handlers keep working. A `script-src-attr` of the site stays as declared.
- The forms render a submit button inside `<noscript>` for visitors without
  JavaScript, with a translated label. Visitors with JavaScript see no change.
- A unit test of the repository fails for an inline event handler of a
  template that its extension does not allow, and for an allowed hash no
  handler needs. The profile editor of `academic_persons_edit` is a documented
  exception.
- Manuals, upgrade guides, a 2.4 `Important` entry per extension and `docs/`
  describe what a site has to add. Nothing is breaking.

The behaviour is the same on TYPO3 v12 and v13.

## Capabilities

### New Capabilities

- `academic-partners/list-form-submission`: how a visitor submits the sorting
  and filter form of the partner list and map, by changing a select under the
  enforced frontend policy of TYPO3, and without JavaScript.
- `academic-projects/list-form-submission`: the same for the form of both
  project lists, the active state select included.
- `academic-programs/list-form-submission`: the same for the form of the
  program list.

### Modified Capabilities

None. What a submission filters, `list-filter`, is unchanged.

## Impact

- `academic_base` (`packages/fgtclb/academic-base`): the mutation the three
  extensions declare and the event listener that takes it out again, with a
  unit test.
- `academic_partners`, `academic_projects` and `academic_programs`: a new
  `Configuration/ContentSecurityPolicies.php` each, the partial
  `SortingAndFilters.html`, the labels `filter.submit` in English and German,
  a functional test each, the configuration manual and the label table,
  `Upgrade/From23To24.rst` and the 2.4 entry
  `Important-FilterFormWorksUnderAContentSecurityPolicy.rst`. The entries and
  upgrade rows of partners and projects are the same files as on `main`.
- `packages-dev/monorepo-shared`: the new `InlineEventHandlerTest`.
- `AGENTS.md`, `docs/architecture/content-security-policy.md` (new),
  `docs/architecture/Index.md`, `docs/architecture/dependency-injection.md`
  and `docs/testing/unit-tests.md`.

## Non-goals

- Replacing the handlers by a script, as the maintainer decided.
- The profile editor of `academic_persons_edit`: its handlers are mostly built
  from variables, and it loads CKEditor 4 from a CDN the policy blocks anyway.
- Other blocked sources, such as the tiles of the partner map.
- A policy the web server sends.
