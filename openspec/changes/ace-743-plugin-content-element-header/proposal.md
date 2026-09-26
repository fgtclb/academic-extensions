## Why

Two of the analysed projects copy plugin templates for no other reason than to
add the content element header. Their site package ships a content element
layout without a header section and renders headers in its element templates,
so a plugin, which renders through that layout, shows no header at all.

Updated 2026-09-25, together with `ace-729-jobs-header-rendered-twice`: this
change used to say that every plugin except jobs, bite jobs and the study plan
"ignores the header an editor enters", and planned to add the header partial
to thirteen templates unconditionally. That premise is false on a site with
the content element layout of EXT:fluid_styled_content or
`bk2k/bootstrap-package`: the layout renders the header around every plugin,
and a template that renders it too shows it twice - which is what the jobs
templates do today. The plugins need the header only where the site's layout
leaves it out, so it becomes a switch.

## What Changes

- The plugins of five extensions gain the switch the jobs header fix
  introduces, "render the content element header in the plugin", one per
  extension and off by default:
  - academic_persons (`packages/fgtclb/academic-persons`): profile list,
    list and detail, card, detail, selected profiles, selected contracts;
  - academic_partners (`packages/fgtclb/academic-partners`): partner list,
    map, partnerships list and teaser;
  - academic_programs (`packages/fgtclb/academic-programs`): program list,
    program details and program finder;
  - academic_projects (`packages/fgtclb/academic-projects`): project list;
  - academic_contacts4pages (`packages/fgtclb/academic-contact4pages`):
    contacts list.
- Off, nothing changes: the header renders once, from the content element
  layout. On, the plugin renders the header and subheader above its output,
  with the header partial of EXT:fluid_styled_content, for every header
  layout except "Hidden".
- A `Feature-` changelog entry per extension; no visible change without the
  switch.
- TYPO3 v14's header partial needs the content record, which the controllers
  provide. TYPO3 v13 reads the content element data. The visible result is the
  same on both.

## Capabilities

### New Capabilities

- `academic-persons/plugin-content-element-header`: who renders the header
  of the persons plugins.
- `academic-partners/plugin-content-element-header`: the same for partners
  plugins.
- `academic-programs/plugin-content-element-header`: the same for programs
  plugins.
- `academic-projects/plugin-content-element-header`: the same for the
  projects plugin.
- `academic-contact4pages/plugin-content-element-header`: the same for the
  contacts plugin.

### Modified Capabilities

None.

## Impact

- Fourteen templates and five controllers in five extensions (the contacts
  controller already assigns `record`, ACE-728).
- The plugin TypoScript (constant, setting, header partial path, default
  header type) of the five extensions, and the site settings definitions of
  persons, partners, programs and projects - contacts4pages declares none.
- No database or TCA change.

## Non-goals

- A switch shared by all extensions (see the jobs change).
- The academic_persons_edit editing plugin.
- Rich-text headers and the header palette of projects.
- Backporting to branch `2`.

Depends on `ace-729-jobs-header-rendered-twice`, which introduces the switch
and its documentation.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`cross-cutting-04`), corrected on 2026-09-25 as described above. Three of the
six analysed projects carry their own code for this today. Filed as ACE-743
after the implementation.
