## Context

See `proposal.md` for the motivation. Facts on `main` at `428cf1a32`, before
the round:

- 28 shipped Fluid files use a ViewHelper of the core namespace for an icon,
  all as `<core:icon …>` tags, none inline. 27 are frontend templates of
  academic_jobs, academic_partners, academic_persons, academic_persons_edit,
  academic_programs, academic_projects and academic_study_plan. One is a
  backend template:
  `typo3-category-types/Resources/Private/Templates/PageCategorySummary.html`,
  rendered above the page module grid by `Backend\PageCategorySummaryRenderer`
  and replaceable through page TSconfig. It stays on the core registry.
- One Fluid comment names `core:icon` in text,
  `academic-persons/Resources/Private/Partials/Profile/PublicProfile/Contact.html`.
- No package has a `Resources/Private/Backend/` directory or any template
  below a `Backend/` directory. The templates live in `Templates/`,
  `Partials/`, `Pages/` (page types), `PageLayoutFallback/Layouts/` and
  `Frontend/Default/` (study plan). `Templates/Email/` of academic_jobs holds
  one HTML and one plain text mail.
- `core` is a global Fluid namespace on v13 and v14 (v13
  `SYS.fluid.namespaces` in `cms-core/Configuration/DefaultConfiguration.php`,
  v14 `cms-core/Configuration/Fluid/Namespaces.php`). It holds three
  icon ViewHelpers on both versions, `icon`, `iconForRecord` and
  `iconForResource`, all rendering through the core `IconFactory`.
- The Fluid parsers (4.6.1 with v13.4.35, 5.3.2 with v14.3.7) have no
  handling for `<!-- -->`, so a ViewHelper inside an HTML comment is parsed
  and rendered. `f:comment` renders an empty string, so a ViewHelper inside it
  never runs.
- After `ace-810-frontend-icon-registry`, the frontend icon ViewHelper lives in
  the namespace `http://typo3.org/ns/FGTCLB/AcademicBase/ViewHelpers`, declared
  per template (prefix `p` where the template already uses it, `ab`
  otherwise). It takes the same arguments as `core:icon` (`identifier`,
  `overlay` and others), reads only the frontend registry and answers an
  unknown identifier with the `default-not-found` drawing. Identifiers come
  from `Configuration/FrontendIcons.php` files (a plain array, the format of
  `Configuration/Icons.php`) and from category_types. As drafted in
  `ace-811-category-type-frontend-icons`, it contributes
  `category_types.<group>.<type>` for every type of a
  `Configuration/CategoryTypes.yaml` that declares an icon, and
  `category_types_group.<group>` for every group that declares one.
- `academic-persons-edit/Tests/Functional/Plugins/AcademicPersonsEditProfileEditingTest.php`,
  `everyIconIdentifierOfTheShippedTemplatesIsRegistered()`, already scans the
  editor's templates for literal identifiers, because a rendered page reaches
  only the states its fixture builds. It covers one extension.
- `packages-dev/monorepo-shared/Tests/Unit/TranslationExtensionNameTest.php` is
  the model: a data provider per `packages/fgtclb/*/Resources/Private`, a
  recursive walk over `*.html`, a quote aware scanner for inline calls, a
  sorted `path:line` list and `assertSame([], $found)` with a message that
  says what to do.

## Goals / Non-Goals

**Goals:**

- A `unit` run fails, with file and line, when a shipped frontend template
  renders an icon through the core registry, or names a literal identifier
  that the frontend registry does not know.
- The single backend exception is visible in the test, with its reason.

**Non-Goals:**

- Running TYPO3. The check reads files, like its eight siblings.
- Templates outside `packages/fgtclb/*/Resources/Private/`, test fixtures
  included. A fixture may render what it likes.

## Decisions

### Every template is a frontend template unless it is listed

The test reads every `*.html` below `packages/fgtclb/*/Resources/Private/`
and treats it as frontend. The exceptions are a constant,
`BACKEND_TEMPLATES`, keyed by the path below `packages/fgtclb/`, with the
reason as value. Today it holds the page module category summary of
category_types. A second test asserts that every listed file exists, so a
stale entry cannot exempt a file created at that path later.

Plain text templates (`*.txt`, one jobs mail today) are not read. They cannot
carry icon markup, and the model reads `*.html` only.

Rejected: a list of frontend directories (`Templates`, `Partials`,
`Layouts`). The repository already keeps frontend templates in `Pages/`,
`PageLayoutFallback/` and `Frontend/Default/` as well, and the next new place
would be unguarded without anyone noticing.
Rejected: exempting every file below a `Backend/` directory. No such
directory exists, and a rule by convention lets a misplaced frontend template
through, while the list costs one line and a stated reason per backend
template.

### What counts as a core icon

A ViewHelper whose name starts with `icon` (`icon`, `iconForRecord`,
`iconForResource`) under a prefix bound to the core namespace: `core`, which
is global, and any prefix a template declares for
`http://typo3.org/ns/TYPO3/CMS/Core/ViewHelpers` with `xmlns:` or
`{namespace …=TYPO3\CMS\Core\ViewHelpers}`. Two notations are matched: a tag
start `<prefix:icon…` and an inline call `prefix:icon…(`, which also covers a
call inside the argument of another ViewHelper and a `->` chain. A mention in
plain text outside a comment is not a call and is not matched. The name is
matched in any letter case, because Fluid finds the ViewHelper for `Icon` as
well.

`iconForRecord` and `iconForResource` are included because they ask the same
`IconFactory`. None is used today.

### Fluid comments are skipped, HTML comments are not

The content of every `<f:comment>…</f:comment>` is blanked before matching,
with its line breaks kept so the reported line stays right. A `core:icon`
there is never rendered. An HTML comment is not blanked, because Fluid
renders a ViewHelper inside it. CDATA is not blanked either. Both directions
of doubt, CDATA and a nested `f:comment` that ends the blanking early,
produce a report and never hide one.

### Second rule: literal identifiers are registered for the frontend

The frontend registry has no fallback, so an identifier registered only in
`Configuration/Icons.php`, or misspelled, renders the placeholder, and only a
functional test that reaches that branch notices. The rule:

- finds the frontend icon ViewHelper under every prefix a template declares
  for the academic_base namespace, as tag (read up to its closing `>` with a
  quote aware scan, tags span lines) and as inline call (balanced
  parentheses, the scanner of the model),
- reads the `identifier` and `overlay` arguments, with `:` or `=` between
  name and value as Fluid accepts both, and without the backslashes of quotes
  escaped inside the argument of another ViewHelper, and checks the ones whose
  value holds no `{`, except an empty `overlay`, which asks for no overlay,
- accepts an identifier that is a key of a `Configuration/FrontendIcons.php`
  of `packages/fgtclb/*`, loaded with `require`, or the identifier
  category_types contributes for a type or group with an icon in a
  `Configuration/CategoryTypes.yaml` there, parsed with `symfony/yaml` (a
  dependency of `typo3/cms-core`).

It is one test over all packages, because the accepted set spans packages,
and it asserts that it read at least one `FrontendIcons.php` and checked at
least one literal identifier. A renamed namespace or ViewHelper would
otherwise turn it into a check of nothing.

Rejected: a functional test that boots TYPO3 and asks the frontend registry.
It also sees the registrations of fixture extensions, it is minutes slower,
and the rule is decidable from the files.
Rejected: checking a partly dynamic identifier (`academic_jobs-{item}`) by its
static prefix. "Some key starts with `academic_jobs-`" holds for any jobs
icon and proves nothing about the one rendered.
Rejected: requiring that the registering package is a dependency of the
template's package. No such defect is known.

### Where the test lives

`packages-dev/monorepo-shared/Tests/Unit/FrontendTemplateIconTest.php`,
`final`, extending PHPUnit's `TestCase` like its siblings. The first rule runs
per extension through a data provider, so a failure names the extension. Not
in academic_base: a split extension runs no tests and cannot see the
templates of the others. `packages-dev/` is covered by `cgl`, not by
`phpstan`, as for every sibling.

The functional scan of academic_persons_edit stays. It also asserts the
reverse direction and the files of its own icons, which this test leaves out.

### Documentation that lists the checks

`AGENTS.md` names every check of `packages-dev/monorepo-shared` twice and
counts them in the Layout bullet, and `docs/testing/unit-tests.md` counts the
classes and lists the checks in *Discovery*. Both get the ninth.
`docs/development/quality-gates.md` names five of the eight checks and
`docs/development/monorepo-layout.md` four of them. Those two enumerations
are replaced by a link to *Discovery* of `docs/testing/unit-tests.md`, which
lists them all.

Rejected: adding the ninth check to the two short lists. They have already
missed three and four checks, and two more hand kept copies drift the same
way.

## Risks / Trade-offs

- [A backend module template is added later] → The test fails and names it.
  The section in `docs/testing/unit-tests.md` says to list it in
  `BACKEND_TEMPLATES` with its reason.
- [The three migration changes leave a `core:icon` behind, or the registry
  change ships a different namespace or argument name] → Task 1.1 checks the
  premises on `main` before the test is written.
- [A `FrontendIcons.php` needs a booted TYPO3 to evaluate] → The format is a
  plain array. A file that is not fails the `require` loudly.
- [The icon consolidation (#617) renames identifiers later] → It renames them
  in both registries and in the templates, and the second rule checks that
  templates and `FrontendIcons.php` agree after the rename.
- [Project templates are not checked] → By design. The Breaking entries of
  the three migration changes tell integrators what to change.

## Migration Plan

None. Nothing is shipped.

## Open Questions

None left. The identifier forms were taken from the drafts of
`ace-811-category-type-frontend-icons`, and the merged change contributes
exactly those: `category_types.<group>.<type>` and
`category_types_group.<group>`, for a type or group whose frontend file,
`frontendIcon` or else `icon`, is not empty. No template renders a category
type icon literally.
