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

| View                                                                  | Key  | Renders the partial from                                       |
|-----------------------------------------------------------------------|------|----------------------------------------------------------------|
| `plugin.tx_academicpersons` (`Configuration/TypoScript/Default/`)     | `-1` | `Profile/Item.html`, `Profile/PublicProfile/ProfileImage.html` |
| `plugin.tx_academiccontacts4pages` (`Configuration/TypoScript/List/`) | `-1` | `Profile/Item.html` of `academic_persons`                      |

`academic_persons_edit` registers the persons partials but renders none that
shows an image, so it does not register the academic_base path. A later
template of its own that renders `Academic/Image` adds the path with it.

The contacts plugin renders `Profile/Item` with its own settings, so its setup
also maps `settings.image.placeholder.default` from the persons constant — the
same way it maps `detailPid`.

A view that renders the partial without the path fails with a Fluid
`InvalidTemplateResourceException`. That applies to a project page object that
renders `Profile/Item` itself, and to a project that replaces the root path
array of a plugin; the Breaking entries of both extensions name the line to
add.

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

## See also

- [TypoScript and site sets](typoscript-and-site-sets.md) — where the plugin
  views are configured and delivered.
- [Functional tests](../testing/functional-tests.md) — the frontend rendering
  harness the plugin tests use.
