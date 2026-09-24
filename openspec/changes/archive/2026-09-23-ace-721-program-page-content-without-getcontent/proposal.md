## Why

The page type "Academic program" of `academic_programs`
(`packages/fgtclb/academic-programs`) renders its page content through the
global object `styles.content.getContent`. Only the opt-in site set
`fgtclb/academic-programs-content-load` defines it, so a site that takes the
component sets without that set gets an exception on every program page. Up
to 2.3 the override was always included; the exception is a regression of the
ACE-458 set split, which `main` and branch `2` both carry.

## What Changes

- A program page renders the content of its main column without the
  content-load set, through an object scoped to the program page type.
- **BREAKING** The site set `fgtclb/academic-programs-content-load` and the
  static template "Academic Programs: Content load override" are removed, and
  the aggregate set `fgtclb/academic-programs` no longer depends on it.
  `academic_programs` no longer defines `styles.content.getContent`; a site
  template that renders it has to switch to the program page variable or
  define the object in its own site package.
- A site that customised `styles.content.getContent` for program pages (for
  example sliding content) now customises the program page variable instead.
- A site that still names the removed set answers every page with HTTP 500
  (core behaviour). `academic:upgrade:check` of `academic_base` reports such a
  dependency, and a site package set that depends on it, as the new error
  finding `unavailable-set`.

The behaviour is identical on TYPO3 v13 and v14, for site packages with a
`FLUIDTEMPLATE` and with a `PAGEVIEW` page object.

## Capabilities

### New Capabilities

- `academic-programs/program-page-content`: how a program page renders the
  content elements of its main column, independent of optional site sets.

### Modified Capabilities

- `academic-base/upgrade-configuration-check`: a site dependency on an academic
  set TYPO3 cannot provide is reported as an error.

## Impact

- `academic_programs`: the page TypoScript of the program page type, the page
  template `Resources/Private/Pages/AcademicProgram.html`, the removed
  `Configuration/Sets/ContentLoad/` and `Configuration/TypoScript/ContentLoad/`,
  the aggregate set and the static template registration.
- Site packages overriding `AcademicProgram.html` that still render
  `styles.content.getContent` throw until they switch to the new variable or
  define the object themselves.
- Site configurations and `sys_template` records naming the removed set or
  static template have to drop the entry. A removed set fails every page of
  the site with HTTP 500 (core behaviour); a removed static template is
  skipped without a message.
- `academic_base`: the configuration checker of `academic:upgrade:check`, its
  help text, documentation and a Feature changelog entry.
- The comment on the content-load sets in the site configuration of the
  `core-13` and `core-14` instances.
- No database schema or dependency change.
- The program page layout change `ace-726-program-page-layout-and-sections`
  builds on this one.

## Non-goals

- The same call in the page templates of `academic_partners`
  (`packages/fgtclb/academic-partners`) and `academic_projects`
  (`packages/fgtclb/academic-projects`), and the removal of their
  content-load sets; `ace-673-page-template-category-block` and
  `ace-tbd-page-templates-sections-subtitle` do that.
- Rendering several columns or a backend layout aware content area.
- Removing the set on branch `2`. Branch `2` (2.4.x-dev) has the same split
  and the same exception, so it gets the non-breaking part - the variable and
  the template switch, set and static template kept - as a change of its own.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`programs-studyplan-02`). Two of the six analysed projects carry their own
code for this today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-program-page-content-without-getcontent` when the issue is filed
after implementation.
