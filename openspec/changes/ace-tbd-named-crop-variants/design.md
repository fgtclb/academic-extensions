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
  compatibility in 13.4.35. That was removed in 14.3.6 (#105855). The v14
  changelog `Feature-105742` configures variants through
  `overrideChildTca.columns.crop.config.cropVariants`, and that path exists on
  both versions.

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

### `default` first, copied from core

`default` repeats the ratios core offers without configuration, and is listed
first. Stored crop data is keyed by variant name, so an existing `default`
crop keeps its meaning. The definition is taken from the core default of both
versions (task 1.1) instead of being retyped.

### Decided: doktype 40 keeps only the free `default`

Partner page media is the partner logo: the partner list and both partnership
items render it, with the `logo` preset of `ace-tbd-image-partial-adoption`,
and ACE-572 asks for free-ratio logos. Doktype 40 therefore gets no crop
variant configuration and keeps the core default. Adding `landscape` and
`portrait` later is non-breaking, while removing them later would orphan crop
data editors had already stored. Rejected: the three variants of doktypes 20
and 30, as first proposed.

## Risks / Trade-offs

- [A project defines the same variant names; its TCA loads later and wins per
  key, while upstream variants it lacks appear in addition] → The Important
  changelog shows how to hide a variant with TCEFORM
  `cropVariants.<name>.disabled = 1`.
- [Core changes its default ratios in a later version] → The functional test
  compares `default` with the core default of the installed version.

## Open Questions

None.
