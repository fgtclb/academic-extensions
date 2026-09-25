## Context

Verified on `main` in `packages/fgtclb/academic-programs`:

- `Resources/Private/Pages/AcademicProgram.html` has no `<f:layout>` and no
  section. It renders title, first image, `Program/Categories`, a fixed list
  of `creditPoints`, `jobProfile`, `performanceScope`, `prerequisites`, and
  `styles.content.getContent`.
- bootstrap_package 15.0.4 (instance `core-13`) uses a `FLUIDTEMPLATE` page
  object; its `Layouts/Page/Default.html` renders navigation, `Main` and the
  footer. A template without a layout renders none of it.
- `Configuration/TypoScript/Page/AcademicPrograms.typoscript` sets
  `paths.100`, `templateRootPaths.100`, `partialRootPaths.100` and
  `layoutRootPaths.100` (`Resources/Private/Layouts/`, which does not exist)
  inside the doktype 20 condition, and `templateName = AcademicProgram`
  (ACE-450).
- The extension ships no `settings.definitions.yaml`; academic_persons and
  academic_jobs do, together with matching constants.
- `Tests/Functional/Pages/AcademicProgramPageTemplateTest.php` checks that
  the template name resolves (ACE-450), and since ACE-721 the media, the
  content variable and the partial root paths of the site package, on a
  `FLUIDTEMPLATE` and a `PAGEVIEW` page object. Nothing in it renders a
  layout. (Re-checked on 2026-09-24; the change was written when the file
  held the template name case only.)
- Fluid 4.6 (v13) and 5.3 (v14) both evaluate a `<f:layout name="{…}"/>`
  argument at render time (`ParsingState::getLayoutName()`), and both throw
  when the named layout resolves in no layout root path.

## Goals / Non-Goals

**Goals:**

- A program page looks like a page of the site without a template override.
- Partials as the override seam, so a project replaces one part.

**Non-Goals:**

- The facts field list, the call to action and the most specific categories;
  they are separate changes that fill `Program/Page/Facts` and
  `Program/Page/CallToAction`.

## Decisions

### Layout from a site setting, section `Main`

The template becomes `<f:layout name="{programPageLayout}"/>` with
`<f:section name="Main">`. The site setting
`plugin.tx_academicprograms.page.layout` (default `Default`) and
`plugin.tx_academicprograms.page.listPid` (default `0`) are declared in
`Configuration/Sets/Full/settings.definitions.yaml` and mirrored with the same
defaults in the extension constants, the convention academic_persons and
academic_jobs follow (the facts field list change adds its settings to the
same file; whichever lands first creates it). Inside the doktype 20 condition
they reach the template as `page.10.variables.programPageLayout` and
`page.10.variables.programListPid` (`TEXT` objects from the constants), like
the `programContent` variable of the content change. If the dynamic layout
name proves unreliable on either core version, a literal `Default` is shipped
and the setting dropped.

Rejected: `page.10.settings`. A `PAGEVIEW` page object ignores it and assigns
the whole settings tree instead (`PageViewContentObject.php:101`), so the
template path would differ between the two integrations; `variables` reach
both. Rejected: an `academicPrograms.*` naming scheme in the ProgramDetails
set, which would be the only second scheme in the repository.

Rejected: keeping the layout-less template and documenting the override. It
is the status quo all six projects paid for.

Rejected: an upstream multi-column backend layout. Every project has
different sections, and replacing a backend layout is already supported.

### Decided: layout `Default` with section `Main`, name from a setting

The default contract is the layout named by
`plugin.tx_academicprograms.page.layout`, default `Default`, rendering the
section `Main`; a literal `Default` without the setting is the fallback only
if the dynamic name proves unreliable on v13 or v14. bootstrap_package's
`Layouts/Page/Default.html` renders exactly that section (checked in the
`core-13` instance), so the default fits the most common site package, the
setting covers layouts named or sectioned differently, and keeping the
template without a layout is the state all six projects paid for.

### Decided: a fallback layout below every site path

A site package without a layout `Default` would otherwise get an exception on
every program page, where today it gets the page without its frame: a
`FLUIDTEMPLATE` package whose page templates use no layout, or one whose
layouts are named differently. The fallback covers `Default` only; a layout
the integrator names has to exist, like any Fluid layout. The extension
therefore ships a layout `Default` that renders nothing but the section
`Main`, in a directory of its own
(`Resources/Private/PageLayoutFallback/Layouts/`), registered under a unique
negative key in `layoutRootPaths` and in `paths`. Every layout a site package
registers sorts above it while all keys of the array are integers, which
`TemplatePaths` needs to sort them at all; it is used only where the site has
no layout `Default`.

It cannot live in `Resources/Private/Layouts/`: on `PAGEVIEW`, `paths.50`
derives `Layouts/` from the same directory at key 50, which would beat a site
package registered at `paths.10`.

Rejected: no layout path at all, as first designed. It turns a frameless page
into an HTTP 500 for a site that changed nothing, and no setting can fix it.

### Section partials

`Main` renders `Program/Page/Header` (title, subtitle, back link from
`plugin.tx_academicprograms.page.listPid`), `Program/Page/Media`,
`Program/Page/Facts`
(today's categories and attribute list, unchanged) and `Program/Page/Content`
(the `programContent` variable of the content change).

Guessed layout — a sketch, not a design:

```text
+------------------------------------------------------------+
| site header / navigation            (site Default layout)  |
+------------------------------------------------------------+
| < Back to all programs                                     |
| Bachelor of Arts                                           |
| Subtitle                             [ Apply now ]         |
| +--------------------------------------------------------+ |
| |                  media (first image)                   | |
| +--------------------------------------------------------+ |
| Facts:  Degree | Duration | 180 ECTS | Location | ...      |
| ---------------------------------------------------------- |
| page content colPos 0                                      |
+------------------------------------------------------------+
| site footer                                                |
+------------------------------------------------------------+
```

The call to action is shown for orientation only; it belongs to the
application link change.

### Path index 50, no layout path at 50

The four path entries move from 100 to 50 and `layoutRootPaths.100` is
removed, so the site's layouts resolve and a site package's own entries at
100 are not replaced. The only layout the extension registers is the fallback
above, far below 50. One project already runs the extension at 50 for that
reason.

Rejected: keeping 100 and documenting "use a higher index". On `PAGEVIEW`,
`paths.100` replaces the site package's entry of the same index, which no
documentation can fix.

### Decided: no deprecation here, the content-load sets are removed in 3.0

This change deprecates nothing. The content-load sets of the three
extensions and `Partials/Program/Categories.html` are removed in 3.0 as a
breaking change, not deprecated for removal in 4.0: the programs set by
`ace-721-program-page-content-without-getcontent`, the partner and project
sets by the changes that take their page templates off
`styles.content.getContent`, the partial by
`ace-733-program-facts-field-list`. This supersedes the earlier
recommendation to deprecate the three sets together.

The `Program/Page/Content` partial renders the `programContent` variable, so
the layout needs no set. Until the facts change lands, `Program/Page/Facts`
renders `Program/Categories`; that change replaces it with `Program/Facts`
before it removes the partial.

## Risks / Trade-offs

- [A site layout without a section `Main` renders an empty page] → The layout
  setting and the Breaking changelog entry.
- [A site without a layout `Default`] → The fallback layout; the page renders
  as it did before, without the site frame. A layout named by the setting that
  does not exist fails, and the documentation says so.
- [A project reused or cleared index 100 of `page.10`] → Breaking changelog
  entry with the new index.
- [Partials of the same name in a site's partial root] → The names live under
  `Program/Page/`, which no known project uses.

## Migration Plan

Projects that override `AcademicProgram.html` keep their override, provided
it no longer renders `styles.content.getContent`, which the content change
stops defining. Projects that override one part move to the matching
partial. Rollback is the code only; no data changes.

## Open Questions

None.
