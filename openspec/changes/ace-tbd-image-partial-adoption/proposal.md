## Why

The list items and page templates of four more academic extensions render
their image with a plain image tag. The projects that restyle them copy the
item partial or the page template only to swap that tag for a responsive
picture. Once academic_base ships the shared image partial (candidate
`cross-cutting-01`), these copies have no reason left.

## What Changes

- The list item and page template images use the shared image partial:
  - academic_partners (`packages/fgtclb/academic-partners`): partner list
    items, partnership list and teaser items, and the partner page template;
  - academic_programs (`packages/fgtclb/academic-programs`): program list
    items and the program page template;
  - academic_projects (`packages/fgtclb/academic-projects`): project list
    items and the project page template;
  - academic_jobs (`packages/fgtclb/academic-jobs`): job list items.
- Presets: `card` for the program, project and job list items, `detail` for
  page templates, and `logo` for every item that shows a partner logo: the
  partner list items and the partnership list and teaser items. `logo` keeps
  partner logos uncropped and passes SVG through.
- The plugin views and page objects of these extensions register the
  academic_base partials.
- **BREAKING** (markup): images change from `<img>` to `<picture>`. This is
  announced as an Important changelog entry per extension.
- The behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-partners/image-rendering`: how partner and partnership images
  render in lists and on the partner page.
- `academic-programs/image-rendering`: how program images render in lists and
  on the program page.
- `academic-projects/image-rendering`: how project images render in lists and
  on the project page.
- `academic-jobs/image-rendering`: how job images render in the job list.

### Modified Capabilities

None.

## Impact

- Nine image calls in list item partials and page templates.
- The plugin TypoScript and the page object TypoScript of the four
  extensions.
- No database, TCA or PHP change; no new dependency beyond academic_base,
  which all four already require.

## Non-goals

- academic_contacts4pages, which renders the persons card partial and is
  covered by candidate `cross-cutting-01`.
- Crop variant definitions (candidate `cross-cutting-03`).
- Backporting to branch `2`.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`cross-cutting-02`). Four of the six analysed projects carry their own code for
this today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-image-partial-adoption` when the issue is filed after
implementation. Depends on the change `ace-tbd-responsive-image-partial`.
