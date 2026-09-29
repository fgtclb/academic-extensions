# Crop variants

Templates request an image crop by the name of its variant. Three extensions
configure named variants on the images a template of a site package is most
likely to crop. The templates of the extensions themselves render `default`
through the [image partial](shared-partials.md) of `academic_base`, apart from
`academic_persons`, which renders the variant a site setting names per view.

| Image                          | Extension           | Variants and aspect ratios                         |
|--------------------------------|---------------------|----------------------------------------------------|
| Profile image                  | `academic_persons`  | `default` (free), `square` 1:1, `portrait` 3:4     |
| Media of a program page (20)   | `academic_programs` | `default` (free), `landscape` 16:9, `portrait` 3:4 |
| Media of a project page (30)   | `academic_projects` | `default` (free), `landscape` 16:9, `portrait` 3:4 |
| Media of a partner page (40)   | `academic_partners` | none configured: TYPO3's `default`                 |
| Media of every other page type | —                   | none configured: TYPO3's `default`                 |

`default` offers the ratios free, 16:9, 3:2, 4:3 and 1:1 wherever it appears.

## Where the variants are configured

On the file field, in `overrideChildTca.columns.crop.config.cropVariants`,
which FormEngine applies to each file reference of the field. For the profile
image that is the column itself. For the page media it is the
`columnsOverrides` of the page type, so the media of a standard page keeps what
TYPO3 offers.

Two alternatives were rejected. A crop configuration on `sys_file_reference`
itself reaches the images of every extension and of the site package. Page
TSconfig (`TCEFORM.sys_file_reference.crop.config.cropVariants`) applies only
where a site loads it, while a template relies on the name everywhere.

`columnsOverrides` is merged into the field with `array_replace_recursive()`,
on TYPO3 v13 and v14 alike. That matters on TYPO3 v13, where `pages.media`
still carries `overrideChildTca.types` for backwards compatibility; the
merge keeps it. TYPO3 v14 removed that entry. No version switch is needed.

## `default` is written out, not derived

The cropper offers a built-in `default` variant only while a field configures
none. As soon as one variant is configured, the built-in one is gone, so each
configuration repeats it: same name, same ratios, free ratio selected, full
crop area. Crop data is stored per variant name, so a crop an editor stored
before the change keeps its meaning.

The built-in definition is a protected static property of
`ImageManipulationElement`, identical on TYPO3 v13 and v14 apart from the
v14-only `excludeFromSync => false`. A missing `excludeFromSync` means `false`
on TYPO3 v14, so the copies leave it out. Each copy is compared with the
built-in variant of the installed core by a test, so a core that changes its
default turns the test red instead of drifting.

## Partner logos keep the free crop

The media of a partner page is the logo of the partner: the partner list and
both partnership views render it with the `logo` preset of the image partial,
and ACE-572 asks for logos in their own ratio. The partner page type therefore
configures no variant. Adding variants later is not breaking; removing them
later would orphan crops editors had stored.

## What an image carries

The cropper writes a crop for every configured variant into the form when the
file reference renders expanded, so saving the record stores all of them. It
starts a variant from a stored crop in two ways, in
`CropVariantCollection::create()`:

- a variant with a stored crop of its name takes that crop;
- a variant without one takes the stored crops in their stored order, starting
  with the first, even one another variant already took by name, and keeps its
  own area, the whole image, once none is left.

Each area is then fitted into the ratio of its variant, centred, provided the
metadata of the file has a width. On an image cropped before the update that
means: `default` keeps its crop, the first new variant (`square`, `landscape`)
starts from the `default` crop fitted into its ratio, and `portrait` starts
from the whole image.

A file reference that no editor has opened and saved since the variants were
added carries no crop for them, and `CropVariantCollection::getCropArea()`
answers a missing variant with the full image. A template that requests
`portrait` therefore renders such an image uncropped. That includes images
written without the backend form: imports, and the profile images the frontend
editing of `academic_persons_edit` uploads.

## How a project changes them

- **Disable a variant in TCA**, at the path the extension uses, with
  `['<name>']['disabled'] = true`. The cropper skips it on that field only.
- **Not with page TSconfig.** `FormEngineUtility::overrideFieldConf()` lets
  `TCEFORM.sys_file_reference.crop.config.cropVariants` through for the cropper
  and merges it in with added keys, for every file reference below the page. On
  a field that configures no variants, `cropVariants.portrait.disabled = 1` then
  becomes the only configured variant, the cropper drops its built-in
  `default`, skips the disabled one, and is left with none.
- **Replace or add a variant in TCA**, at the same path. A variant set by name
  replaces the one of that name; assigning the whole array replaces all.
- **Configuration elsewhere is merged, not replaced.** Variants on `pages.media`
  for every page reach the program and project pages through
  `columnsOverrides`, and variants on `sys_file_reference.columns.crop` reach
  every image through `overrideChildTca`. Both merges are
  `array_replace_recursive()`: the extension's values win key by key, and a
  ratio the project adds to a variant of the same name stays. A project that
  restricted `default` to one ratio that way gets all the ratios of TYPO3's
  `default` back on these images.

## Tests

`CropVariantsAssertionTrait` of the [testing helper](../testing/testing-helper.md)
compiles the record form, expands the file reference, and hands its crop
configuration, its stored crop and its file to the cropper element. The tests
assert what the cropper offers, not what `$GLOBALS['TCA']` holds:

- `academic-persons/Tests/Functional/Backend/FormEngine/ProfileImageCropVariantsTest.php`
- `academic-{programs,projects}/Tests/Functional/Backend/FormEngine/PageMediaCropVariantsTest.php`,
  which also assert that the page type adds nothing but the variants to the
  media field — on TYPO3 v13 that is the `overrideChildTca.types` entry — and
  that the media of a standard page keeps TYPO3's `default`.
- `academic-partners/Tests/Functional/Backend/FormEngine/PageMediaCropVariantsTest.php`
  for the partner logo.

Each variant set is compared with the built-in `default` of the installed core.
A stored crop in a ratio the cropper does not offer has to keep its area, and the
start areas of the new variants are asserted, which pins the order-based mapping
above. The persons test also disables a variant in TCA.

## See also

- [Shared partials](shared-partials.md) — the image partial that renders the
  variants.
- [Testing helper](../testing/testing-helper.md) — `CropVariantsAssertionTrait`.
- [Core version aware code](core-version-aware-code.md) — why a difference
  like the v13 `overrideChildTca.types` needs no switch here.
