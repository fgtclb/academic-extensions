## Context

See `proposal.md` for the motivation. Verified on this branch at `9fffaf6fd`:

- `tx_academicprojects_short_description` and `tx_academicprojects_funders`
  are `text` fields with `enableRichtext`, as on `main`.
- `f:format.raw` prints them in `Pages/AcademicProject.html:13` and `:82`, and
  in `Partials/Project/Item.html:24`. The partial is byte-identical to `main`
  before the fix; the page template differs only in the category block, which
  ACE-673 changed on `main` and which is not on this branch.
- `f:format.html` defaults `parseFuncTSPath` to `lib.parseFunc_RTE` on 12.4.45
  as on v13, and `parseFunc()` sanitizes by default on both.
- **TYPO3 v13** defines `lib.parseFunc` and `lib.parseFunc_RTE` in the default
  TypoScript of `EXT:frontend` since 13.2 (Important #103485), and this branch
  requires `^13.4`.
- **TYPO3 v12** does not: `EXT:frontend/ext_localconf.php` of 12.4.45 adds only
  `styles.content.get` and `tt_content`. `lib.parseFunc_RTE` comes from
  fluid_styled_content, bootstrap_package or a site package. Without one,
  `ContentObjectRenderer::parseFunc()` throws `LogicException` 1641989097.
- `academic_jobs` (`Job/Show.html:42`), `academic_persons`
  (`Profile/Detail.html:207`, `:254`), `academic_persons_edit` and
  `academic_study_plan` already render rich text through `f:format.html` on
  this branch, so the v12 dependency exists in the extension family today.
- There is no list plugin test for `academic_projects` on this branch.
  `FrontendPluginRenderingTrait` is available and the extension's plugin
  TypoScript is identical to `main`.

## Goals / Non-Goals

**Goals:**

- The three call sites render through the site's `lib.parseFunc_RTE` on v12
  and v13.

**Non-Goals:**

- A fallback for a v12 site without `lib.parseFunc_RTE`.

## Decisions

### `f:format.html` with its default path, on both core versions

The same patch as on `main`: the three `f:format.raw` calls become
`f:format.html` without a `parseFuncTSPath` argument.

Rejected: `f:transform.html` (plus `f:sanitize.html`), for the reason given on
`main` - it bypasses the site's `parseFunc` configuration. Rejected as well: a
core version split that keeps `f:format.raw` on v12. It keeps the link defect
on the version where most 2.x installations run, to spare sites without any
content rendering definition, which already cannot render the job detail or
the profile detail of the same extension family.

### The v12 requirement is documented and tested, not enforced

No set or composer dependency on fluid_styled_content: a site running
bootstrap_package or its own site package defines `lib.parseFunc_RTE` without
it. The `Important-` changelog entry names the requirement and the exception
code; a v12-only test pins that the failure is the core exception, and a
v13-only test pins that no configuration is needed there.

## Risks / Trade-offs

- [A v12 site without `lib.parseFunc_RTE` now gets exception 1641989097 on
  project pages and lists] → Named with the exception code in the changelog
  entry and the configuration chapter. Such a site already cannot render
  `academic_jobs` or `academic_persons` detail views, nor any core rich text
  field.
- [parseFunc wraps loose text in `<p>`, depending on the configuration] →
  Named in the changelog entry; content written in the rich text editor
  already carries paragraphs.
- [The page template test runs without the ACE-673 category tests here] →
  Unrelated to this change; ACE-673 is a separate backport.

## Migration Plan

None.

## Open Questions

None.
