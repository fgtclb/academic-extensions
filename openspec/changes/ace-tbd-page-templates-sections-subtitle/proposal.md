## Why

Partner pages (page type 40 of `academic_partners`,
`packages/fgtclb/academic-partners`) and project pages (page type 30 of
`academic_projects`, `packages/fgtclb/academic-projects`) render through one
template each, 59 and 88 lines, without partials. Four of the analysed
projects theme these pages, and each copies the whole file. The core page
field "Subtitle" is editable on both page types, but no template renders it,
so one project added a subtitle column of its own. On a PAGEVIEW page object,
the project heading falls back to a variable that page object does not provide,
and renders empty when the project title is not filled. Both templates render
their content through the global `styles.content.getContent`, which only the
content-load sets define, and those sets are removed in 3.0.

## What Changes

- The template names stay. Their blocks move into partials:
  `Partner/Page/{Header,Media,Categories,Address,Content}` and
  `Project/Page/{Header,Media,Categories,Facts,Content}`. A project overrides
  one section through its own partial path instead of the whole template.
- The header renders the page subtitle when it is filled.
- The page record is resolved for both page object types, FLUIDTEMPLATE and
  PAGEVIEW, so the heading fallback and the subtitle work on both.
- The content section renders the `main` content area when a PAGEVIEW page
  object provides it, and otherwise a content variable scoped to the partner
  or project page type.
- **BREAKING** Neither page template renders `styles.content.getContent` any
  more. The content of the main column comes from `partnerContent` or
  `projectContent` on the page object, for FLUIDTEMPLATE and PAGEVIEW
  alike. A site that customised `styles.content.getContent` for these pages
  customises the page variable instead, and a site template override that
  still renders the global path has to switch to the variable, because the
  content-load sets that defined it are removed in 3.0.
- **BREAKING** The site sets `fgtclb/academic-partners-content-load` and
  `fgtclb/academic-projects-content-load` and the static templates "Academic
  Partners: Content load override" and "Academic Projects: Content load
  override" are removed, and the aggregate sets no longer depend on them.
  Neither extension defines `styles.content.getContent` any more. A site
  configuration that still lists a removed set loses it without an error.
  The programs set is removed by
  `ace-tbd-program-page-content-without-getcontent`.
- The markup order stays the same; the subtitle line is the only addition.

The behaviour is the same on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-partners/partner-page`: what a partner page shows and which of
  its sections an integrator can replace.
- `academic-projects/project-page`: what a project page shows and which of
  its sections an integrator can replace.

### Modified Capabilities

None.

## Impact

- `academic_partners`: `Resources/Private/Pages/AcademicPartner.html` and
  five new partials below `Resources/Private/Partials/Partner/Page/`.
- `academic_projects`: `Resources/Private/Pages/AcademicProject.html` and
  five new partials below `Resources/Private/Partials/Project/Page/`.
- `Configuration/TypoScript/Page/AcademicPartners.typoscript` and
  `AcademicProjects.typoscript`: a page-scoped content variable. No path
  change: both page objects already register `Resources/Private/Partials/`
  for FLUIDTEMPLATE and `Resources/Private/` for PAGEVIEW.
- Removed in both extensions: `Configuration/Sets/ContentLoad/`,
  `Configuration/TypoScript/ContentLoad/`, the dependency in
  `Configuration/Sets/Full/config.yaml`, the `ContentLoad` line of
  `Configuration/TypoScript/Full/include_static_file.txt` and the static
  template registration in `Configuration/TCA/Overrides/sys_template.php`.
- Tests in both extensions: `SiteSetDeliveryTest`, `StaticRegistrationTest`
  and the `Probe.typoscript` fixture assert the absence of the set and the
  static template instead of their delivery.
- Full-template overrides keep working as long as they do not render
  `styles.content.getContent`. No database change.
- Site configurations and `sys_template` records naming a removed set or
  static template have to drop the entry.
- The comment on the content-load sets in the site configuration of the
  `core-13` and `core-14` instances.
- `Breaking-ContentLoadSetRemoved.rst` per extension with the migration,
  next to the `Feature-` entry.

## Non-goals

- A named layout around the templates.
- A subtitle column of the extensions; the core field is used.
- Migrating a project's own subtitle column, which is project work.
- Crop variants on the page media.
- The program page and the programs content-load set, which
  `ace-tbd-program-page-content-without-getcontent` covers.
- Backporting to branch `2`, which serves TYPO3 v12.

Depends on candidates `listings-01` (change
`ace-tbd-page-template-category-block`) and `listings-03` (change
`ace-tbd-project-rte-fields-parsefunc`), which change the same templates.
`ace-tbd-page-template-category-block` carries the same removal of
`styles.content.getContent`; whichever lands first introduces the page
variable, and the other builds on it.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`listings-22`). Four of the six analysed projects carry their own code for
this today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-<slug>` when the issue is filed after implementation.
