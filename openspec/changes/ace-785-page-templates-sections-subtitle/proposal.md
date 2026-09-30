## Why

Partner pages (page type 40 of `academic_partners`) and project pages (page
type 30 of `academic_projects`) render through one template each, without a
layout and without partials. On a site package that renders its frame through
a page layout, both come without header, navigation and footer, and four of the
analysed projects copy the whole template to change one part. Their paths at
the key 100 of `page.10` replace a PAGEVIEW site package's own `paths.100`. The
core field "Subtitle" is editable but never rendered, the project heading
fallback is empty on PAGEVIEW, and the content comes from the global
`styles.content.getContent`, which only the content-load sets define. The
program page solved all of this in `ace-721` and `ace-726`.

## What Changes

- Each template renders the section `Main` of the layout named by
  `plugin.tx_academicpartners.page.layout` or
  `plugin.tx_academicprojects.page.layout`, `Default` unless configured, with
  a fallback layout for a site package without `Default`.
- The section renders five partials below `Partner/Page/` or `Project/Page/`.
- The header renders the page subtitle. The page record comes from `page`
  first and from `data` otherwise, in the template and in the data processors,
  so both page object types work.
- The content is the page object variable `partnerContent` or
  `projectContent`.
- The paths move from the key 100 to 50.
- **BREAKING** The templates no longer render `styles.content.getContent`, and
  the sets `fgtclb/academic-partners-content-load` and
  `fgtclb/academic-projects-content-load` and their static templates are
  removed. A site still naming a set answers with HTTP 500, and
  `academic:upgrade:check` names the replacement.
- **BREAKING** The pages render inside the site layout, the partner heading
  gains a wrapper, and overrides at the key 100 move to 50.

The behaviour is the same on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `academic-partners/partner-page`: layout, header, content and replaceable
  sections of a partner page.
- `academic-projects/project-page`: the same for a project page.
- `academic-base/upgrade-configuration-check`: the two removed sets are named
  with their replacement.

## Impact

- Both extensions: page template, partials, fallback layout, page object
  TypoScript, constant, site setting, data processor, removed content-load
  set and static template, tests, `Documentation/` with two `Breaking-`
  entries each.
- `academic_base`: `ConfigurationChecker::REMOVED_SETS`.
- The `core-13` and `core-14` site configurations, and `docs/`.
- No database change.

## Non-goals

- A subtitle column of the extensions, or migrating a project's own one.
- A link back to the list, which the program page has.
- The `main` content area of a PAGEVIEW page content data processor, which
  `ace-721` rejected for the program page as well.
- The program data processor, which reads `data` first too (ACE-786).
- Backporting to branch `2`, which serves TYPO3 v12.

## Source

Project differences analysis of 2026-09-12 (candidate `listings-22`). Four of
the six analysed projects carry their own code for this. The scope grew to the
full program page pattern on 2026-09-30, as `docs/architecture/page-type-rendering.md`
already announced. Issue ACE-785.
