## Context

See `proposal.md` for the motivation.
`Resources/Private/Frontend/Default/Templates/AcademicStudyPlan.html` registers
`f:asset.css` and `f:asset.module` unconditionally, as the first two lines of
its `Main` section. The change was written against the template before
ACE-702, where they were lines 6-7 of a template with no layout; they moved
into the section with that change and are otherwise untouched.
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

### The filter template item is rendered `hidden`

Checked rather than assumed: nothing hides it. The template renders one
`<li>` with a placeholder button reading `category-label-placeholder`, the
module clones it per category and empties the list first, and neither the
shipped stylesheet nor the user agent hides that item. A page whose script is
switched off would therefore show it as a filter button with placeholder text.

The item is rendered `<li hidden>` and `buildCategoryFilter()` takes the
attribute off every clone it appends. That also repairs the case this change
did not create - a script that fails to load, or is blocked - and it needs no
stylesheet, which matters because the stylesheet is switchable too.

Rejected: wrapping the whole `<nav>` in the `js` switch. The markup is the
contract an integrator's own script addresses, and the proposal's non-goal
says the markup stays.

## Risks / Trade-offs

- [Differing defaults reset a site setting] → A site that uses the set and
  the static template reads the constants after the site settings; the
  defaults are identical in both files, and the functional test pins them.
- [A boolean site setting is not `true` in TypoScript] →
  `SysTemplateTreeBuilder::addDefaultTypoScriptConstantsFromSite()`
  concatenates the value into a constants line, so `true` arrives as `1` and
  `false` as the empty string. Both read as false and true respectively for
  Fluid's `f:if`, and the delivery test asserts `1`, not `true`.

## Open Questions

None.
