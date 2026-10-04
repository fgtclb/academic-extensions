## 1. Verify the premises

- [ ] 1.1 On `main` after `ace-812-persons-frontend-icons`,
  `ace-813-jobs-study-plan-frontend-icons` and
  `ace-814-programs-partners-projects-frontend-icons` are merged, confirm
  with a scan over `packages/fgtclb/*/Resources/Private/**/*.html` that
  `typo3-category-types/Resources/Private/Templates/PageCategorySummary.html`
  is the only file with a core icon ViewHelper outside `<f:comment>`, in tag
  and inline notation. Confirm from the merged
  `ace-810-frontend-icon-registry` the namespace URI of the frontend icon
  ViewHelper, its tag name and the names of its `identifier` and `overlay`
  arguments, and from `ace-811-category-type-frontend-icons` the identifier
  forms category_types contributes to the frontend registry (types, and
  groups if any). If anything differs from `design.md`, stop and update this
  change.

## 2. First rule: no core icon in a frontend template

- [ ] 2.1 Add `packages-dev/monorepo-shared/Tests/Unit/FrontendTemplateIconTest.php`
  with the per extension test of the first rule, the `BACKEND_TEMPLATES`
  constant holding the category summary with its reason, and the test that
  every listed file exists, as `design.md` describes. Verify it is green on
  `main` with `-s unit packages-dev/monorepo-shared/Tests/Unit/FrontendTemplateIconTest.php`.
- [ ] 2.2 Show it red, one at a time, and restore after each:
  - `<core:icon identifier="academic-persons-envelope" />` back in
    `academic-persons/Resources/Private/Partials/Profile/PublicProfile/Contact.html`
    reports that file and line,
  - `{core:icon(identifier: 'academic-study-plan-plus')}` in a study plan
    partial reports the inline call,
  - a declared `xmlns:c="http://typo3.org/ns/TYPO3/CMS/Core/ViewHelpers"`
    with `<c:iconForRecord …>` reports the aliased prefix,
  - the tag of the first item inside an HTML comment is reported, the same
    tag inside `<f:comment>` is not,
  - a misspelled path in `BACKEND_TEMPLATES` fails the existence test.

## 3. Second rule: literal identifiers are registered for the frontend

- [ ] 3.1 Add the second test: literal `identifier` and `overlay` values of
  the frontend icon ViewHelper, in tag and inline notation, against the keys
  of every `packages/fgtclb/*/Configuration/FrontendIcons.php` and the
  category type identifiers of every `Configuration/CategoryTypes.yaml`
  there, with the assertion that at least one file and one identifier were
  read. Verify it is green on `main`.
- [ ] 3.2 Show it red, one at a time, and restore after each:
  - a misspelled literal identifier in an academic_persons_edit partial,
  - a literal identifier that is registered only in an `Icons.php` of the
    repository,
  - an entry removed from a `FrontendIcons.php` whose identifier a template
    names literally,
  - the namespace URI in the test changed, which fails the "read at least
    one identifier" assertion.
  Also show `category_types.partners.region` named literally in a partner
  partial passes, then restore.

## 4. Documentation

- [ ] 4.1 `docs/testing/unit-tests.md`: a section for the new test before
  *See also* (both rules, the allowed backend template and how to add one,
  why HTML comments count and Fluid comments do not, what dynamic
  identifiers leave to the functional tests), the check in the *Discovery*
  paragraph, and the class count of the first paragraph, recounted with the
  command printed there (eight in `packages-dev/monorepo-shared` become nine).
- [ ] 4.2 `AGENTS.md`: the Layout bullet of `packages-dev/monorepo-shared`
  (eight unit tests become nine, and the sentence names the new check) and
  the list of its checks in the test discovery paragraph.
- [ ] 4.3 `docs/development/quality-gates.md` and
  `docs/development/monorepo-layout.md`: replace their partial lists of the
  `monorepo-shared` checks with a link to *Discovery* of
  `docs/testing/unit-tests.md`.
- [ ] 4.4 `docs/architecture/icons.md`: in the section on keeping a
  template's icons resolvable, as `ace-810-frontend-icon-registry` left it, a
  paragraph naming the check and linking its section.
- [ ] 4.5 No changelog entry: nothing an installation observes changes, and
  `packages-dev/monorepo-shared` is never released.

## 5. File the issue

- [ ] 5.1 File the ACE issue (Task, Version 3.0.0, subtask of ACE-10, relates
  to the umbrella issue of the frontend icon round and to the issues of the
  three migration changes), verify the key, and rename the change to
  `ace-NNN-frontend-icon-guard`.

## 6. Definition of done

- [ ] 6.1 `lintPhp` green.
- [ ] 6.2 After `-t 13 -s composerUpdate`: `cgl -n`, `phpstan` and `unit`
  green with `-t 13`.
- [ ] 6.3 After `-t 14 -s composerUpdate`: `cgl -n`, `phpstan` and `unit`
  green with `-t 14`.
- [ ] 6.4 `functional` green for v13 and v14, each after its own
  `composerUpdate`: SQLite with `-j auto`, PostgreSQL with `-b docker -j 8`
  and MariaDB with `-j 8`. The change adds no runtime code, but the run is the
  proof that the round as a whole stays green.
- [ ] 6.5 `lintMarkdown -n` and `checkRstRenderingAll` green. `docs/` updated, `README.md` and
  `CONTRIBUTING.md` still only summarize.
- [ ] 6.6 Commit `[TASK] ACE-<NNN>: Check frontend template icons` in TYPO3 Core
  format, without attribution of any tool, and archive the change as the
  last commit of the pull request.
- [ ] 6.7 No backport: branch `2` has no frontend icon registry.
