## Context

See `proposal.md` - Why. Verified on main:

- `academic-programs/Configuration/page.tsconfig:17` registers
  `templates.typo3/cms-backend.academic-programs =
  fgtclb/academic-programs:Resources/Private/Backend`; the only file there is
  `Partials/PageLayout/Doktype20.html`. partners (`Doktype40.html`) and
  projects (`Doktype30.html`) do the same at `page.tsconfig:17`.
- No template of `typo3/cms-backend` renders a `PageLayout/Doktype*` partial:
  grep over `Resources/Private` of 13.4.35 (`.Build/vendor`) and 14.3.6
  (`core-14/vendor`) finds none.
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
`#[AsEventListener]` (never Symfony's). It reads the page id from the
request, loads the page record with the backend user's permission clause,
returns when the doktype is not its own, and otherwise appends the rendered
summary with `addHeaderContent()`. The page belongs to the listener's
extension, so the doktype and the group (`programs`/20, `projects`/30,
`partners`/40) are constants of that listener.

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

Rejected: keeping three partials, one per extension. They are identical but
for the group, and the defect shipped three times for that reason.

### Labels from the registered type, not from a key convention

The renderer passes, per type identifier of the group, the `CategoryType`
from the registry; the template uses its `title` and icon. Rejected: fixing
the key to `sys_category.<group>.{type}` - it repairs the shipped types but a
type added through a project's `CategoryTypes.yaml` (one project adds
`internship`) still renders an empty label.

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
  `ace-tbd-category-type-priority-order`; no extra work here.

### Decided: four stacked commits

The change lands as four stacked commits: the renderer in `category_types`
first, then one commit per extension (programs, projects, partners), each
with its own verified ACE issue and its own `Important-` changelog entry.

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
