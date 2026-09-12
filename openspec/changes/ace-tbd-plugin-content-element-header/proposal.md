## Why

Only five of twenty frontend plugin templates render the header of their
content element: jobs, bite jobs and the study plan. Every other academic
plugin ignores the header an editor enters, and two of the analysed projects
copy plugin templates for no other reason than to add it.

## What Changes

- The plugins of five extensions render the content element header and
  subheader above their output, with the standard header partial of
  EXT:fluid_styled_content, as the jobs plugins do:
  - academic_persons (`packages/fgtclb/academic-persons`): profile list,
    card, detail, selected profiles, selected contracts;
  - academic_partners (`packages/fgtclb/academic-partners`): partner list,
    map, partnerships list and teaser;
  - academic_programs (`packages/fgtclb/academic-programs`): program list and
    program details;
  - academic_projects (`packages/fgtclb/academic-projects`): project list;
  - academic_contacts4pages (`packages/fgtclb/academic-contact4pages`):
    contacts list.
- Editors hide it with the header layout "Hidden".
- The role headings of the contacts list and the partnerships list and teaser
  keep the header layout but no longer repeat the subheader.
- The five extensions require `typo3/cms-fluid-styled-content`.
- **BREAKING** (markup): headers appear where none rendered before, and a
  project that renders its own header outside the plugin template shows two.
  This is announced as a Breaking changelog entry per extension, with the
  markup before and after and the migration: remove the project's own header,
  or hide the content element header with the header layout "Hidden".
- TYPO3 v14's header partial needs the content record, which the controllers
  provide. TYPO3 v13 reads the content element data. The visible result is the
  same on both.

## Capabilities

### New Capabilities

- `academic-persons/plugin-content-element-header`: the header of persons
  plugins.
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

- Thirteen templates and seven controllers in five extensions.
- The plugin TypoScript of the five extensions (header partial path), their
  `composer.json` and `ext_emconf.php`.
- No database or TCA change.

## Non-goals

- A switch to turn the header off other than the header layout.
- The academic_persons_edit editing plugin.
- Rich-text headers and the header palette of projects.
- Backporting to branch `2`.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`cross-cutting-04`). Three of the six analysed projects carry their own code
for this today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-plugin-content-element-header` when the issue is filed after
implementation.
