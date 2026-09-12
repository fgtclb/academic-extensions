## Context

See `proposal.md` for the motivation. Verified on `main`:

- `tx_academicprojects_short_description` and `tx_academicprojects_funders`
  are `text` fields with `enableRichtext` (`Configuration/TCA/Overrides/pages.php:77`
  and `:133`).
- They are printed with `f:format.raw` in `Pages/AcademicProject.html:13`
  and `:82`, and in `Partials/Project/Item.html:24`.
- `f:format.html` defaults to `lib.parseFunc_RTE`. When that path is
  undefined, `ContentObjectRenderer::parseFunc()` throws the `LogicException`
  1641989097 (read in the installed 13.4.35).
- `academic_jobs` already renders its rich text description with
  `f:format.html` (`Templates/Job/Show.html:42`), so the extension family has
  the dependency today.
- The page template and list plugin tests load fluid_styled_content, so
  `lib.parseFunc_RTE` exists in the test sites.

## Goals / Non-Goals

**Goals:**

- The three call sites render through the site's `lib.parseFunc_RTE`.

**Non-Goals:**

- A fallback when `lib.parseFunc_RTE` is missing.

## Decisions

### `f:format.html` with its default path

Replace the three `f:format.raw` calls with `f:format.html` and no
`parseFuncTSPath` argument, which is how core and `academic_jobs` render rich
text.

Rejected: `f:transform.html` (plus `f:sanitize.html`). It resolves `t3://`
links without needing TypoScript, but it bypasses the site's `parseFunc`
configuration - link targets, allowed tags, the sanitizer build - that every
other rich text field of the site follows.

Rejected: leaving `f:format.raw` and documenting overrides. It keeps a link
defect as the default, and one project already overrides the page for
exactly this.

### No set dependency on fluid_styled_content

The requirement is documented, not enforced. A site running bootstrap_package
or its own site package defines `lib.parseFunc_RTE` without
fluid_styled_content, and a hard set dependency would force the extension's
content rendering onto it.

### Decided: backport to branch `2` as a change of its own

The fix is backported to branch `2` in a separate change with its own
analysis. The same templates are verified on branch `2`, so projects on
2.3.4 carry the defect until the backport is released.

## Risks / Trade-offs

- [A site without `lib.parseFunc_RTE` now gets exception 1641989097 on project
  pages and lists] → The `Important-` changelog entry states the requirement
  and the exception code; before this change such a site already could not
  resolve the links.
- [parseFunc wraps loose text in `<p>`, depending on the configuration] →
  Named in the changelog entry; content written in the rich text editor
  already carries paragraphs.

## Migration Plan

None.

## Open Questions

None.
