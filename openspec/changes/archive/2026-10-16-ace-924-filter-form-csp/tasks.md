## 1. Tests first, red on the unchanged code

- [x] 1.1 `InlineEventHandlerTest` of `packages-dev/monorepo-shared`: red on
  TYPO3 v13 without the two policy files, seven handlers reported
  (`.agent/tmp/ace-924/unit-inline-13-red-no-policy.log`), red for a new
  handler text (`unit-inline-13-red-new-handler.log`) and for a hash no
  handler needs (`unit-inline-13-red-orphan-hash.log`), then the same on v14.
- [x] 1.2 `AcademicPartnersContentSecurityPolicyTest` and
  `AcademicProjectsContentSecurityPolicyTest`: the policy and the button
  tests red on v13 against the unchanged partial without the policy file,
  four failures each (`func-partners-csp-13-red.log`,
  `func-projects-csp-13-red.log`), then on v14. The submission test passes on
  the unchanged code, it pins the flow the button relies on.
- [x] 1.3 The scenarios of a site with `'unsafe-inline'` and of a site with a
  `script-src-attr` of its own in both functional tests: red without the
  listener (`r2-func-listener-1{3,4}-red.log`), and the second one red with a
  listener that does not spare a directive of the site
  (`r2-func-listener-14-red-site-attr.log`). The scenario of a site that
  extends `script-src-attr` by `'unsafe-inline'`: red with the listener of
  round 2, which kept the hash next to it (`r3-*-red.log`).
- [x] 1.4 `DropInlineEventHandlerHashesWhereInlineIsAllowedTest` of
  `academic_base` and the new collector cases of `InlineEventHandlerTest`:
  red with the listener disabled and the old patterns
  (`r2-unit-1{3,4}-red.log`).

## 2. Implementation

- [x] 2.1 `InlineEventHandlerMutation` of `academic_base`, and
  `Configuration/ContentSecurityPolicies.php` in `academic_partners` and
  `academic_projects` returning it for `this.form.submit()`, verified by 1.1
  and 1.2 green on v13 and v14.
- [x] 2.2 `Partner/SortingAndFilters.html` and `Project/SortingAndFilters.html`
  render the submit button inside `<noscript>`, labelled `filter.submit` in
  English and German.
- [x] 2.3 The listener `DropInlineEventHandlerHashesWhereInlineIsAllowed` of
  `academic_base`, verified by 1.3 and 1.4 green on v13 and v14, including
  the reduction of our hashes in a `script-src-attr` with `'unsafe-inline'`.

## 3. Documentation

- [x] 3.1 The manuals of both extensions: a section on the Content Security
  Policy in the list page, with the listener, links from the map and the
  selected projects pages, the tiles of the map, the markup on the styling
  pages and the label tables.
- [x] 3.2 The 2.4 entry
  `Important-FilterFormWorksUnderAContentSecurityPolicy.rst` of partners,
  projects and programs, identical on branch `2`, true on TYPO3 v12 and v13
  and without references that fail there, a row in `Upgrade/From23To24.rst`
  of all three and in `Upgrade/From23To30.rst` of partners and projects, and
  the note in the 3.0 documentation of programs.
- [x] 3.3 `docs/architecture/content-security-policy.md`, linked from the
  index and from the page on frontend JavaScript loading, the listener in
  `docs/architecture/dependency-injection.md`, the new test in
  `docs/testing/unit-tests.md`, and `AGENTS.md`.

## 4. Definition of done

- [x] 4.1 `lintPhp`, then per core version after its own `composerUpdate`:
  `cgl -n`, `phpstan`, `unit` and `functional -j 4` on SQLite, green.
- [x] 4.2 `lintMarkdown -n` and the rendering of the manuals of partners,
  projects and programs, green.
- [x] 4.3 Commit message in TYPO3 Core format with `Resolves: ACE-924`, and
  the archive of this change with the spec sync as the last commit.
