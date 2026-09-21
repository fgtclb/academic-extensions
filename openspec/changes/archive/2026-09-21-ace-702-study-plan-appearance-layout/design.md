## Context

See `proposal.md`. The file level diff against `main` was taken first, as
`docs/workflow/backporting.md` asks.

`Resources/Private/Frontend/Default/Templates/AcademicStudyPlan.html` differs
from the one on `main` in exactly two lines: this branch has no `f:asset.css`
and no `f:asset.module`, because it registers the assets through
`page.includeCSS` and `page.includeJSFooter` in its `setup.typoscript` —
classic assets rather than an ES module, because `f:asset.module` does not
exist on TYPO3 v12. That makes the backported template *smaller* than the one
on `main`, not different. Everything else — the wrapper, the `Header/All`
render, the body — is identical.

`Configuration/TypoScript/ContentElement/setup.typoscript` is the same
`=< lib.contentElement` copy with the same three root paths.

## Goals / Non-Goals

**Goals:**

- The element behaves like every other content element of the site, on TYPO3
  v12 and v13.

**Non-Goals:**

- Changing the study plan markup inside the wrapper.
- Porting the full rendering coverage `main` has for this element.

## Decisions

### Render through the Default layout

The template starts with `<f:layout name="Default"/>` and wraps its markup in
`<f:section name="Main">`; the explicit `Header/All` render is removed, because
the layout renders the header section. The layout name resolves through the
`layoutRootPaths` of `lib.contentElement`; the extension's own
`layoutRootPaths.100` points at a directory that does not exist and therefore
changes nothing.

Verified in the installed TYPO3 **v12.4.45** tree rather than inferred from
v13: `cms-fluid-styled-content/Resources/Private/Layouts/Default.html` is
byte-identical to the v13 one — same frame wrapper with `id="c{data.uid}"` and
its `frame-layout-{data.layout}` class, same space classes, same bare `<a>` in
the "no frame" branch, and the same five sections, four of which fall back to a
partial (`DropIn/Before/All`, `Header/All`, `Footer/All`, `DropIn/After/All`)
while `Main` has none.

Rejected: rebuilding the frame markup inside the template. It duplicates what
the site's layout does, and drifts from a bootstrap_package site, whose layout
renders a different frame.

### Build the rendering test rather than port it

`main` has `AcademicStudyPlanContentElementTest` with the full coverage of the
element, added by a change that was never backported. This branch has nothing
under `Tests/Functional/ContentElement/`. The test class added here is
deliberately small and covers the Appearance tab only, the way
`AcademicContacts4PagesListPluginTest` on this branch is small for the same
reason. `FrontendPluginRenderingTrait` exists here and 23 test files already use
it, four of them abstract delivery cases, so the harness is available.

## Risks / Trade-offs

- [CSS addressed the header inside `.academic-study-plan`] → The header now
  precedes the wrapper; the `Important` changelog shows the new nesting and
  carries the migration.
- [A site whose `lib.contentElement` has no `Default` layout] → Such a site
  cannot render any core content element either; no fallback is added. A
  missing layout is fatal, but so was the missing partial before it: Fluid
  treats `optional` as a property of a section, never of a partial.

## Open Questions

None.

## Verification of the unchanged interaction

The delta spec requires that the filter, the semester toggles and the module
dialogs keep working. On `main` a `testJs` test asserts that against the new
markup. This branch has no JavaScript suite, so it is established differently
and the difference is worth stating: the module scopes every lookup to
`.academic-study-plan` (`academic-study-plan.ts:59-60, 76-77, 91, 116, 236`),
the one exception being the module dialogs, which it looks up from the
document. Neither a wrapper *around* that container nor a `<header>` moved
*out* of it can reach either. The functional test asserts that the markup
inside the container is unchanged.
