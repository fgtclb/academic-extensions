# Page type rendering

`academic_partners`, `academic_programs` and `academic_projects` each register
a page type (doktype 40, 20 and 30) and ship a page template for it. The page
object that renders it, `page.10`, belongs to the site package, not to the
extension: the extension refines it inside a
`[page && traverse(page, "doktype") == NN]` condition, and everything it
writes there is a key in someone else's array. This page is about writing those
keys so that the site package keeps working on the extension's pages. The
integrator side is in each extension's `Documentation/Configuration/`.

Today only the program page follows all of it (ACE-450 set the template name,
ACE-721 the content variable, ACE-726 the rest). The partner and
project pages still register their paths at `100` and declare no layout; the
change `ace-tbd-page-templates-sections-subtitle` brings them in line.

## Two shapes of page object

A site package renders pages through `FLUIDTEMPLATE` or through `PAGEVIEW`, and
the two read different properties:

| Property      | `FLUIDTEMPLATE`                                            | `PAGEVIEW`                                                             |
|---------------|------------------------------------------------------------|------------------------------------------------------------------------|
| Template name | `templateName`                                             | the backend layout without `pagets__`, below `Pages/`                  |
| Root paths    | `templateRootPaths`, `partialRootPaths`, `layoutRootPaths` | `paths` only, each entry expanded to `Pages/`, `Partials/`, `Layouts/` |
| `settings`    | `settings` of the object                                   | the settings tree of the site; the object's `settings` are ignored     |
| `variables`   | yes                                                        | yes                                                                    |

So an extension registers every path twice, once per shape, and passes values
to its template through `variables` — the one property both read the same way.
A value through `settings` would arrive at a different path in each shape
(`PageViewContentObject::render()`). `dataProcessing` is read by both as well,
so a value a processor needs goes in as an option of that processor: the
program facts take their field list as `factsFields` of `program-data`, see
[Program facts](program-facts.md).

## Path keys

Fluid takes a file from the path with the **highest** key that has it. Three
kinds of key are in use on the program page:

| Key           | What                                                                               |
|---------------|------------------------------------------------------------------------------------|
| `50`          | `Pages/` and `Partials/` of the extension, and its `paths` entry for `PAGEVIEW`    |
| `-1758484802` | the shared partials of `academic_base` — see [Shared partials](shared-partials.md) |
| `-1758484902` | the fallback layout of the extension                                               |

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
passed as a `TEXT` variable with `ifEmpty = Default`: Fluid 4.6 (v13) and 5.3
(v14) both evaluate a layout name argument at render time
(`ParsingState::getLayoutName()`), compiled templates included, but both throw
on an empty name.

A section rendered from a layout gets the template's variables
(`AbstractTemplateView::renderSection()` switches back to the base rendering
context), so the partials inside it can be handed `{_all}`.

### The fallback layout

Both Fluid versions throw when the named layout exists in no layout root path.
Without a fallback, a site package that has no layout `Default` — a
`FLUIDTEMPLATE` package whose templates use none, or one whose layouts carry
other names — would answer every program page with an exception, where before
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

A partner or project fallback layout gets a key of its own next to it
(`-1758484901`, `-1758484903`), for the same reason the `academic_base` keys
differ per extension.

## Partials as the override seam

The section renders `Program/Page/Header`, `Media`, `Facts` and `Content`, the
rule of [Overridable partials](overridable-partials.md) applied to a page
template: a project that changes one part overrides one file. `Facts` renders
`Program/Facts`, which the details content element and the program card render
as well. The header
renders one element of its own (`academic-programs-detail__header`), because it
sits in a reversed flex column with the media and several siblings there would
be reordered.

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
media, the content variable and the site package's partial root paths.

## See also

- [Shared partials](shared-partials.md) — the `academic_base` path in
  `page.10` and why its key is negative.
- [Overridable partials](overridable-partials.md) — how a template is cut into
  partials.
- [TypoScript and site sets](typoscript-and-site-sets.md) — where the page
  object refinement is delivered from.
- [Program facts](program-facts.md) — the facts section of the program page and
  its two siblings.
