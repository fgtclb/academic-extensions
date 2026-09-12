## Context

See `proposal.md` for the defect.
`Resources/Private/Frontend/Default/Templates/AcademicStudyPlan.html` has no
`f:layout` and renders `Header/All` itself inside the
`.academic-study-plan` wrapper. `tt_content.academic_study_plan` is a copy of
`lib.contentElement`, and `Configuration/TCA/Overrides/tt_content.php` offers
the `frames` palette on the Appearance tab.

The frame wrapper (`id="c{data.uid}"`, `frame-{frame_class}`,
`frame-space-before-…`, `frame-space-after-…`) and the header are rendered by
the content element layout `Default`. In fluid_styled_content that is
`Layouts/Default.html` on v13.4.35 and `Layouts/Default.fluid.html` on
v14.3.6, both with the same frame markup (verified in both installed trees).
The extension does not require fluid_styled_content: whichever package
provides `lib.contentElement`, fluid_styled_content or a site package such as
bootstrap_package, also provides its `Default` layout.

## Goals / Non-Goals

**Goals:**

- The element behaves like every other content element of the site.

**Non-Goals:**

- Changing the study plan markup inside the wrapper.

## Decisions

### Render through the Default layout

The template starts with `<f:layout name="Default"/>` and wraps its markup in
`<f:section name="Main">`; the explicit `Header/All` render is removed,
because the layout renders the header section. The `f:asset` lines move into
the section. The layout name resolves through the `layoutRootPaths` of
`lib.contentElement`; the extension's own `layoutRootPaths.100` points at a
directory that does not exist and therefore changes nothing.

Rejected: rebuilding the frame markup inside the template. It duplicates what
the site's layout does, and drifts from a bootstrap_package site, whose layout
renders a different frame.

## Risks / Trade-offs

- [CSS addressed the header inside `.academic-study-plan`] → The header now
  precedes the wrapper; the `Important` changelog shows the new nesting.
- [A site whose `lib.contentElement` has no `Default` layout] → Such a site
  cannot render any core content element either; no fallback is added.

## Open Questions

None.
