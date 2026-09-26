## Context

Verified on `main` and in both vendor trees:

- No `cropVariants` anywhere below `packages/fgtclb/*/Configuration`.
- The profile `image` column is a plain `type => file`
  (`academic-persons/Configuration/TCA/tx_academicpersons_domain_model_profile.php:250`).
- The page types are 20 (programs), 30 (projects) and 40 (partners), from each
  extension's `Classes/Enumeration/PageTypes.php`. All three copy the showitem
  of the standard page, `media` included. academic_projects already writes
  `types[30]['columnsOverrides']`
  (`academic-projects/Configuration/TCA/Overrides/pages.php:172-176`).
- The partner list renders `partner.media`, so the doktype 40 media is the
  partner logo.
- Core `pages.media` still carries `overrideChildTca.types` for backwards
  compatibility in 13.4.34. It was removed in 14.0 (#105855). The v14
  changelog `Feature-105742` configures variants through
  `overrideChildTca.columns.crop.config.cropVariants`, and that path exists on
  both versions.
- The built-in `default` variant of the cropper
  (`ImageManipulationElement::$defaultConfig`) is identical on 13.4.34 and
  14.3.7 apart from `excludeFromSync => false`, which only v14 knows. A missing
  `excludeFromSync` is read as `false` there. The cropper drops the built-in
  variant as soon as a field configures one.
- FormEngine compiles a collapsed file reference with the columns of its title
  only; the crop field is compiled once the reference is expanded. Rendering
  it writes a crop for every configured variant into the form, so saving the
  record stores all of them. `CropVariantCollection::create()` starts a
  variant without a stored crop of its name from the stored crops in their
  stored order, starting with the first, even one another variant took by
  name, and from the whole image once none is left; each area is fitted into
  its ratio, centred: on an image cropped before, the first new variant starts
  from the `default` crop. A reference without a crop for a variant renders
  uncropped in the frontend (`CropVariantCollection::getCropArea()` answers
  with the full image).
- `FormEngineUtility::overrideFieldConf()` lets page TSconfig
  `TCEFORM.sys_file_reference.crop.config.cropVariants` through for the cropper,
  merged with added keys. On a field without variants, a `disabled` entry
  becomes the only variant, and the cropper is left with none.

## Goals / Non-Goals

**Goals:**

- The names the projects already use, with the ratios the projects chose.

**Non-Goals:**

- `excludeFromSync` or other v14-only cropper options.

## Decisions

### Crop variants through `overrideChildTca`, per column and page type

The profile `image` column gets
`config.overrideChildTca.columns.crop.config.cropVariants`. The page media gets
the same fragment in
`types[20|30]['columnsOverrides']['media']['config']`. FormEngine merges
`columnsOverrides` recursively onto the column, so the v13 `overrideChildTca`
`types` entry survives and no version switch is needed. Rejected: a crop
configuration on `sys_file_reference`, which changes the images of every
other extension. Rejected: page TSconfig (`TCEFORM`), which only applies where
a site loads it, while the templates rely on the names everywhere.

### `default` first, written out as core has it

`default` repeats the ratios core offers without configuration, and is listed
first. Stored crop data is keyed by variant name, so an existing `default`
crop keeps its meaning. The definition is written out in each of the three
TCA files, without the v14-only `excludeFromSync`. Rejected: reading the
protected core property at TCA load time, and a shared helper in
academic_base for one array. Each copy is instead compared with the built-in
variant of the installed core by a test.

### Tests read what the cropper offers

The tests compile the record form as FormEngine does, expand the file
reference, and pass its crop configuration, its stored crop and its file
through the two protected methods of the cropper element that resolve them. An
assertion on `$GLOBALS['TCA']` would check the configuration before the two
merges this change relies on, `columnsOverrides` and `overrideChildTca`, and
before the cropper drops its built-in variant. The helper is a trait of the
testing helper package, as four extensions use it.

### Decided: doktype 40 keeps only the free `default`

Partner page media is the partner logo: the partner list and both partnership
items render it, with the `logo` preset of `ace-710-image-partial-adoption`,
and ACE-572 asks for free-ratio logos. Doktype 40 therefore gets no crop
variant configuration and keeps the core default. Adding `landscape` and
`portrait` later is non-breaking, while removing them later would orphan crop
data editors had already stored. Rejected: the three variants of doktypes 20
and 30, as first proposed.

## Risks / Trade-offs

- [A project defines the same variant names on the same field; its TCA loads
  later and wins per key, while upstream variants it lacks appear in addition]
  → The Feature changelog and the configuration chapter show how to disable a
  variant in TCA on the field, `['<name>']['disabled'] = true`, and warn
  against page TSconfig, which reaches every image below the page.
- [A project configured its variants on `pages.media` for every page, or on
  `sys_file_reference.columns.crop` for every image] → Both are merged with the
  extension's with `array_replace_recursive()`: the extension's values win key
  by key, and ratios the project added stay. A `default` a project restricted
  to one ratio gets TYPO3's ratios back on these images. Documented.
- [An image nobody opened in the backend since the update has no crop for the
  new variants] → A template requesting one renders it uncropped. This includes
  imports and the images the frontend editing uploads; documented.
- [Core changes its default ratios in a later version] → The functional test
  compares `default` with the core default of the installed version.

## Open Questions

None.
