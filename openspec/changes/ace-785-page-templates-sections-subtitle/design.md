## Context

`packages/fgtclb/academic-partners/Resources/Private/Pages/AcademicPartner.html`
and `packages/fgtclb/academic-projects/Resources/Private/Pages/AcademicProject.html`
are single templates without layout or partials. Both end with
`<f:cObject typoscriptObjectPath="styles.content.getContent"/>` (`:64`,
`:95`). The project heading is
`{f:if(condition: '{project.projectTitle}', then: '{project.projectTitle}', else: '{data.title}')}`
(`AcademicProject.html:11`). The partner heading is a bare `<h1>` inside the
`d-flex flex-column-reverse` wrapper, next to the media.

`styles.content.getContent` is defined only by the content-load component
sets `fgtclb/academic-partners-content-load` and
`fgtclb/academic-projects-content-load`
(`Configuration/TypoScript/ContentLoad/setup.typoscript`) and their static
templates. `f:cObject` throws when the path is undefined, and the page
template tests include that setup explicitly. The programs set is gone since
`ace-721-program-page-content-without-getcontent`.

The page objects in `Configuration/TypoScript/Page/AcademicPartners.typoscript`
and `AcademicProjects.typoscript` register their paths at `100`:
`paths.100` for PAGEVIEW, `templateRootPaths`, `partialRootPaths` and
`layoutRootPaths` `100` for FLUIDTEMPLATE, where the layout directory does not
exist. On a PAGEVIEW site package at `paths.100` this replaces the package's
own directory on these pages, as `ace-726-program-page-layout-and-sections`
found for program pages.

PAGEVIEW assigns only `site`, `language`, `page` and `settings` besides the
variables and the data processing results (`PageViewContentObject.php`,
TYPO3 13.4 and 14.3), and from TYPO3 14.2 on the content areas as `content`. There is no `data`, so the project heading fallback is
empty under PAGEVIEW. `page` is the page information object, whose
`getPageRecord()` exists on both versions.

The core field `pages.subtitle` sits in the `title` palette of
`cms-core/Configuration/TCA/pages.php` and reaches page types 30 and 40
because both copy the showitem of the default page type
(`Configuration/TCA/Overrides/pages.php` of each extension).

The program page is the reference: `ace-721` introduced the content variable,
`ace-726` the layout setting, the fallback layout, the partials and the key
`50`. `docs/architecture/page-type-rendering.md` describes that pattern and
already reserves `-1758484901` and `-1758484903` for the partner and project
fallback layouts.

## Goals / Non-Goals

**Goals:**

- The same rendering contract on all three academic page types.
- One partial per part, with the same order of parts as today.
- The same output on both page object types.

**Non-Goals:**

- Any change to the program data processor, which is ACE-786.
- A link back to the list, which only the program page has.
- CSS classes beyond the header wrapper and the subtitle.

## Decisions

### A layout named by a setting, with a fallback layout

Each template declares `<f:layout name="{partnerPageLayout}" />` or
`{projectPageLayout}` and renders everything inside `<f:section name="Main">`.
The layout name is the constant and site setting
`plugin.tx_academicpartners.page.layout` or
`plugin.tx_academicprojects.page.layout`, `Default` unless configured, passed
as a `TEXT` variable with `ifEmpty = Default`, because Fluid throws on an empty
layout name. The site setting is declared in the
`settings.definitions.yaml` of the aggregate set, where the other settings of
both extensions already are.

A fallback layout `Default` in `Resources/Private/PageLayoutFallback/Layouts/`
renders the section alone. It is registered at `-1758484901` (partners) and
`-1758484903` (projects) in `paths` and `layoutRootPaths`, below every key a
site uses, so a layout `Default` of the site wins. Its directory is its own
because on PAGEVIEW the entry at `50` also expands to `Layouts/`.

Rejected, as in `ace-726`: no layout. A site package that renders its frame
through a layout then shows these pages without header, navigation and footer,
which is why projects copy the whole template. The earlier objection, a
collision with site layouts called `Default`, is what the fallback answers.

### Paths at the key 50

The four path entries move from `100` to `50`, and `layoutRootPaths.100` is
removed. A PAGEVIEW site package at `paths.100` keeps its layouts and partials
on these pages, a project override between `50` and `100` wins over the
extension, and `50` is still above the `0` and `1` of `bk2k/bootstrap-package`.

### Partials in a `Page/` subfolder, template names unchanged

The section renders `Partner/Page/{Header,Media,Categories,Address,Content}`
and `Project/Page/{Header,Media,Categories,Facts,Content}` with
`arguments="{_all}"`. The `d-flex flex-column-reverse` wrapper stays in the
template around Header and Media.

The header is one element, because in that reversed column several siblings
would be reordered. The project header already has a wrapper `<div>` with the
heading and the short description, and gets the class
`academic-projects-detail__header`. The partner heading gains a wrapper
`<div class="academic-partners-detail__header">`. The subtitle is a
`<p class="academic-{partners,projects}-detail__subtitle">` right below the
heading, the markup of the program header.

Rejected: a wrapper only when the subtitle is filled. It keeps the markup of a
page without subtitle byte for byte, but gives a stylesheet two shapes of the
same header.

### The page record is resolved once in the template

The section sets `pageRecord` to `{page.pageRecord}` and, when that is empty,
to `{data}`. `page` comes first because PAGEVIEW reserves that name, while a
PAGEVIEW site package may assign a `data` of its own. The project heading
fallback and the subtitle read `pageRecord.title` and `pageRecord.subtitle`.

The `partner-data` and `project-data` processors resolve the record in the same
order. Up to ACE-785 they read `data` first, and a PAGEVIEW site package with a
variable `data` of its own made them fail with a type error. The program
processor still reads `data` first, which is left to ACE-786.

Rejected: a data processor that assigns `data` under PAGEVIEW. Site packages
built on PAGEVIEW may already use that name. Rejected: a subtitle on the
partner and project models. The program model has one because its factory
maps the page, the partner and project models do not carry page fields
beyond their own.

### The content is a variable of the page object

Inside the doktype condition, the page object gets `partnerContent` or
`projectContent`, a `CONTENT` object on `tt_content` with
`where = {#colPos}=0` and `orderBy = sorting, uid`, exactly as
`programContent`. `Content.html` renders it with `f:format.raw()`. `CONTENT`
applies the language and workspace overlays itself. Both FLUIDTEMPLATE and
PAGEVIEW assign `variables`.

Rejected: rendering `{content.main.records}` of a PAGEVIEW page content data
processor when it exists. `ace-721` rejected it for the program page, and
preferring it here would give the three page types two content contracts: a
change of the variable, documented for all three, would be ignored on a
PAGEVIEW site with that processor. A site that wants the content area
replaces the `Content` partial, which is one file now.

Rejected: keeping the `f:cObject` as a fallback, or guarding it with a
condition. The path no longer exists in 3.0, and a guard hides that.

### The partner and project content-load sets are removed

For both extensions this change removes `Configuration/Sets/ContentLoad/`,
`Configuration/TypoScript/ContentLoad/`, the dependency in
`Configuration/Sets/Full/config.yaml`, the `ContentLoad` line of
`Configuration/TypoScript/Full/include_static_file.txt` and the
`addStaticFile()` registration in `Configuration/TCA/Overrides/sys_template.php`.
Together with `ace-721`, no academic extension defines
`styles.content.getContent` afterwards.

The tests that pin the set today are inverted, not deleted, as `ace-721` did
for programs: `SiteSetDeliveryTest` asserts that no delivery mechanism assigns
the override and that a site naming the removed set is not delivered,
`StaticRegistrationTest` that the static template is gone, and the
`AcademicPartnersLabelOverrideTest` and `AcademicProjectsLabelOverrideTest`
stop including the removed file.

Both sets are added to `ConfigurationChecker::REMOVED_SETS` of
`academic_base`, so the `unavailable-set` error of `academic:upgrade:check`
says what replaced them.

Rejected: deprecating the sets in 3.x and removing them in 4.0. The
maintainer decided the removal for 3.0.

Guessed layout, a sketch, not a design:

```text
+--------------------------------------------------+
| site header (layout "Default" of the site)       |
+--------------------------------------------------+
| Header:     Title / subtitle                     |
| Media:      [landscape image]                    |
| Categories: Region: Europe | Type: University    |
| Address:    Street 1, 12345 City, Country        |
| Content:    (colPos 0 elements)                  |
+--------------------------------------------------+
| site footer                                      |
+--------------------------------------------------+
```

## Risks / Trade-offs

- [A site layout without a section `Main` renders an empty page] → The layout
  setting and the Breaking changelog entry.
- [A layout named by the setting that the site does not have] → Fails as any
  missing Fluid layout does, and the documentation says so.
- [A project reused or cleared the key 100 of `page.10` for these pages] →
  Breaking changelog entry with the new key.
- [A stylesheet targets the partner `h1` as a direct child of the flex
  column] → The Breaking entry names the wrapper.
- [A site customised `styles.content.getContent` for these pages] → It
  changes `page.10.variables.partnerContent` or `projectContent` instead.
- [A site's full-template override still renders `styles.content.getContent`]
  → It throws once the sets are gone. The Breaking entries name the variable,
  or the site defines the path itself.
- [A site configuration or `sys_template` record still names a removed set or
  static template] → A removed set fails the whole site with HTTP 500, a
  removed static template is skipped without a message. Both are named in the
  Breaking entries, and the set in `academic:upgrade:check`.

## Migration Plan

No data migration. Sites remove the content-load set or static template,
replace a customised `styles.content.getContent` with the page variable,
replace the `f:cObject` call in their own template overrides, and move
overrides of these pages that relied on the key 100 above 50. Rollback is the
code only.

## Open Questions

None.
