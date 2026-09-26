## Context

Verified on `main` (`a4a4ff0a9`) and `origin/2` on 2026-09-25:

- `academic-jobs/Resources/Private/Templates/Job/List.html:9`, `Job/Show.html:9`,
  `Job/New.html:16` and `academic-bite-jobs/Resources/Private/Templates/BiteJobs/List.html:9`
  render `<f:render partial="Header/All" arguments="{_all}" />` as the first
  line inside their wrapper `div`. Branch `2` has the same line in the same
  four templates: the three job templates got it with `09b001569`, the bite
  jobs list with `fa987f046`, both before the branches split and first
  released in 2.1.0.
- The plugins are registered as content elements, so each CType is
  `tt_content.<CType> =< lib.contentElement` with the `Generic` template. Its
  `Default` layout renders the `Header` section - `Header/All` - around the
  plugin output (`docs/architecture/content-element-rendering.md`, "What the
  `Default` layout does"); so does the layout of `bk2k/bootstrap-package`.
- A site package can ship its own `Default` layout without a `Header` section
  and render headers in its element templates. One analysed project does
  (its content element layout renders the frame and `Main` only); it copies
  plugin templates to add `Header/All` - the evidence behind
  `ace-743-plugin-content-element-header`.
- Probe (functional tests, header "Open positions", subheader set, FSC
  layout): header layout "Default" - one heading and one subheader from the
  layout, plus an empty `<header></header>` from the plugin template; header
  layout 2 - heading and subheader twice. Identical on TYPO3 v13.4.35 and
  v14.3; jobs list, new job form and bite jobs list alike. The empty element
  comes from `Header/Header` falling back to `settings.defaultHeaderType`,
  which the plugin settings do not carry.
- The four header tests use `assertStringContainsString` and see neither.
- `academic_base` delivers no TypoScript today (its sets only group the
  CTypes). `academic_jobs` declares site settings in its `Full` set;
  `academic_bite_jobs` has constants only.

## Goals / Non-Goals

**Goals:**

- The header renders once on a site whose layout renders it, with every
  header layout, without configuration.
- A site whose layout leaves the header out keeps a job header, by one
  setting.

**Non-Goals:**

- Any change to the layout, `lib.contentElement` or other plugins.

## Decisions

### A switch per extension, off by default

`plugin.tx_academicjobs.settings.renderContentElementHeader` and
`plugin.tx_academicbitejobs.settings.renderContentElementHeader`, mapped from a
constant of the same extension (`plugin.tx_academicjobs.renderContentElementHeader`,
`plugin.tx_academicbitejobs.renderContentElementHeader`, default `0`);
`academic_jobs` also declares it as a site setting next to its other settings.
The templates render `Header/All` only while it is on.

Off is the default because the core and bootstrap package layouts render the
header: that is the configuration where the double header happens today.

Rejected: removing the header from the templates without a switch. A site
whose layout leaves the header out would lose the job header it has today,
with nothing to turn it back on but a template copy - the copy the projects
want to get rid of.

Rejected: one switch in `academic_base` for all extensions. It delivers no
TypoScript and no settings on either branch, so one flag would add a component
and a set there; the per-extension key follows the existing settings blocks
and costs a site one line per extension.

Rejected: giving these CTypes a layout without a `Header` section. It forks
the core layout and replaces a site package's own layout.

### The header layout "Default" while switched on

With the switch on, `Header/All` must not emit an empty element for the header
layout "Default". The plugin setup maps
`settings.defaultHeaderType = {$styles.content.defaultHeaderType}`, the
constant `lib.contentElement` uses.

Probed where EXT:fluid_styled_content's constants are not included: the
constant stays undefined, the setting carries the literal reference, and
"Default" renders an empty `<header>` in the plugin - and no heading in the
elements of EXT:fluid_styled_content either, whose layout reads the same
constant. Documented, not guarded: TypoScript has no fallback for an undefined
constant, and a constant of our own with a literal default would stop following
the one a site sets for all its other elements. A site without
EXT:fluid_styled_content's TypoScript sets the plugin setting itself; the
configuration chapters say so.

### Keep `record`, the partial path and the requirement

The controllers keep assigning `record` (ACE-270), the plugin TypoScript keeps
the partial path of EXT:fluid_styled_content, and `academic_jobs` keeps
requiring it: the switch needs all three on TYPO3 v14.

### Changelog placement

An `Important-` entry per extension in `Documentation/Changelog/2.4/`, on
`main` and on branch `2` alike, since the fix ships with 2.4.0 first (same
decision as ACE-728). It names the switch for sites whose layout renders no
header, and the `Header/All` line a project copy of a template should drop.

### `ace-743-plugin-content-element-header` follows this pattern

That change planned to add `Header/All` unconditionally to thirteen templates
on the premise that those plugins "ignore the header an editor enters". On a
site with the core or bootstrap package layout they do not - the layout renders
it - so the plan would have created this defect in five more extensions. It
was updated in the same commit as this proposal: same switch, per extension,
off by default. It depends on this change.

### Order with other active jobs changes

`ace-tbd-jobs-list-pagination` and `ace-tbd-jobs-form-flag-fields` touch the
same templates; the change is a wrapping condition per template, so whichever
lands second rebases trivially.

### Tests count headings

The header tests count the headings reading the header and the subheader, and
the `header` elements inside the plugin wrapper, in the DOM. The helper is a
trait of the testing helper package, `ContentElementHeaderAssertionTrait`,
because `ace-743-plugin-content-element-header` needs the same assertions in
five more extensions.

### Verification

Against the unchanged templates and TypoScript, on TYPO3 v13 and v14 alike,
the new tests fail in the same three cases per plugin: the header layout 2
renders the header twice, "Default" leaves an empty `<header>` inside the
plugin, and the switched on "Default" case renders no heading because
`defaultHeaderType` is not mapped. "Hidden" passes unchanged, with the switch
off and on. Removing only the `defaultHeaderType` mapping from the finished
change turns exactly the switched on "Default" cases red (v13); removing the
`record` assignment turns the switched on cases red on v14.

## Risks / Trade-offs

- [A site whose layout renders no header loses the job headers on update] ->
  It switches the setting on; the changelog entries of both extensions lead
  with it.
- [A site that switches it on while its layout does render the header] ->
  Double header again; the documentation of the setting says when to use it.

## Open Questions

None.
