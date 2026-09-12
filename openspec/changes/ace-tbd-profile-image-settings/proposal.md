## Why

Five projects override the persons item and detail templates only to render
the profile image with a crop variant, a size and a placeholder. Upstream the
list and card render the original file unsized, the detail view only limits
the width to 1200 pixels, and a profile without an image renders nothing. The
templates cannot know a crop variant name, so a project that adds crop
variants in TCA has to override the templates as well.

## What Changes

- New site settings, mirrored as TypoScript constants with the same defaults:
  - `plugin.tx_academicpersons.image.list.cropVariant`,
    `plugin.tx_academicpersons.image.card.cropVariant` and
    `plugin.tx_academicpersons.image.detail.cropVariant`, default `default`;
  - optional per-gender placeholders
    `plugin.tx_academicpersons.image.placeholder.mr`, `.ms` and `.diverse`,
    next to `plugin.tx_academicpersons.image.placeholder.default`.
- There is one placeholder key family, `image.placeholder.*`, owned by this
  change. `ace-tbd-responsive-image-partial` introduces its `default` key with
  the neutral placeholder SVG it ships as the default value; this change adds
  the gender siblings and documents the group.
- A profile without an image therefore shows the neutral placeholder unless
  an integrator configures another one; an empty `image.placeholder.default`
  renders no image, as before 3.0.
- A placeholder is an `EXT:` resource path. A placeholder file that does not
  exist fails the rendering instead of being skipped.
- Image widths are not configured here: list and card images follow the
  `card` preset of the responsive image partial, the detail image its
  `detail` preset with today's maximum of 1200 pixels.
- The item image partial and the public profile image partial pass these
  settings to the responsive image partial.
- The crop variants `square` and `portrait` themselves are added by the change
  `ace-tbd-named-crop-variants`; this change only consumes their names.

Behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-persons/profile-image-display`: which crop, which size and which
  placeholder the persons plugins render for a profile image.

### Modified Capabilities

None.

## Impact

- `academic_persons` (`packages/fgtclb/academic-persons`): the site set
  settings definitions of the aggregate set, the TypoScript constants and
  setup of the shared plugin block, the item image partial and
  `Partials/Profile/PublicProfile/ProfileImage.html`, the integrator
  documentation and the 3.0 changelog.
- `academic_base` (`packages/fgtclb/academic-base`): only as the provider of
  the responsive image partial; nothing changes there.
- Depends on `ace-tbd-responsive-image-partial` (the partial, the
  placeholder SVG and the `image.placeholder.default` key),
  `ace-tbd-named-crop-variants` (the crop variant names) and
  `ace-tbd-item-and-list-partials` (the separate item image partial).
- No database schema change.

## Non-goals

- Adding or changing crop variant TCA.
- The markup of the responsive image partial.
- A width setting per view; widths belong to the presets of
  `ace-tbd-responsive-image-partial`.
- FAL identifiers as placeholder values.
- Shipping gendered placeholder artwork.
- Profile images rendered by `academic_contacts4pages`
  (`packages/fgtclb/academic-contact4pages`).
- A backport to branch `2`.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`persons-display-09`). Five of the six analysed projects carry their own code
for this today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-profile-image-settings` when the issue is filed after
implementation.
