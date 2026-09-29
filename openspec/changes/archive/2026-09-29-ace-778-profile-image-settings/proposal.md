## Why

Five projects override the persons item and detail templates only to render
the profile image with a crop variant of their choice and a placeholder per
gender. The templates render the `default` crop and one placeholder for
everybody, and they cannot know a crop variant name, so a project that wants
another crop has to copy them.

## What Changes

- New site settings, mirrored as TypoScript constants with the same defaults:
  - `plugin.tx_academicpersons.image.list.cropVariant`, `.card.cropVariant`
    and `.detail.cropVariant`, default `default`.
  - `plugin.tx_academicpersons.image.placeholder.mr`, `.ms` and `.diverse`,
    default empty, next to the existing `image.placeholder.default`.
- The card element reads the card crop variant, the public profile the detail
  one, and every other element that renders profile items the list one: the
  list, the list and detail, the selected profiles, the selected contracts and
  the contacts of a page.
- A gender placeholder wins over the default one. An empty one, and a profile
  without a gender, fall back to the default placeholder.
- A placeholder is an `EXT:` resource path, and a missing file fails the
  rendering.
- Widths stay in the presets of the responsive image partial.
- The contacts element of `academic_contacts4pages` maps the list crop
  variant and the gender placeholders, as it already maps the default
  placeholder.

Behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-persons/profile-image-display`: which crop variant and which
  placeholder the persons content elements render for a profile image.

### Modified Capabilities

- `academic-persons/profile-image-rendering`: an empty default placeholder no
  longer means "no image" when a placeholder is configured for the profile's
  gender.

## Impact

- `academic_persons` (`packages/fgtclb/academic-persons`): the settings
  definitions of the aggregate set, the constants and setup of the shared
  plugin block, the card template, `Profile/List/Items`, `Profile/Item/Image`,
  `PublicProfile/ProfileImage`, the documentation and the 3.0 changelog.
- `academic_contacts4pages` (`packages/fgtclb/academic-contact4pages`): four
  more settings mapped in the plugin setup, its documentation and changelog.
- `academic_base`: the provider of the responsive image partial, which does
  not change. Only the example in its template documentation does.
- Builds on `ace-646-responsive-image-partial`, `ace-744-named-crop-variants`
  and `ace-716-item-and-list-partials`, all merged on `main`.
- No database schema change.

## Non-goals

- Adding or changing crop variant TCA.
- The markup of the responsive image partial.
- A width setting per view.
- FAL identifiers as placeholder values.
- Shipping gendered placeholder artwork.
- Settings of `academic_contacts4pages` of its own. It follows the persons
  settings.
- A backport to branch `2`.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`persons-display-09`). Five of the six analysed projects carry their own code
for this today.
