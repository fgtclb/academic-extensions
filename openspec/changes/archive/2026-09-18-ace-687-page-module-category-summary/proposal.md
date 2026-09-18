## Why

`academic_programs`, `academic_projects` and `academic_partners` each ship a
Fluid partial meant to show the categories of their page type in the page
module, and none of the three renders. All three are registered in
`Configuration/page.tsconfig` as `templates.typo3/cms-backend.academic-<ext>`,
an override of a partial of `EXT:backend`, and no template of `EXT:backend`
renders a `PageLayout/Doktype*` partial — not on TYPO3 v12 and not on v13. The
partials are byte-identical to the ones on `main`, so this branch carries the
same defect, and it carries it for the same reason: the markup worked as a full
page module template override until March 2023, when a rename turned it into a
partial nothing asks for.

Each partial also translates `sys_category.academic_<ext>.{type}`, a key that
exists in no XLF file of any of the three; the real keys are
`sys_category.<group>.*`.

This is the backport of ACE-687, ACE-688, ACE-689 and ACE-690, whose
implementation on `main` is pull request #667, re-derived for TYPO3 v12 and v13.

## What Changes

- The page module shows a summary of the assigned categories, grouped by
  category type, above the content grid of a program page (doktype 20), a
  project page (doktype 30) and a partner page (doktype 40).
- Every type of the page's category group is listed, with its registered title
  and icon; a type without categories says so, hidden categories are marked.
- The labels come from the registered category type titles, so types an
  integrator adds through `CategoryTypes.yaml` are labelled too.
- The dead backend template override registration is removed from the page
  TSconfig of the three extensions, together with their unused partials, and
  `academic_projects` loses a second, older registration of the same directory
  in `ext_typoscript_setup.typoscript`.
- The summary template can be overridden through page TSconfig.

Behaviour is identical on TYPO3 v12 and v13.

## Capabilities

### New Capabilities

- `typo3-category-types/page-module-category-summary`: an extension that owns a
  page type can have the page module show that page's categories, labelled from
  the registered types and overridable through page TSconfig.
- `academic-programs/page-module-category-summary`: the page module shows the
  categories of a program page.
- `academic-projects/page-module-category-summary`: the page module shows the
  categories of a project page.
- `academic-partners/page-module-category-summary`: the page module shows the
  categories of a partner page.

### Modified Capabilities

None.

## Impact

- `category_types` (`packages/fgtclb/typo3-category-types`): one shared,
  stateless summary renderer and its backend template.
- `academic_programs`, `academic_projects` and `academic_partners`: one event
  listener each, registered with the `event.listener` tag in `Services.yaml`;
  `Configuration/page.tsconfig` loses the `templates.typo3/cms-backend.*` line;
  the `Doktype20/30/40.html` partials are removed.
- A project that overrode one of those partials through the removed key keeps
  seeing nothing, as before, and moves its override to the new key.
- No database schema change, no new dependency.

## Non-goals

- Changing which categories a page carries, or their order. On this branch
  `CategoryRepository::findByGroupAndPageId()` has no `ORDER BY` at all, which
  is ACE-431 and is not touched here.
- A summary for any other page type or for records outside the page module.
- Making the summary editable.

## Source

Backport of ACE-687 and its three siblings from `main`, re-derived from the
file-level backport analysis rather than cherry-picked. The three partials and
their `page.tsconfig` registrations are byte-identical to `main`, so the removal
is a literal copy. The implementation is not: `#[AsEventListener]` does not
exist on TYPO3 v12, so the listeners are registered with the `event.listener`
tag, and `final readonly class` is PHP 8.2 while this branch floors at PHP 8.1,
so the renderer and the listeners are `final class` with promoted
`private readonly` properties.
