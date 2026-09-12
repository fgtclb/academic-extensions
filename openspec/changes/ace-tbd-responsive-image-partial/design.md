## Context

Verified on `main`:

- academic_base has `Resources/Private/Language/` only, so no Fluid file.
- `academic-persons/Resources/Private/Partials/Profile/Item.html:80,85`
  renders `<f:image image="{profile.image}" class="card-img-top img-fluid"/>`,
  honouring `settings.showFields`, with no crop variant and no placeholder.
  `Partials/Profile/PublicProfile/ProfileImage.html:9` renders
  `maxWidth="1200"`, `loading="lazy"` and the names as `alt`.
- `Profile/Item` is rendered by the persons `List`, `Card`,
  `SelectedProfiles` and `SelectedContracts` views, and by
  `academic-contact4pages/Resources/Private/Templates/Contacts/List.html`.
  The contacts view registers the persons partials at `partialRootPaths.5`,
  the persons_edit view at `partialRootPaths.0`.
- The persons view uses `partialRootPaths.0` for its own partials and `.1` for
  the project constant; contacts uses `.5` and `.10`.
- The only upstream `<picture>` is
  `academic-persons-edit/Resources/Private/Partials/Profile/Image/Card.html:19`,
  with webp sources of 390, 370 and 690 pixels.
- `imagefile_ext` contains `webp` in the core default configuration of
  13.4.35 and 14.3.6.

## Goals / Non-Goals

**Goals:**

- One file a project overrides to change image markup across all academic
  plugins.
- The same partial works for Extbase file references (persons profile image)
  and core file references (page templates in `cross-cutting-02`).

**Non-Goals:**

- Choosing breakpoints per content element in the backend.

## Decisions

### A partial, not a ViewHelper

`EXT:academic_base/Resources/Private/Partials/Academic/Image.html` takes
`image`, `preset`, `cropVariant` (default `default`), `placeholder`, `alt`,
`class`, `showCaption` and `showCopyright`. Each preset is a section in the
same file holding its `<source>` list, so a project changes all plugins by
overriding one file. `card` starts from the persons_edit card widths, and
`detail` keeps today's maximum width of 1200 pixels. Rejected: a ViewHelper.
It puts the markup in PHP, and overriding it takes an XCLASS or a DI override,
the fragile customisation this analysis found in several projects. Rejected:
a site setting that points all extensions at one partial path, because
academic_base ships no TypoScript to default it.

### One resource for both reference types

The partial resolves `image.originalResource` when it exists (Extbase file
reference) and uses `image` otherwise (core file reference). The caption comes
from `description`, and the copyright from `properties.copyright`. Array
access is safe without EXT:filemetadata, while `getProperty()` throws.

### Register the partials below every project slot

A partial root path with a higher key wins, and `Academic/Image.html` must
stay overridable through each extension's project constant. The academic_base
path therefore gets a key below the lowest existing key in the persons,
contacts4pages and persons_edit views. With persons already at `0`, that means
a negative key. Rejected: key `5` as first proposed; in the persons view it
sorts after the project constant at `1`, so the upstream partial would beat a
project override. Rejected: renumbering the persons keys, which breaks every
project that sets `partialRootPaths.1` directly.

### Decided: one placeholder key, owned by the image settings change

The card reads the placeholder from `settings.image.placeholder.default` of
the persons plugins, the key family `image.placeholder.*` that
`ace-tbd-profile-image-settings` owns, and this change ships
`EXT:academic_persons/Resources/Public/Images/ProfilePlaceholder.svg` as its
default. If this change lands first it introduces only that one key;
the image settings change adds the per-gender siblings next to it. An empty
value keeps today's cards without an image. The value is an `EXT:` resource
path only, and a placeholder file that does not exist fails the rendering
instead of being skipped, as decided in `ace-tbd-profile-image-settings`.
The first draft declared a separate `profileImagePlaceholder` setting, which
left two settings for one value with contradicting defaults.

### Decided: the `teaser` preset ships now

The partial ships four presets from the start: `card`, `detail`, `logo` and
`teaser`. No upstream template renders `teaser` yet, so the preset is covered
by the fixture template of the partial's own functional test rather than by a
plugin test. Adding a preset later would have been non-breaking, but a fixed
set of four presets gives projects a stable name for teaser images now,
instead of each project inventing its own section in an override.

### What the partial renders

Guessed layout — a sketch, not a design:

```text
+---------------------------+
| <picture>  webp per preset|
|  [ portrait crop image ]  |  placeholder SVG when empty
| <figcaption> text  (c) X  |  only with showCaption/showCopyright
+---------------------------+
```

## Risks / Trade-offs

- [Partial arguments become API] → Declared `@api` in
  `academic-base/Documentation/Templates`; a change needs a Breaking entry.
- [Negative partial root path keys may not sort first on one core version] →
  Task 1.4 verifies the order on v13 and v14 before any template uses the
  partial.
- [An installation removed `webp` from `imagefile_ext`] → Task 1.3 records
  what the processing does then, and the Important entry names the
  requirement.
- [A project page object renders `Profile/Item` without the academic_base
  path] → The Important entry names the path to add.

## Open Questions

None.
