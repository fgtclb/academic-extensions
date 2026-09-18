## Context

See `proposal.md` - Why. Verified on main:

Versions these were measured against: `.Build/vendor` holds 13.4.35 after
`composerUpdate -t 13` and 14.3.7 after `-t 14`; the `core-13`/`core-14`
development instances carry 13.4.34 and 14.3.6 and were used as a second
opinion. Line numbers below are from the trees named with them.

- `academic-programs/Configuration/page.tsconfig:17` registers
  `templates.typo3/cms-backend.academic-programs =
  fgtclb/academic-programs:Resources/Private/Backend`; the only file there is
  `Partials/PageLayout/Doktype20.html`. partners (`Doktype40.html`) and
  projects (`Doktype30.html`) do the same at `page.tsconfig:17`.
- No template of `typo3/cms-backend` renders a `PageLayout/Doktype*` partial:
  grep over `Resources/Private` of 13.4.34, 13.4.35, 14.3.6 and 14.3.7 finds
  none. Core renders `PageLayout/Record`, `PageLayout/Grid`,
  `PageLayout/ActionControls` and `PageLayout/LanguageColumns`.
- **The history is different in each of the three extensions, and only one of
  them ever rendered.** This is where two review rounds were spent, so it is
  written out per extension:
  - `academic_programs`: `861d7d0e9` (2023-03-16) added the markup as
    `Backend/Templates/PageLayout/PageLayout.html`, registered as
    `module.tx_backend.view.templateRootPaths.10` - a full override of core's
    page module template (Feature-90348), which rendered the categories and
    then delegated to core's `PageLayout/Grid`. **It worked.** One day later
    `7b8eac8cf`, *[TASK] Add dynamic page layout rendering*, renamed it to
    `Partials/PageLayout/Doktype20.html` and switched the registration to
    `partialRootPaths`; that is where it stopped rendering.
  - `academic_projects`: `784742607` (2023-09-19, "first commit") added
    `Partials/PageLayout/Doktype30.html` **as a partial from the start**,
    together with the `partialRootPaths` registration - the broken shape,
    copied from programs six months after the rename. It has never rendered,
    and that is why it is the only one of the three that still carried an
    `ext_typoscript_setup.typoscript`: it was born with it, and `abdffb510`
    (2025-04-09) cleaned that file out of the other two and skipped this one.
  - `academic_partners`: `bb4c01300` (2025-02-25, "Initial commit"), same
    shape, same answer. Never rendered.
  - Across every ref, exactly one `Backend/Templates/PageLayout/*` file was
    ever added to this repository: the `academic_programs` one.
  - `PageLayout/Doktype*` is not a core convention on any version, and this is
    a citation rather than an inference: the Fluid page module arrived in TYPO3
    v10.3, and Feature-90348 lists every template and partial it ships. Its
    only per-record convention is
    `PageLayout/Record/<CType>/{Header,Footer,Preview}`, keyed by the content
    type of a **record**, never by the type of a **page**. That closes the one
    gap the measurements leave: v11, where `module.tx_backend.view` was still
    live and where these registrations were originally written.
  TYPO3 v12.0 later removed `module.tx_backend.view` altogether
  (Breaking: #96812) in favour of the TSconfig key, and
  `templates.typo3/cms-backend.academic-*` is that replacement - still naming
  the unrendered partial. So a claim about "the three partials" has to be
  phrased so it holds for all three: none of them renders today, one of them
  used to.
- `academic-projects` carries a **second** dead registration of the same tree,
  `ext_typoscript_setup.typoscript` with
  `module.tx_backend.view.partialRootPaths.ep`. `abdffb510` removed that file
  from programs and partners in April 2025 and skipped projects. It has been
  inert since TYPO3 v12.0 on every branch, this one and `2` alike, but it names
  a directory this change deletes and goes with it.
- Line 28 of each partial translates `sys_category.academic_<ext>.{type}`,
  a key in no XLF file. The real keys are `sys_category.programs.*`
  (programs `locallang.xlf` and `locallang_be.xlf`),
  `sys_category.partners.*` (partners `locallang.xlf` only) and
  `sys_category.projects.*` (projects `locallang.xlf` and
  `locallang_be.xlf`). The `CategoryTypes.yaml` of each extension already
  points every type title at the right key.
- `TYPO3\CMS\Backend\Controller\Event\ModifyPageLayoutContentEvent` exists
  with `addHeaderContent()` on both versions; `PageLayoutController`
  dispatches it (13.4.35 `:141`, 14.3.6 `:125`) and the page module template
  renders `eventContentHtmlTop` (v13 `PageModule.html`, v14
  `PageModule.fluid.html`). The candidate marked v14 unverified; it is
  verified against the v14 tree of the `core-14` instance.
- `category_types` already has the `ct:be.category` ViewHelper, whose
  functional test names these partials as its only callers.

## Goals / Non-Goals

**Goals:**

- One summary implementation for all three page types instead of three
  copies of the same defect.
- Labels that cannot drift from the registered types.
- An override seam that uses the backend's own mechanism.

**Non-Goals:**

- A generic doktype-to-group registry in `category_types`.
- Changing the be.category ViewHelper's contract.

## Decisions

### Listen to `ModifyPageLayoutContentEvent`, one listener per extension

Each of the three extensions gets a `final readonly` listener with TYPO3's
`#[AsEventListener]` (never Symfony's). It hands the request, the page type it
owns and its category group (`programs`/20, `projects`/30, `partners`/40) to
the renderer and appends the answer with `addHeaderContent()` - four lines,
and the two constants are all that is extension specific.

**Amended while implementing.** This decision first placed the page
resolution in the listener: read the page id from the request, load the page
record with the backend user's permission clause, return when the doktype is
not its own. That is ten lines, identical in all three, and the reason this
change exists is that ten identical lines were shipped three times and
carried the same two defects three times. They live in
`PageCategorySummaryRenderer::renderForPageOfType()` instead, which answers
with an empty string for a page of another type - and `addHeaderContent('')`
appends nothing, so the listener needs no branch either.

Reading the page also turned out to be version sensitive, which is a second
reason not to have it three times. `PageLayoutController` resolves the page
through a `pageContext` request attribute on v14 and through its own
`$this->pageinfo` on v13; the attribute does not exist on v13 at all. The
renderer uses `BackendUtility::readPageAccess()`, which exists on every
supported version but is not the same declaration: v13 declares no return type
and wraps its body in an extra `if ((string)$id !== '')`, v14 declares
`array|false`. For an int page id the two behave the same, and the result is
checked with `is_array()` rather than against `false`, which holds either way.

Rejected: overriding the core page module template (one project's attempt).
It breaks with every core template change and collides with any other
extension doing the same. Rejected: one listener in `category_types` fed by a
doktype-to-group map, which would add a registration API for three constants.

### One stateless renderer in `category_types`

`FGTCLB\CategoryTypes\Backend\PageCategorySummaryRenderer` (`final readonly`,
autowired) takes the request, the page uid and the group, creates the view
through `BackendViewFactory::create($request, ['fgtclb/category-types'])` and
renders one template `PageCategorySummary.html`. The three
`Doktype*.html` partials are removed. `BackendViewFactory` resolves
`templates.fgtclb/category-types.<key>` from page TSconfig on both versions
(`BackendViewFactory.php:94`), so an integrator overrides the template the way
the extensions meant to override the core partial.

Naming the package does more than create that key, which the red proof showed:
it is what puts this extension's `Resources/Private/Templates/` on the search
path at all. Registered against `typo3/cms-backend`, as the removed partials
were, the view searches core's template directory and nothing else - so the
failure mode of the old registration was never "the override is ignored" but
"there is no path to this extension".

Rejected: keeping three partials, one per extension. They are identical but
for the group, and the defect shipped three times for that reason.

### Labels from the registered type, not from a key convention

The renderer passes, per type identifier of the group, the `CategoryType`
from the registry together with its resolved title and icon identifier.
Rejected: fixing the key to `sys_category.<group>.{type}` - it repairs the
shipped types but a type added through a project's `CategoryTypes.yaml` (one
project adds `internship`) still renders an empty label.

That rejection is now measured rather than argued.
`PageModuleCategorySummaryIntegratorTypeTest` loads a fixture extension that
adds a type to the `programs` group, and the repaired key convention passes
**every** shipped-type test of all three extensions and fails on that one
test alone.

**Amended while implementing:** the title is resolved in the renderer with
`LanguageService::sL()`, not in the template with `f:translate`. A
`CategoryTypes.yaml` `title` does not have to be an `LLL:` reference - a
literal string is valid and is what a project writing its own type tends to
use - and `f:translate` answers an empty string for one. `sL()` resolves a
reference and returns anything else unchanged. The `LanguageService` is built
with `LanguageServiceFactory::createFromUserPreferences()`, which exists on
both versions; `createForBackendUser()` is v14 only.

### Removing the dead registration is not breaking

The `templates.typo3/cms-backend.academic-*` lines only ever pointed at
partials nothing rendered. An `Important-` changelog entry per extension
names the new override key.

## Risks / Trade-offs

- [The header slot is shared with other extensions' listeners] → the summary
  is appended, never set; ordering among listeners is not guaranteed.
- [A translated page view shows the default-language categories] → categories
  are read from the default-language page, which is where the frontend reads
  them too.
- [The type order follows the registry] → becomes priority-aware with
  `ace-tbd-category-type-priority-order`; no extra work here. Note for that
  change: the summary takes its **row** order from
  `CategoryTypeRegistry::getGroupedCategoryTypes()`, not from
  `CategoryCollection::getAllCategoriesByType()`, which only orders the
  categories inside a row.
- [Three listeners read the page record on every page module request] → three
  extra single-row lookups plus a rootline resolution per render, standard pages
  included, only to compare a doktype. `BackendUtility::getRecord()` has no
  runtime cache; `BEgetRootLine()` is statically cached, so that half is paid
  once. Accepted: memoising it would be service state, which this repository
  forbids, and the alternative - a doktype-to-group registry in
  `category_types` - was rejected above for a different reason.

### Decided: one commit per package, plus the archive

The change lands as five commits: the renderer in `category_types` first, then
one commit per extension (programs, projects, partners), each with its own
verified ACE issue and its own changelog entry, and the archive of this change
last. The original wording said four and predates the archive commit, which
this repository requires as the last commit of the pull request.

Each extension removes its own dead `templates.typo3/cms-backend`
registration and is split to its own repository. Per-commit gates catch a
listener that only works together with a sibling, and per-extension commits
keep the branch `2` backport separable. Rejected: one commit for all four
packages, and two commits (renderer, then all three listeners).

## Migration Plan

Nothing to migrate. A project that overrode `PageLayout/Doktype*.html`
moves the override to the new TSconfig key and deletes its `@todo`; a project
that copied the core page module template deletes its unregistered copy.

## Open Questions

None.
