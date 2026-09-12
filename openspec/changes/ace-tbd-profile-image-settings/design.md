## Context

See `proposal.md` for the motivation. State on `main`:

- `Configuration/TCA/tx_academicpersons_domain_model_profile.php` defines
  `image` as a plain `type => file` without `overrideChildTca`; there are no
  crop variants anywhere in the persons TCA.
- `Partials/Profile/Item.html` renders `<f:image image="{profile.image}"
  class="card-img-top img-fluid"/>` twice, without size or crop variant, so
  the original file is delivered.
- `Partials/Profile/PublicProfile/ProfileImage.html` renders
  `maxWidth="1200"` and nothing else.
- The frontend editor crops at 3:4 (`Settings.yaml`
  `special.image.settings.ratio: 3x4`).
- The shared plugin block `plugin.tx_academicpersons` gets its settings from
  `Configuration/TypoScript/Default/constants.typoscript` and from
  `Configuration/Sets/Full/settings.definitions.yaml`, which must carry the
  same defaults (the comment at the top of that file explains why).

This change builds on three others: `ace-tbd-responsive-image-partial` ships
the partial with `image`, `cropVariant`, `preset` and `placeholder`
arguments, `ace-tbd-named-crop-variants` adds the `square` and `portrait`
crop variants to the profile image, and `ace-tbd-item-and-list-partials`
separates the item image into its own partial.

## Goals / Non-Goals

**Goals:**

- A crop variant name and a placeholder become configuration, so a project
  stops overriding the image blocks of the item and detail templates.

**Non-Goals:**

- Validating crop variant names against TCA.
- Any new PHP; the change is settings and Fluid only.
- A width setting; widths belong to the presets of the responsive partial.

## Decisions

### Site settings, not FlexForm fields

The values are integrator decisions per site, not editor decisions per
content element. They are declared once in the aggregate set's
`settings.definitions.yaml`, once in the constants with the same default, and
mapped in the shared setup to `settings.image.*`.

Rejected: FlexForm fields. There are six FlexForm files, two of them split per
core version (ACE-560), and an editor cannot know the crop variant names.

### `placeholder.default` instead of `placeholder` plus `placeholder.<gender>`

The candidate proposed `image.placeholder` next to `image.placeholder.mr`. A
site settings tree cannot hold a value and children on the same node, so the
generic value becomes `image.placeholder.default`, and `mr`, `ms` and
`diverse` are siblings of it. These are the gender values of the profile TCA.

### Decided: one placeholder key, defaulting to the shipped SVG

`image.placeholder.default` is the only generic placeholder setting, and its
default is `EXT:academic_persons/Resources/Public/Images/ProfilePlaceholder.svg`.
`ace-tbd-responsive-image-partial` lands first, introduces that key with that
default and reads it; this change owns the `image.placeholder.*` family, adds
`mr`, `ms` and `diverse`, and documents the group. An empty value renders no
image.

The two drafts declared two settings for one value with contradicting
defaults: a `profileImagePlaceholder` defaulting to the SVG and an
`image.placeholder.default` defaulting to empty. Several analysed projects and
the ACE demo add a placeholder of their own, so a visible default serves more
installations than an empty one.

### Gender resolution in Fluid

The partial picks `{settings.image.placeholder.{profile.gender}}` and falls
back to `{settings.image.placeholder.default}`. That is one `f:variable`, and
it replaces the ViewHelpers the ACE demo and one other project wrote for it.

Rejected: a model getter or a ViewHelper. Both are PHP for a two-line choice,
and an override would need an XCLASS.

### Decided: widths stay in the presets of the responsive partial

There is no width site setting, neither for the list and card nor for the
detail view. List and card images take their widths from the `card` preset
of `ace-tbd-responsive-image-partial`, the detail image from its `detail`
preset, which keeps today's maximum of 1200 pixels. A project changes widths
by overriding the preset section of that partial, which changes every
plugin at once.

The presets already carry the widths: `card` starts from the card widths of
the frontend editor, and `detail` keeps 1200. A second width setting would
compete with the preset, and an integrator could not tell which of the two
wins. The earlier draft of this change declared a maximum width per view.

### Decided: the placeholder is an `EXT:` path, and a missing file fails

A placeholder setting accepts an `EXT:` resource path only, and is handed to
the responsive partial unchanged. A combined FAL identifier
(`1:/user_upload/x.svg`) is not a supported value. A placeholder file that
does not exist fails the rendering, as `f:image` does, and is not skipped.

`f:image` throws exception 1509741911 on a missing resource
(`cms-fluid/Classes/ViewHelpers/ImageViewHelper.php:181-183`). A FAL
placeholder that an editor deletes in the file list would therefore break
every list at runtime, while a missing `EXT:` file is a deploy error that a
functional test catches before release. Skipping an unresolvable placeholder
silently would need a ViewHelper, against the partial-only decision of the
responsive partial. The earlier draft of this change accepted FAL
identifiers as well; the accepted format is aligned with
`ace-tbd-responsive-image-partial`.

Rejected: a `sys_file` uid, which is not portable between instances.

### An unknown crop variant is not an error

The core crop variant collection returns an empty area for an unknown name, so
the image is processed without a crop. The change keeps that and documents it
instead of validating names, because a project with its own crop TCA (one
uses `default` and `tablet`) only has to set the setting.

## Risks / Trade-offs

- [A placeholder path that does not resolve breaks the whole list] →
  Intended: only `EXT:` paths are accepted, so the failure is a deploy error.
  The documentation names the format, and functional tests cover a valid
  and a missing `EXT:` path.
- [Projects with their own crop TCA keep their variant names] → They set the
  setting; no migration is needed.
- [The processed-file behaviour of the list and card changes] → That change
  comes with `ace-tbd-responsive-image-partial` and its changelog, not with
  this one.

## Migration Plan

None for crop variants: their defaults reproduce today's output. Widths
change with `ace-tbd-responsive-image-partial` and its changelog, not here.
A profile without an image shows the neutral placeholder by default; an
installation that wants no image sets `image.placeholder.default` to an empty
value. Projects replace their image overrides with the settings after
upgrading; a project that referenced its placeholder as a FAL file moves it
into its site package and sets the `EXT:` path.

## Open Questions

None.

Guessed layout — a sketch, not a design:

```text
+-----------------+
|   .-----.       |   <- placeholder.<gender> when no image
|  ( o o )        |
|   `---'         |
+-----------------+
| Dr. Anna Beispiel|
| Professor       |
+-----------------+
```
