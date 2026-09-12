## Why

Five of six projects copy the academic_persons card and detail partials for
one reason: to replace the plain image tag with a `<picture>` that serves webp
per breakpoint, applies a named crop variant and shows a placeholder when a
profile has no image. academic_base ships no templates, so every project
writes this markup itself and keeps a copy of the surrounding partial frozen at
the version it copied.

## What Changes

- academic_base (`packages/fgtclb/academic-base`) ships its first Fluid
  partial, a responsive image partial:
  - the presets `card`, `detail`, `logo` and `teaser`, each with its own webp
    sources;
  - a crop variant argument;
  - an optional placeholder;
  - optional caption and copyright;
  - SVG images passed through unprocessed.

  The partial and its arguments are public API.
- academic_persons (`packages/fgtclb/academic-persons`) renders the profile
  image of the list card and of the public profile detail through it. It
  ships a neutral placeholder that the card shows for a profile without an
  image; an integrator replaces or disables the placeholder through the
  setting `image.placeholder.default`, the one placeholder key the image
  settings change (`ace-tbd-profile-image-settings`) owns.
- academic_contacts4pages (`packages/fgtclb/academic-contact4pages`) and
  academic_persons_edit (`packages/fgtclb/academic-persons-edit`) render the
  persons partials, so their views register the academic_base partials too.
- **BREAKING** (markup): card and detail images change from `<img>` to
  `<picture>`, and cards of profiles without an image show the placeholder.
  Project CSS that selects the image directly may need an adjustment. This is
  announced as an Important changelog entry.
- The behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-base/responsive-image-partial`: what the shared image partial
  renders per preset, per image type and for a missing image.
- `academic-persons/profile-image-rendering`: how profile cards and the public
  profile detail show the profile image and the placeholder.

### Modified Capabilities

None.

## Impact

- academic_base: a new `Resources/Private/Partials/` tree and a new
  `Documentation/Templates` chapter; no new dependency.
- academic_persons: the card and detail image partials, a new
  `Resources/Public/Images/` folder, its plugin TypoScript and constants.
- academic_contacts4pages, academic_persons_edit: their plugin TypoScript.
- No database, TCA or PHP change.

## Non-goals

- Adopting the partial in academic_partners, academic_programs,
  academic_projects and academic_jobs (candidate `cross-cutting-02`).
- Defining crop variants in TCA (candidate `cross-cutting-03`).
- A gendered or per-profile placeholder; the per-gender siblings of the
  placeholder key belong to `ace-tbd-profile-image-settings`.
- A placeholder setting of its own next to `image.placeholder.default`.
- Backporting to branch `2`, where webp output is rejected on TYPO3 v12
  (ACE-303).

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`cross-cutting-01`). All six analysed projects carry their own code for this
today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-responsive-image-partial` when the issue is filed after
implementation.
