## Why

Every plugin content element renders through `lib.contentElement`. Whether its
`Default` layout renders the header an editor enters depends on the site: the
layout of EXT:fluid_styled_content and of `bk2k/bootstrap-package` do, a site
package may ship a layout without a header section and render headers in its
element templates instead (one analysed project does exactly that).

The job plugins of `academic_jobs` (`packages/fgtclb/academic-jobs`) and the job
list of `academic_bite_jobs` (`packages/fgtclb/academic-bite-jobs`) always
render the header themselves, through the header partial of
EXT:fluid_styled_content. Verified on 2026-09-25 by rendering, on TYPO3 v13 and
v14 alike, and the same templates are on branch `2`:

- With a layout that renders the header and an explicit header layout (1 to
  5), the header and the subheader appear twice.
- With the header layout "Default" the heading shows once, from the layout,
  and the plugin emits an empty `<header></header>` element, because the
  partial falls back to a setting plugin settings do not carry.

The existing header tests assert that the text appears somewhere and see
neither. Found in the review of ACE-728.

## What Changes

- A new setting per extension, "render the content element header in the
  plugin", off by default. Off: the job plugins render no header of their
  own; the header appears once, from the content element layout. On: the
  plugins render it themselves, for sites whose layout leaves it out -
  including the header layout "Default".
- Changelog entries for both extensions: the markup change, and that a site
  whose layout renders no header switches the setting on to keep the job
  headers it has today.

Behaviour is the same on TYPO3 v13 and v14, and on v12 and v13 on branch `2`.

## Capabilities

### New Capabilities

- `academic-jobs/content-element-header`: who renders the header of the job
  plugins, and how often.
- `academic-bite-jobs/content-element-header`: the same for the job list.

### Modified Capabilities

None.

## Impact

- Templates `Job/List.html`, `Job/Show.html`, `Job/New.html`,
  `BiteJobs/List.html`; the TypoScript constants and setup of both
  extensions and the site settings definition of `academic_jobs`; their
  tests and documentation.
- No PHP, TCA or database change. The `record` view variable and the partial
  path of EXT:fluid_styled_content stay.
- Backport to branch `2` (2.4.0): the defect exists there unchanged.
- The same switch is the pattern `ace-tbd-plugin-content-element-header`
  applies to five more extensions; that change was updated with this one.

## Non-goals

- Changing the content element layout or `lib.contentElement`.
- One switch shared by all academic extensions (see design.md).

## Source

Adopted from outside the analysis: found in the review of ACE-728 (pull
request #738) and verified by rendering. No YouTrack issue is filed yet; the
change is renamed to `ace-<NNN>-jobs-header-rendered-twice` when the issue is
filed after implementation.
