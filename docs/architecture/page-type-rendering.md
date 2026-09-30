# Page type rendering

`academic_partners`, `academic_programs` and `academic_projects` each register
a page type (doktype 40, 20 and 30) and ship a page template for it. The page
object that renders it, `page.10`, belongs to the site package, not to the
extension: the extension refines it inside a
`[page && traverse(page, "doktype") == NN]` condition, and everything it
writes there is a key in someone else's array. This page is about writing those
keys so that the site package keeps working on the extension's pages. The
integrator side is in each extension's `Documentation/Configuration/`.

All three page types follow it. The program page came first (ACE-450 set the
template name, ACE-721 the content variable, ACE-726 the rest), and ACE-785
brought the partner and project pages in line.

## Two shapes of page object

A site package renders pages through `FLUIDTEMPLATE` or through `PAGEVIEW`, and
the two read different properties:

| Property      | `FLUIDTEMPLATE`                                            | `PAGEVIEW`                                                             |
|---------------|------------------------------------------------------------|------------------------------------------------------------------------|
| Template name | `templateName`                                             | the backend layout without `pagets__`, below `Pages/`                  |
| Root paths    | `templateRootPaths`, `partialRootPaths`, `layoutRootPaths` | `paths` only, each entry expanded to `Pages/`, `Partials/`, `Layouts/` |
| `settings`    | `settings` of the object                                   | the settings tree of the site; the object's `settings` are ignored     |
| `variables`   | yes                                                        | yes                                                                    |
| `data`        | the page record                                            | not assigned                                                           |
| `page`        | not assigned                                               | the page information object, `{page.pageRecord}` is the page record    |

So an extension registers every path twice, once per shape, and passes values
to its template through `variables` — the one property both read the same way.
A value through `settings` would arrive at a different path in each shape
(`PageViewContentObject::render()`). `dataProcessing` is read by both as well,
so a value a processor needs goes in as an option of that processor: the
program facts take their field list as `factsFields` of `program-data`, see
[Program facts](program-facts.md). A value the template needs goes the same
way: `partner-data` takes the map settings as its option `map` and hands them
to the partner page as `{mapSettings}`, for a site package template that renders
the map partial for the partner of the page.

A template that needs a field of the page therefore resolves the record
itself. The partner and project page templates set `pageRecord` to
`{page.pageRecord}`, and to `{data}` when that is empty, and hand it to their
partials. `page` comes first because `PAGEVIEW` reserves that name, while a
`PAGEVIEW` site package may assign a `data` of its own. The `partner-data` and
`project-data` processors resolve the record in the same order. Up to ACE-785
they read `data` first, and such a site package made them fail with a type
error. `program-data` still reads `data` first (ACE-786).
The project heading falls back to `pageRecord.title`, and both headers render
`pageRecord.subtitle`, the core field. Before ACE-785 the project heading read
`{data.title}` and stayed empty on `PAGEVIEW`. Assigning `data` through a data
processor instead was rejected: a `PAGEVIEW` site package may use that name for
something else.

## The content variable

None of the three page templates renders a global object path. Each page object
carries its content as a variable inside its doktype condition,
`programContent`, `partnerContent` and `projectContent`: a `CONTENT` object on
`tt_content`, colPos 0, ordered by `sorting, uid` as
[Database queries](database-queries.md) asks for a manually sortable table.
`CONTENT` applies the language and workspace overlays itself, and both shapes
of page object assign `variables`. The template renders it with
`f:format.raw()` in its `Content` partial.

Before 3.0 the templates rendered `styles.content.getContent`, which only the
content-load set of each extension defined, for every page of the site. The
three sets are removed, and `academic:upgrade:check` names them as removed sets
when a site still depends on one.

The `main` content area of a `PAGEVIEW` page content data processor was
rejected twice, by ACE-721 and ACE-785: preferring it where it exists would give
the page types two content contracts, and a change of the variable would be
ignored on such a site. A site that wants it overrides the `Content` partial.

## Path keys

Fluid takes a file from the path with the **highest** key that has it. Three
kinds of key are in use on each of the three page types:

| Key                    | What                                                                               |
|------------------------|------------------------------------------------------------------------------------|
| `50`                   | `Pages/` and `Partials/` of the extension, and its `paths` entry for `PAGEVIEW`    |
| `-1758484801` … `-803` | the shared partials of `academic_base`, see [Shared partials](shared-partials.md)  |
| `-1758484901` … `-903` | the fallback layout of the extension                                               |

The last digit names the extension: `1` partners, `2` programs, `3` projects.

`50` and not `100`, for two reasons found in the project analysis of 2026-09:

- On `PAGEVIEW`, one `paths` entry stands for all three kinds of file. An
  extension entry at `100` replaced a site package's own `paths.100` on the
  extension's pages, so the page lost the site's layouts and partials. One
  project had moved the extension to `50` for exactly that.
- A project override between `50` and `100` should win over the extension, and
  a site package's `Pages/AcademicProgram.html` at `100` should win as well.

`50` is still above the `0` and `1` that `bk2k/bootstrap-package` registers
its page paths with (`Configuration/TypoScript/General/page.typoscript` of
15.0.4), so a partial of the extension is not shadowed by a theme partial of
the same name.

## The layout contract

The page template declares `<f:layout name="{programPageLayout}"/>` and renders
everything inside `<f:section name="Main">`, the section
`bk2k/bootstrap-package`'s `Layouts/Page/Default.html` renders. The layout name
is a site setting (`plugin.tx_academicprograms.page.layout`, default `Default`),
passed as a `TEXT` variable with `ifEmpty = Default`. The partner and project
templates do the same with `partnerPageLayout` and `projectPageLayout`, from
`plugin.tx_academicpartners.page.layout` and
`plugin.tx_academicprojects.page.layout`. Fluid 4.6 (v13) and 5.3 (v14) both
evaluate a layout name argument at render time
(`ParsingState::getLayoutName()`), compiled templates included, but both throw
on an empty name, which is what `ifEmpty` is for.

A section rendered from a layout gets the template's variables
(`AbstractTemplateView::renderSection()` switches back to the base rendering
context), so the partials inside it can be handed `{_all}`.

### The fallback layout

Both Fluid versions throw when the named layout exists in no layout root path.
Without a fallback, a site package that has no layout `Default`, a
`FLUIDTEMPLATE` package whose templates use none or one whose layouts carry
other names, would answer every page of the type with an exception, where before
the page rendered without the site's frame. The fallback covers `Default` only:
a layout the integrator names through the setting has to exist.

The extension therefore ships `Default.html` rendering the section and nothing
else, registered at `-1758484902` in `layoutRootPaths` and in `paths`. Every
layout a site registers sorts above it — as long as every key of the array is
an integer: `TemplatePaths` sorts the paths with
`ArrayUtility::sortArrayWithIntegerKeys()`, which leaves an array with a string
key in its written order. It lives in a directory of its own,
`Resources/Private/PageLayoutFallback/Layouts/`, because on `PAGEVIEW` the
entry at `50` expands to `Resources/Private/Layouts/` as well — a fallback
there would beat a site package registered at `paths.10`.

The partner and project fallback layouts have keys of their own next to it
(`-1758484901`, `-1758484903`), for the same reason the `academic_base` keys
differ per extension.

## Partials as the override seam

The section renders `Program/Page/Header`, `Media`, `CallToAction`, `Facts` and
`Content`, the rule of [Overridable partials](overridable-partials.md) applied
to a page template: a project that changes one part overrides one file.
`Facts` renders `Program/Facts`, which the details content element and the
program card render as well. The header
renders one element of its own (`academic-programs-detail__header`), because it
sits in a reversed flex column with the media and several siblings there would
be reordered.

`CallToAction` renders the application link of the page (ACE-777) after that
column, not inside the header: a project that overrides the header keeps the
link. Like the link back to the list in the header, it resolves the URL first
and renders nothing when the target cannot be linked, because
`f:link.typolink` would leave the bare label on the page. It reads nothing but
`program`, so a list item override renders the same partial, and its class is
therefore the block `academic-programs-application` rather than an element of
`academic-programs-detail`, see
[Classes are added, never moved](overridable-partials.md#classes-are-added-never-moved).

The partner page renders `Partner/Page/Header`, `Media`, `Categories`,
`Address` and `Content`, the project page `Project/Page/Header`, `Media`,
`Categories`, `Facts` and `Content`, in the order the single templates had
before. Their headers are one element for the same reason
(`academic-partners-detail__header`, `academic-projects-detail__header`), and
both render the subtitle of the page below the heading. The partner heading
gained that wrapper with ACE-785, the project heading had one already.

## Tests

`academic-programs/Tests/Functional/Pages/AcademicProgramPageLayoutTest.php`
renders fixture site packages of both shapes — with layouts, without, and a
`PAGEVIEW` package at `paths.10` and at `paths.100` whose layout needs a
partial of its own — plus the settings through constants and site settings,
overrides at `75` and the back link. Every render after the first compile of
the page template runs the compiled class, at the latest the second program
page of the layout setting test; in a run of the whole class an earlier test
has compiled it already.
`AcademicProgramPageTemplateTest.php` next to it keeps the template name, the
media, the content variable and the site package's partial root paths, and
`AcademicProgramApplicationLinkTest.php` the application link.

`AcademicPartnerPageLayoutTest.php` and `AcademicProjectPageLayoutTest.php`
repeat the layout cases for the other two page types, with a header or facts
override and the subtitle on both shapes, and for projects the heading
fallback on `PAGEVIEW`. A `PAGEVIEW` site package that assigns a variable
`data` of its own pins the page-first order of the templates and the data
processors. Their `*PageTemplateTest.php` carry the content
variable: manual order, ties by uid, other columns and hidden elements, the
translated page, an integrator's change of the variable, and site set sites.

## See also

- [Shared partials](shared-partials.md) — the `academic_base` path in
  `page.10` and why its key is negative.
- [Overridable partials](overridable-partials.md) — how a template is cut into
  partials.
- [TypoScript and site sets](typoscript-and-site-sets.md) — where the page
  object refinement is delivered from.
- [Program facts](program-facts.md) — the facts section of the program page and
  its two siblings.
