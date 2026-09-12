## Context

See `proposal.md` for the motivation.
`Resources/Private/Frontend/Default/Templates/AcademicStudyPlan.html:6-7`
registers `f:asset.css` and `f:asset.module` unconditionally.
`tt_content.academic_study_plan` is a copy of `lib.contentElement`, a
`FLUIDTEMPLATE`, so its `settings.` reach the template as `{settings}`. The
set `fgtclb/academic-study-plan-content-element`
(`Configuration/Sets/ContentElement/`) has no settings definitions yet, and
the constants file already holds `plugin.tx_academicstudyplan.view.*`.

## Goals / Non-Goals

**Goals:**

- One name per switch, valid for site-set and static-template installations.

**Non-Goals:**

- Making the markup work without the upstream module; an integrator switching
  the script off brings their own.

## Decisions

### Setting identifiers equal the TypoScript constants

The settings are named `plugin.tx_academicstudyplan.assets.css` and
`plugin.tx_academicstudyplan.assets.js`, type `bool`, default `true`.
`constants.typoscript` declares the same paths with the same defaults, and
`setup.typoscript` maps them:
`tt_content.academic_study_plan.settings.assets.css =
{$plugin.tx_academicstudyplan.assets.css}`, likewise for `js`. The template
wraps each `f:asset` in an `f:if` on `{settings.assets.css}` or
`{settings.assets.js}`.

This is the shape `academic_jobs` uses (its settings definitions repeat the
constant paths, and its comment explains why the defaults must agree in both
files). The candidate named the settings `academicStudyPlan.assets.*`;
rejected, because a static-template installation would then need a second
name for the same switch.

### Declare them on the content element set

The definitions sit in `Configuration/Sets/ContentElement/`, not on the
aggregate. The element is the only component of the extension; the aggregate
`fgtclb/academic-study-plan` and the alias `fgtclb/academic-study-plan-default`
depend on it, so every site that renders the element can set them.

Rejected: going back to `page.includeCSS`/`page.includeJSFooter`, which
loaded the files on every page and was removed on purpose.

## Risks / Trade-offs

- [The filter template item is visible without the module] → The template
  renders a placeholder button (`category-label-placeholder`) that the module
  clones and removes. The implementation checks whether it stays hidden
  without the module; if not, the template marks it `hidden` and the module
  un-hides the clones.
- [Differing defaults reset a site setting] → A site that uses the set and
  the static template reads the constants after the site settings; the
  defaults are identical in both files, and the functional test pins them.

## Open Questions

None.
