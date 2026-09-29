## Context

See `proposal.md` for the motivation. State on `main`, re-checked on
2026-09-29 after the three changes this one builds on were merged:

- The profile image offers the crop variants `default`, `square` (1:1) and
  `portrait` (3:4) (`ace-744-named-crop-variants`). The frontend editor still
  crops at 3:4 (`Settings.yaml` `special.image.settings.ratio: 3x4`).
- `Partials/Profile/Item/Image.html` renders the item image through the
  `Academic/Image` partial of `academic_base` with the `card` preset, the
  placeholder `settings.image.placeholder.default` and no crop variant. It
  honours `settings.showFields`. It is the one image partial of every
  element that renders profile items: the list, the card, the selected
  profiles, the selected contracts and the contacts element of
  `academic_contacts4pages`. They reach it through `Profile/List/Items` and
  `Profile/Item`, and the contacts element through `Profile/Item` directly.
- `Partials/Profile/PublicProfile/ProfileImage.html` renders the `detail`
  preset with the names as alternative text, no crop variant and no
  placeholder.
- `plugin.tx_academicpersons.image.placeholder.default` is declared in
  `Configuration/Sets/Full/settings.definitions.yaml` and in the constants,
  both defaulting to the shipped `ProfilePlaceholder.svg`, and mapped to
  `settings.image.placeholder.default` in the shared setup. The contacts
  element maps the same constant into its own plugin settings.
- The shared plugin block `plugin.tx_academicpersons` gets its settings from
  `Configuration/TypoScript/Default/constants.typoscript` and from the
  aggregate set's `settings.definitions.yaml`, which must carry the same
  defaults (the comment at the top of that file explains why). There is no
  plugin specific TypoScript: all six content elements share one settings
  array.
- The placeholder renders with an empty alternative text today: the partial
  passes no `alt`, and the file an `EXT:` path resolves to has no
  alternative text of its own.

## Goals / Non-Goals

**Goals:**

- A crop variant name and a placeholder become configuration, so a project
  stops overriding the image blocks of the item and detail templates.

**Non-Goals:**

- Validating crop variant names against TCA.
- Any new PHP. The change is settings, TypoScript and Fluid only.
- A width setting. Widths belong to the presets of the responsive partial.

## Decisions

### Site settings, not FlexForm fields

The values are integrator decisions per site, not editor decisions per
content element. They are declared once in the aggregate set's
`settings.definitions.yaml`, once in the constants with the same default, and
mapped in the shared setup to `settings.image.*`.

Rejected: FlexForm fields. There are six FlexForm files, two of them split per
core version (ACE-560), and an editor cannot know the crop variant names.

### The card template names its view, every other caller gets `list`

All six content elements share one settings array, so the item image cannot
tell from the settings which element renders it. `Templates/Profile/Card.html`
therefore passes `imageView: 'card'` to `Profile/List/Items`, which hands it
on to `Profile/Item` and from there, with all arguments, to
`Profile/Item/Image`. The item image reads
`settings.image.{imageView}.cropVariant` and treats a missing `imageView` as
`list`. So the list, the selected profiles, the selected contracts and the
contacts element get the list crop variant without a change of their own.

A project copy of `Templates/Profile/Card.html` or `Profile/List/Items.html`
made before this change drops the argument, and so does a copy of
`Profile/Item.html` that renders the item image with arguments of its own
instead of all of them. Its card falls back to the list crop variant. The
changelog names all three, and both settings default to `default`.

Rejected: plugin specific TypoScript (`plugin.tx_academicpersons_card.settings`)
that sets one `settings.image.item.cropVariant` per element. Extbase merges it
over the shared block on both core versions, so it would survive a copied
template, and that is its real advantage. It was rejected because the choice
would then live in TypoScript that no template shows, every other element,
and the contacts element with its own plugin name, would need a line of its
own, and a value an integrator assigns to the shared block in setup would be
overridden by it without any sign. The template argument is visible where the
card is rendered and documented with the partial's other arguments.

Rejected as well: telling the elements apart by the `CType` of the content
element record, which a project that registers its own element around these
templates does not share.

### The placeholder keeps an empty alternative text

The first draft of the spec gave the placeholder the profile name as its
alternative text. The placeholder shows nobody, and the name is the heading
of the same card right next to it, so a screen reader would announce it twice
and describe a picture that is not there. The placeholder stays decorative
with an empty alternative text, as it renders today. The real image keeps
the alternative text of its file in the card and the names in the detail
view.

### The contacts element follows the persons settings

`academic_contacts4pages` renders `Profile/Item` of `academic_persons` with
its own plugin settings, and maps the persons placeholder constant into them
so that a contact looks like a listed profile. It maps the list crop variant
and the three gender placeholders the same way. Without that, a contact of
the gender `ms` would show the default placeholder while the same profile in a
list shows the `ms` one.

Rejected: leaving it out as the first draft did. That contradicts the existing
requirement that a contacts card shows the image exactly as the profile list
does.

### `placeholder.default` instead of `placeholder` plus `placeholder.<gender>`

The candidate proposed `image.placeholder` next to `image.placeholder.mr`. A
site settings tree cannot hold a value and children on the same node, so the
generic value becomes `image.placeholder.default`, and `mr`, `ms` and
`diverse` are siblings of it. These are the gender values of the profile TCA.

### Decided: one placeholder key, defaulting to the shipped SVG

`image.placeholder.default` is the only generic placeholder setting, and its
default is `EXT:academic_persons/Resources/Public/Images/ProfilePlaceholder.svg`.
`ace-646-responsive-image-partial` introduced that key with that default and
reads it. This change owns the `image.placeholder.*` family, adds `mr`, `ms`
and `diverse` with an empty default, and documents the group. An empty
default renders no image, unless a gender placeholder applies.

The two drafts declared two settings for one value with contradicting
defaults: a `profileImagePlaceholder` defaulting to the SVG and an
`image.placeholder.default` defaulting to empty. Several analysed projects and
the ACE demo add a placeholder of their own, so a visible default serves more
installations than an empty one.

### Gender resolution in Fluid

The partial picks `{settings.image.placeholder.{profile.gender}}` when the
profile has a gender and that setting is not empty, and falls back to
`{settings.image.placeholder.default}` otherwise. A profile without a gender
never builds the key, because `settings.image.placeholder.` with an empty last
segment is not a setting. That is a few lines of `f:variable` and `f:if`, and
it replaces the ViewHelpers the ACE demo and one other project wrote for it.

Rejected: a model getter or a ViewHelper. Both are PHP for a two-line choice,
and an override would need an XCLASS.

### Decided: widths stay in the presets of the responsive partial

There is no width site setting, neither for the list and card nor for the
detail view. List and card images take their widths from the `card` preset
of `ace-646-responsive-image-partial`, the detail image from its `detail`
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

`f:image` throws its view helper exception on a missing resource
(`cms-fluid/Classes/ViewHelpers/ImageViewHelper.php`). For an `EXT:` path to a
missing file the code differs by core version: TYPO3 v13 reports the
`InvalidArgumentException` of the file lookup as 1509741914, v14 a
`ResourceDoesNotExistException` of the fallback storage as 1509741911. The test
therefore asserts the exception class and the missing path, not the code. A FAL
placeholder that an editor deletes in the file list would therefore break
every list at runtime, while a missing `EXT:` file is a deploy error that a
functional test catches before release. Skipping an unresolvable placeholder
silently would need a ViewHelper, against the partial-only decision of the
responsive partial. The earlier draft of this change accepted FAL
identifiers as well. The accepted format is aligned with
`ace-646-responsive-image-partial`.

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
  setting, and no migration is needed.
- [The processed-file behaviour of the list and card changes] → That change
  came with `ace-646-responsive-image-partial` and its changelog, not with
  this one.
- [A project copy of `Card.html`, `Profile/List/Items.html` or
  `Profile/Item.html` drops `imageView`] → Its card uses the list crop
  variant. Both default to `default`, so nothing changes until an integrator
  sets the card one, and the changelog says so.
- [An image has no crop stored for the configured variant] → It is shown
  uncropped at its own ratio, whatever crop it has for `default`. That is
  every image the frontend editor of `academic_persons_edit` uploads, since
  it stores no crop area, and every image not opened and saved in the
  backend since `ace-744-named-crop-variants`. The documentation says so next
  to the settings.
- [An empty crop variant setting] → The image view helpers read an empty
  value as `default`, on both core versions, so it renders the `default`
  crop rather than none.

## Migration Plan

None for crop variants: their defaults reproduce today's output. Widths
changed with `ace-646-responsive-image-partial` and its changelog, not here.
The gender placeholders default to empty, so a profile without an image keeps
showing the default placeholder. Projects replace their image overrides with
the settings after upgrading. A project that referenced its placeholder as a
FAL file moves it into its site package and sets the `EXT:` path.

## Open Questions

None.
