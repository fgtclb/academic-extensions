# Shared partials

`academic_base` ships Fluid partials that several academic extensions render,
so a project overrides a piece of markup once instead of once per extension.
Today there is one: the responsive image partial
`Resources/Private/Partials/Academic/Image.html`. Its arguments and presets
are public API and are documented in the `Templates` chapter of
`academic_base`, not here; this page is about how the partial is wired.

## Why a partial and not a ViewHelper

The partial is the override surface. A project changes the image markup of all
plugins by copying one file into its partial root path, the mechanism every
integrator already uses. A ViewHelper would put the markup into PHP, and
changing it would take an XCLASS or a service override — the customisation the
project analysis of 2026-09 found to be fragile in several installations.

The presets are sections of the same file for the same reason: breakpoints and
widths are markup, and a project changes them where it changes the markup.

## The root path key `-1`

Fluid sorts the root paths of a view with
`ArrayUtility::sortArrayWithIntegerKeys()` and takes a partial from the path
with the **highest** key that has it. The persons view uses `0` for its own
partials and `1` for the project constant; the contacts view uses `5` for the
persons partials and `10` for its constant. The academic_base path has to sort
below all of them, or the upstream partial would beat a project override, so
it is registered with the key `-1` in both views.

PHP turns the TypoScript key `-1` into the integer `-1`, so the root paths keep
the all-integer keys `sortArrayWithIntegerKeys()` needs and sort with
`ksort()`. The sort is skipped as soon as a project adds a string key, which is
the core behaviour for any root path and not specific to this path.

Key `5` was the first proposal and is wrong for the persons view: it sorts
above the project constant at `1`. Renumbering the persons keys was rejected
because it breaks every project that sets `partialRootPaths.1` directly.
`AcademicPersonsProfileImageRenderingTest::projectOverrideOfTheSharedImagePartialWins()`
turns red with the key `5`.

## Who registers the path

| View                                                                  | Key           | Renders the partial from                                                            |
|-----------------------------------------------------------------------|---------------|-------------------------------------------------------------------------------------|
| `plugin.tx_academicpersons` (`Configuration/TypoScript/Default/`)     | `-1`          | `Profile/Item.html`, `Profile/PublicProfile/ProfileImage.html`                      |
| `plugin.tx_academiccontacts4pages` (`Configuration/TypoScript/List/`) | `-1`          | `Profile/Item.html` of `academic_persons`                                           |
| `plugin.tx_academicpartners`                                          | `-1`          | `Partner/Item.html`, `Partnerships/List/Item.html`, `Partnerships/Teaser/Item.html` |
| `plugin.tx_academicprograms`                                          | `-1`          | `Program/Item.html`                                                                 |
| `plugin.tx_academicprojects`                                          | `-1`          | `Project/Item.html`                                                                 |
| `plugin.tx_academicjobs`                                              | `-1`          | `Job/Item.html`                                                                     |
| `page.10` of `academic_partners` (doktype 40 only)                    | `-1758484801` | `Pages/AcademicPartner.html`, under `partialRootPaths` and under `paths`            |
| `page.10` of `academic_programs` (doktype 20 only)                    | `-1758484802` | `Pages/AcademicProgram.html`, under `partialRootPaths` and under `paths`            |
| `page.10` of `academic_projects` (doktype 30 only)                    | `-1758484803` | `Pages/AcademicProject.html`, under `partialRootPaths` and under `paths`            |
| `page.10` of `academic_jobs` (every page)                             | `-1758484804` | `Job/Item.html`, which that extension registers in `page.10` as well                |

`academic_persons_edit` registers the persons partials but renders none that
shows an image, so it does not register the academic_base path. A later
template of its own that renders `Academic/Image` adds the path with it.

`academic_jobs` renders no page template of its own. It registers the path in
`page.10` because it has been registering its own partials there all along, so
a page object that renders `Job/Item` finds that partial — and would then fail
on the `Academic/Image` it renders.

The contacts plugin renders `Profile/Item` with its own settings, so its setup
also maps `settings.image.placeholder.default` from the persons constant — the
same way it maps `detailPid`.

A view that renders the partial without the path fails with a Fluid
`InvalidTemplateResourceException`. That applies to a project that replaces the
root path array of a plugin, and to a page object of a project that renders one
of those partials itself; the Breaking entry of each of the six extensions names
the line to add.

## The `page.10` keys

`page.10` is the page object of the site package, not of an extension, so the
key an extension writes into its `partialRootPaths` is a key in someone else's
array. `-1` is fine in a plugin view, where the extension owns every other key;
in `page.10` it would replace a theme path that happens to use it. Each of the
four extensions therefore uses a negative key of its own, unique across them —
sorting below every theme and project path, and colliding with none of them.

The three page objects are inside a `[page && traverse(page, "doktype") == NN]`
condition, so two of them never apply to the same page anyway. The distinct keys
are about the theme, and about a project that adds a path of its own.

A page object comes in two shapes, and only one of them reads `partialRootPaths`.
`PAGEVIEW` derives its partial and layout root paths from `paths` by appending
`Partials/` and `Layouts/`, and reads no `partialRootPaths` at all — the class
docblock of `PageViewContentObject` says so in as many words. The
three page objects therefore register the academic_base path twice: as
`EXT:academic_base/Resources/Private/Partials/` under `partialRootPaths` for a
`FLUIDTEMPLATE` integration, and as `EXT:academic_base/Resources/Private/` under
`paths` for a `PAGEVIEW` one, both with the same negative key. Registering only
the first one left a `PAGEVIEW` page dying on `Academic/Image`, which is what
`Academic*PageTemplateTest::*PageShowsItsMediaOnAPageViewPageObject()` pins.

## Placeholder

The partial takes the placeholder as an `EXT:` path and hands it to
`<f:image src="…">`. A path that does not resolve fails the rendering as
`f:image` does, instead of being skipped: a missing file shipped by an
extension is a deploy error a functional test catches, while a skipped one
would hide it. `academic_persons` ships `Resources/Public/Images/ProfilePlaceholder.svg`
as the default of `plugin.tx_academicpersons.image.placeholder.default`.

## Tests

- `academic-base/Tests/Functional/Partials/` renders the partial through a
  fixture template with real files and real processing: every preset, the crop
  variants, SVG, placeholder, empty output, alternative text, caption, and the
  copyright with and without `filemetadata`. The processed WebP files are read
  back from `sys_file_processedfile`, so a crop that reaches only the fallback
  image is caught.
- `academic-persons/Tests/Functional/Plugins/AcademicPersonsProfileImageRenderingTest.php`
  covers the card, the public profile, the placeholder setting, the shown
  fields and the project override.
- `academic-contact4pages/Tests/Functional/Plugins/AcademicContacts4PagesListPluginTest.php`
  covers the contacts card, which fails without the path.
- `academic-{partners,programs,projects,jobs}/Tests/Functional/Plugins/Academic*ImageRenderingTest.php`
  cover the list items of those four extensions, and
  `academic-{partners,programs,projects}/Tests/Functional/Pages/Academic*PageTemplateTest.php`
  the page templates, on a `FLUIDTEMPLATE` and on a `PAGEVIEW` page object. They
  assert the number of sources and the width of the fallback image, which is what
  separates one preset from another — every preset renders a `<picture>`, so "is
  a picture" would pass for the wrong one. The page template fixtures also carry
  a theme path at the key `0` and a project path at `1` and assert that the
  extension replaced neither.
- The shared assertions live in
  `packages-dev/testing-helper/Classes/FunctionalTestCase/ResponsiveImageAssertionTrait.php`.

`academic_projects` deserves a note. Its plugin view has a single partial root
path, `0`, and it *is* the project constant, so a project that sets it names a
directory holding nothing but its override. The other partials of the extension
stay resolvable all the same: `ActionController::addDefaultPathToPaths()`
prepends `EXT:academic_projects/Resources/Private/Partials/` when the configured
paths do not list it, and prepending puts it at the lowest precedence, so the
project still wins for every file it does carry.
`AcademicProjectsImageRenderingTest::projectOverrideOfTheSharedImagePartialWins()`
asserts both halves: the override renders, and the item partial of the extension
is still found.

## See also

- [TypoScript and site sets](typoscript-and-site-sets.md) — where the plugin
  views are configured and delivered.
- [Functional tests](../testing/functional-tests.md) — the frontend rendering
  harness the plugin tests use.
