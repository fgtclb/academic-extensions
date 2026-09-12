## Context

See `proposal.md` for the motivation. Verified in both installed core trees
(v13 `.Build/vendor/typo3`, 13.4.35; v14 `core-14/vendor/typo3`, 14.3.6):

- The category form data provider resolves `###SITE:<path>###` in
  `treeConfig.startingPoints`
  (`AbstractItemProvider::parseStartingPointsFromSiteConfiguration()`, called
  from `TcaCategory`; v13 `:938`, v14 `:1025`). The path is read with
  `ArrayUtility::getValueByPath($site->getConfiguration(), $path, '.')`, and
  `Site` merges its settings into that configuration under `settings`
  (`Site.php:119` on both).
- Values are cast to integers and empty entries dropped, and a missing path
  resolves to an empty string, so an unset setting keeps the whole tree.
- The `flexFormSegment` form data group runs `SiteResolving` before
  `TcaCategory` on both versions (`cms-core/Configuration/DefaultConfiguration.php`),
  so the marker resolves in a FlexForm field as well.

`academic_programs` ships no settings definitions today; `academic_jobs`
declares its settings on its aggregate set only.

## Goals / Non-Goals

**Goals:**

- One site setting drives both the page field and the plugin field.
- No PHP of our own; the core marker does the work.

**Non-Goals:**

- A generic setting shared with partners and projects; each extension gets
  its own (see the decision on the setting name).

## Decisions

### Use the core `###SITE:` marker

The page type gets
`types[20].columnsOverrides.categories.config.treeConfig.startingPoints =
'###SITE:settings.plugin.tx_academicprograms.categoryRootUids###'` in
`Configuration/TCA/Overrides/pages.php`. The FlexForm field
`settings.categories` gets the same marker below `treeConfig.startingPoints`.
Whether the FlexForm preparation still adds `parentField` once `treeConfig` is
set partially is checked by the functional test; if it does not, the field
states `parentField` explicitly.

Rejected: uids per environment in TCA or TSconfig, as two projects do today.
Rejected as well: a PSR-14 listener or an items processor, which would
re-implement what the core offers.

### Declare the setting on the aggregate set

`Configuration/Sets/Full/settings.definitions.yaml` declares
`plugin.tx_academicprograms.categoryRootUids` (type `string`, default `''`,
category `academic.programs`). This follows `academic_jobs`: a definitions
file sits next to exactly one `config.yaml`, and the two component sets
`fgtclb/academic-programs-program-list` and `-program-details` cannot both
declare one identifier.

Rejected: a type `int`. Several roots are a legitimate need, and the core
already splits and casts a comma-separated value.

### Decided: one setting per extension, keyed by the constant path

The setting is `plugin.tx_academicprograms.categoryRootUids`, read as
`###SITE:settings.plugin.tx_academicprograms.categoryRootUids###`; partners
and projects follow the same pattern when they get theirs. The roots differ
per record type (one project uses three different roots for programs,
partners and projects), so one shared key cannot express them, and the other
programs changes and the listings family use the constant-path keys of
academic_jobs rather than an `academicPrograms.*` scheme. `SiteSettings`
expands dotted keys, so the longer marker path resolves.

### Decided: an undefined setting still resolves

A value written to a site's `settings.yaml` without a settings definition
reaches `Site::getConfiguration()` on v13 and v14, so the `###SITE:` marker
resolves for a site that depends on a component set only. The definition is
needed for the settings editor, not for the marker.
`SiteSettingsProvider::getProvidedSettings()` keeps undefined values as
anonymous settings (no definition, not validated),
`SettingsFactory::resolveSettings()` maps them, `SiteSettings::create()`
rebuilds the dotted keys into a tree, and `Site::__construct()` merges it
into `configuration['settings']` (`Site.php:119`); the files are identical on
13.4.35 and 14.3.6. Not verified: whether saving through the settings editor
drops such an undefined key from `settings.yaml`; a functional test pins the
resolution without a definition, and the documentation names the open point.

## Risks / Trade-offs

- [A category assigned outside the root is not in the tree] → FormEngine may
  drop a value that is not among the tree items when the record is saved in
  the form. The functional test determines whether it does; if so, the
  changelog says so and advises setting the root before editors work.
- [A site that depends on a component set only] → It cannot edit the setting
  in the settings editor, but a value written to its `settings.yaml` resolves
  (see the decision above). Whether the settings editor keeps such a key on
  save is unverified; the documentation advises editing the file for these
  sites.
- [A uid from another tree] → The tree shows that branch; the documentation
  says the setting names the root of the program categories.

## Migration Plan

Nothing is migrated. The two projects set the site setting and delete their
TSconfig and TCA overrides.

## Open Questions

None.
