## Context

`packages/fgtclb/academic-partners/Resources/Private/Pages/AcademicPartner.html`
and `packages/fgtclb/academic-projects/Resources/Private/Pages/AcademicProject.html`
are single templates without layout or partials. Both end with
`<f:cObject typoscriptObjectPath="styles.content.getContent"/>` (`:56`,
`:87`). The project heading is
`{f:if(condition: '{project.projectTitle}', then: '{project.projectTitle}', else: '{data.title}')}`
(`AcademicProject.html:11`).

`styles.content.getContent` is defined only by the content-load component
sets `fgtclb/academic-partners-content-load` and
`fgtclb/academic-projects-content-load`
(`Configuration/TypoScript/ContentLoad/setup.typoscript`, `getContent <
styles.content.get` restricted to colPos 0), and by their static template
counterparts. `f:cObject` throws when the path is undefined, and the page
template tests include that setup explicitly
(`AcademicPartnerPageTemplateTest.php:63-65`). The maintainer decided that
all three content-load sets (partners, programs, projects) are removed in
3.0 as a breaking change, so from 3.0 on nothing defines that path.

The page objects in `Configuration/TypoScript/Page/AcademicPartners.typoscript`
and `AcademicProjects.typoscript` set `templateName`, register
`paths.100 = EXT:<ext>/Resources/Private/` for PAGEVIEW and
`templateRootPaths`/`partialRootPaths`/`layoutRootPaths` `100` for
FLUIDTEMPLATE, and add the `partner-data`/`project-data` and `files` data
processors.

PAGEVIEW assigns only `site`, `language` and `page` besides the data
processing results (`PageViewContentObject.php:145-147` on TYPO3 13.4.34 and
14.3.6). There is no `data`, so the heading fallback is empty under PAGEVIEW.
`page` is the page information object, whose `getPageRecord()` exists on both
versions (`PageInformation.php:131`). The shipped backend layouts give colPos
0 the identifier `main` (`Configuration/TSconfig/BackendLayouts/*.tsconfig:16`),
so a site whose PAGEVIEW adds the page content data processor gets
`content.main.records`.

The core field `pages.subtitle` (`cms-core/Configuration/TCA/pages.php:238`,
title palette `:892`) reaches page types 30 and 40 through
`TcaManipulator::addToPageTypesGeneralTab()`. The existing
`Tests/Functional/Pages/*PageTemplateTest.php` render a FLUIDTEMPLATE page
object through a fixture site package.

## Goals / Non-Goals

**Goals:**

- One partial per section, with the same markup order as today.
- The same output on both page object types.

**Non-Goals:**

- New CSS classes beyond one for the subtitle.
- Any change to the data processors.

## Decisions

### Partials in a `Page/` subfolder, template names unchanged

The templates become a sequence of `f:render` calls on
`Partner/Page/{Header,Media,Categories,Address,Content}` and
`Project/Page/{Header,Media,Categories,Facts,Content}`. The existing
`d-flex flex-column-reverse` wrapper stays in the template around Header and
Media, so the markup order does not change. The project short description
stays inside the Header partial, where it sits today.

Rejected: a named layout around the templates. It helps only projects that
ship a layout of the same name, and it collides with site layouts called
`Default`. Rejected: a subtitle column of the extensions, which would split
the data from the core field an editor already sees.

### The page record is resolved once in the template

The template sets `pageRecord` to `{data}` and, when that is empty, to
`{page.pageRecord}`, and passes it to the partials. The heading fallback and
the subtitle read `pageRecord.title` and `pageRecord.subtitle`.

Rejected: a data processor that assigns `data` under PAGEVIEW. Site packages
built on PAGEVIEW may already use that name, and the extension would redefine
what the PAGEVIEW documentation promises.

### The content section prefers the content area

`Content.html` renders each of `{content.main.records}` with
`<f:cObject typoscriptObjectPath="{record.mainType}" data="{record}" table="{record.mainType}"/>`
when the variable exists, and the page-scoped content variable of the next
decision otherwise. The content area form is the one documented for v13; a
test proves it on v14 as well.

### Decided: the page templates no longer render `styles.content.getContent`

Neither page template nor any of its partials calls
`styles.content.getContent` any more. Inside the doktype condition of
`AcademicPartners.typoscript` and `AcademicProjects.typoscript`, the page
object gets a variable of its own, as the program page does
(`ace-tbd-program-page-content-without-getcontent`):

```typoscript
variables {
  partnerContent = CONTENT
  partnerContent {
    table = tt_content
    select {
      orderBy = sorting, uid
      where = {#colPos}=0
    }
  }
}
```

`projectContent` for projects, with the same body. `Content.html` renders it
with `{partnerContent -> f:format.raw()}` (`projectContent` respectively).
`CONTENT` applies the language and workspace overlays itself, and
`sorting, uid` follows the ordering rule for manually sortable tables. Both
FLUIDTEMPLATE and PAGEVIEW assign `variables`.

This is breaking and is marked `[!!!]`: all three content-load sets and
with them the only definition of `styles.content.getContent` are removed in
3.0, so a template that still renders the path throws. A site that
customised `styles.content.getContent` for these pages (for example with
`slide`) moves the customisation to `page.10.variables.partnerContent` or
`projectContent`. `ace-tbd-page-template-category-block` changes the same
templates; whichever of the two lands first introduces the variable, and the
other builds on it. The removal of the sets belongs to this change, see the
next decision.

Rejected: keeping the cObject as a fallback. The path it renders no longer
exists in 3.0. Rejected: guarding the `f:cObject` with a condition, which
renders an empty page body instead of the content and hides the missing
definition. Rejected: a data processor, which adds PHP for what one
`CONTENT` object does.

### Decided: this change removes the partner and project content-load sets

For `academic_partners` and `academic_projects` alike, this change removes
`Configuration/Sets/ContentLoad/` and `Configuration/TypoScript/ContentLoad/`,
the dependency on `fgtclb/academic-partners-content-load` or
`fgtclb/academic-projects-content-load` in `Configuration/Sets/Full/config.yaml`,
the `ContentLoad` line of `Configuration/TypoScript/Full/include_static_file.txt`
and the `addStaticFile()` registration "Content load override" in
`Configuration/TCA/Overrides/sys_template.php`. The programs set is removed by
`ace-tbd-program-page-content-without-getcontent`; together the two changes
remove all three sets, and no academic extension defines
`styles.content.getContent` afterwards. Marked `[!!!]`, with
`Breaking-ContentLoadSetRemoved.rst` in both extensions.

Verified on `main`, the removal touches per extension: `sys_template.php`
(partners `:43`, projects `:31`), `Configuration/TypoScript/Full/include_static_file.txt`
(partners `:5`, projects `:3`; the `Full` set folder holds only
`config.yaml`), and three test files that pin the set today:
`Tests/Functional/SiteSet/SiteSetDeliveryTest.php` (`CONTENT_LOAD_SET`,
`contentLoadOverrideIsDeliveredByItsOwnSetOnly()` and the set list that
names it), `Tests/Functional/Tca/StaticRegistrationTest.php` (the
"content load override" case) and the `contentLoad` probe line of
`Tests/Functional/SiteSet/Fixtures/TypoScript/Probe.typoscript`. Those tests
are inverted, not deleted: they assert the absence of the set and the
static template.

The removal and the template switch land in one commit. Removed alone, every
partner and project page throws; switched alone, the sets stay a site wide
redefinition of a global object path that nothing in the extensions needs.

A site configuration that still lists a removed set loses it without an
error on v13 and v14, as for the programs set; the `Breaking-` entries say
so, because such a site sees no hint at all. The same holds for a
`sys_template` record that includes the removed static template.

Rejected: deprecating the sets in 3.x and removing them in 4.0. The
maintainer decided the removal for 3.0, and a deprecated set would keep
redefining `styles.content.getContent` for every page of a site for another
major version.

Guessed layout — a sketch, not a design:

```text
+--------------------------------------------------+
| Header:     Title / subtitle                     |
| Media:      [landscape image]                    |
| Categories: Region: Europe | Type: University    |
| Address:    Street 1, 12345 City, Country        |
| Content:    (colPos 0 elements)                  |
+--------------------------------------------------+
```

## Risks / Trade-offs

- [A project already ships partials of the same names above key `100`] →
  Unlikely with the `Page/` subfolder; the changelog entries list the names.
- [The content area rendering differs between v13 and v14] → The PAGEVIEW
  test runs on both; a difference is a switch in the partial, not a split.
- [`listings-01` and `listings-03` change the same templates] → This change
  lands after them and moves their result into the partials.
- [A site customised `styles.content.getContent` for partner or project
  pages] → It overrides `page.10.variables.partnerContent` or
  `projectContent` instead; the `Breaking-` entries show how.
- [A site's full-template override still renders
  `styles.content.getContent`] → It throws once the content-load sets are
  gone; the `Breaking-` entries name the variable to render instead, or a
  site defines the path itself.

- [A site configuration or `sys_template` record still names a removed set
  or static template] → The entry is dropped without an error; the
  `Breaking-` entries tell the integrator to remove it.

## Migration Plan

No data migration. Sites remove the content-load set or static template from
their site configuration and `sys_template` records, replace a customised
`styles.content.getContent` with the page variable, and replace the
`f:cObject` call in their own template overrides, or define
`styles.content.getContent` in their site package when a site template keeps
rendering it. Rollback is the code only.

## Open Questions

None.
