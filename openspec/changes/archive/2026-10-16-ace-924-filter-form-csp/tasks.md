## 1. Tests first, red on the unchanged code

- [x] 1.1 `InlineEventHandlerTest` of `packages-dev/monorepo-shared`: red on
  TYPO3 v12 without the three policy files, ten handlers reported
  (`.agent/tmp/ace-924-2/logs/unit-inline-12-red.log`). Without the
  `academic_persons_edit` entry its eight handlers are reported, and an entry
  for a package without handlers fails
  (`unit-inline-12-red-exception.log`). Then the same on v13.
- [x] 1.2 `AcademicPartnersContentSecurityPolicyTest`,
  `AcademicProjectsContentSecurityPolicyTest` and
  `AcademicProgramsContentSecurityPolicyTest`: the policy and the button tests
  red on v12 against the unchanged partials without the policy files, four,
  four and two failures (`func-partners-csp-12-red.log`,
  `func-projects-csp-12-red.log`, `func-programs-csp-12-red.log`), then on
  v13. The submission tests pass on the unchanged code, they pin the flow the
  button relies on.
- [x] 1.3 `DropInlineEventHandlerHashesWhereInlineIsAllowedTest` of
  `academic_base` and the site policy scenarios of the three functional tests:
  red on v12 with the listener doing nothing and unregistered, five unit
  failures and four, four and two functional ones
  (`unit-listener-12-red2.log`, `func-*-csp-12-red2.log`), then on v13. The
  scenario of a `script-src-attr` the site declares passes without the
  listener, it pins that the listener leaves it alone.

## 2. Implementation

- [x] 2.1 `InlineEventHandlerMutation` and the listener
  `DropInlineEventHandlerHashesWhereInlineIsAllowed` in `academic_base`, the
  listener registered with the `event.listener` tag in its `Services.yaml`,
  verified by 1.3 green on v12 and v13.
- [x] 2.2 `Configuration/ContentSecurityPolicies.php` in `academic_partners`,
  `academic_projects` and `academic_programs`, each returning the mutation for
  `this.form.submit()`, verified by 1.1 and 1.2 green on v12 and v13.
- [x] 2.3 `Partner/SortingAndFilters.html`, `Project/SortingAndFilters.html`
  and `Program/SortingAndFilters.html` render the submit button inside
  `<noscript>`, labelled `filter.submit` in English and German.

## 3. Documentation

- [x] 3.1 The manuals of the three extensions: a section on the Content
  Security Policy in the configuration chapter and the label tables.
- [x] 3.2 The 2.4 entry
  `Important-FilterFormWorksUnderAContentSecurityPolicy.rst` per extension and
  a row in `Upgrade/From23To24.rst`, byte for byte the files of `main`.
- [x] 3.3 `docs/architecture/content-security-policy.md`, linked from the
  index, the `#[Exclude]` count in `docs/architecture/dependency-injection.md`,
  the new tests in `docs/testing/unit-tests.md`, and `AGENTS.md`.

## 4. Definition of done

- [x] 4.1 `lintPhp`, then per core version after its own `composerUpdate`:
  `cgl -n`, `phpstan`, `unit` and `functional -j 4` on SQLite, green on v12
  and v13.
- [x] 4.2 `lintMarkdown -n`, the rendering of the three manuals and
  `openspec validate --specs --strict`, green.
- [x] 4.3 Commit message in TYPO3 Core format with `Resolves: ACE-924`, and
  the archive of this change with the spec sync as the last commit.
