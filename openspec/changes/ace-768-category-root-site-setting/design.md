## Context

See `proposal.md` for the motivation. Verified in both installed core trees
(v13 13.4, v14 14.3.7), and by the functional test on both:

- The category form data provider resolves `###SITE:<path>###` in
  `treeConfig.startingPoints`
  (`AbstractItemProvider::parseStartingPointsFromSiteConfiguration()`, called
  from `TcaCategory`, v13 `:938`, v14 `:1019`). The path is read with
  `ArrayUtility::getValueByPath($site->getConfiguration(), $path, '.')`, and
  `Site` merges its settings into that configuration under `settings`
  (`Site.php:119` on both).
- Values are cast to integers and empty entries dropped, and a missing path
  resolves to an empty string. **Corrected during implementation:** that does
  not keep the tree as it is. An empty value leaves `startingPoints` empty, a
  text becomes `0`, and a record outside of every site keeps the literal
  marker, which `TreeDataProviderFactory` reads as `0`. In every case
  `startingPoints` is set without a comma, and the factory then sets
  `nonSelectableLevels` to `''`, so the top node of the whole tree becomes
  selectable. Without `startingPoints` it is not.
- The `flexFormSegment` form data group runs `SiteResolving` before
  `TcaCategory` on both versions (`cms-core/Configuration/DefaultConfiguration.php`),
  so the marker resolves in a FlexForm field as well.

**Corrected during implementation:** `academic_programs` already declares
eight settings in `Configuration/Sets/Full/settings.definitions.yaml` (since
ACE-726 and later changes), so the setting is added there. The program finder,
merged after this change was planned (ACE-91), has a category field of its own,
`settings.preselectedCategories`, and gets the same root.

`TcaCategory::initializeDefaultFieldConfig()` adds `parentField` for every
category field, so a FlexForm field that sets only `startingPoints` keeps it.

## Goals / Non-Goals

**Goals:**

- One site setting drives the page field and the two plugin fields.
- A site without a category uid in the setting sees the fields exactly as
  before.

**Non-Goals:**

- A generic setting shared with partners and projects. Each extension gets
  its own (see the decision on the setting name).

## Decisions

### Decided: the core marker as declaration, resolved by a form data provider

The page type gets
`types[20].columnsOverrides.categories.config.treeConfig.startingPoints =
'###SITE:settings.plugin.tx_academicprograms.categoryRootUids###'` in
`Configuration/TCA/Overrides/pages.php`, and the FlexForm fields
`settings.categories` of the list and `settings.preselectedCategories` of the
finder get the same marker below `treeConfig.startingPoints`.

The form data provider `CategoryTreeRoot` replaces the marker before
`TcaCategory` resolves it: with the positive uids of the setting, read from
`Site::getConfiguration()` at the path the marker names and in the same forms
core accepts (a comma separated string, an integer or a list), or by removing
`startingPoints` when there are none. It is registered after `SiteResolving`
and `TcaColumnsOverrides`, which brings the marker of a program page, and
before `TcaCategory` in
`tcaDatabaseRecord`, `flexFormSegment` and `tcaSelectTreeAjaxFieldData`. The
tree items are loaded by `FormSelectTreeAjaxController` through the last group,
and a FlexForm field of that request through `flexFormSegment`.
`tcaInputPlaceholderRecord` also runs `TcaCategory` but resolves no site, so the
marker falls back to core resolution there, which is harmless for a
placeholder.

The maintainer chose this over two alternatives when the selectable top node
showed up (see Context):

- Rejected: the marker alone. Every site without the setting would get a
  selectable top node in three trees.
- Rejected: the marker plus `treeConfig.appearance.nonSelectableLevels = 0`.
  No PHP, but a single configured root could no longer be selected itself,
  unlike the page TSconfig the projects use today.

Rejected as before: uids per environment in TCA or TSconfig, and an items
processor.

### Declare the setting on the aggregate set

`Configuration/Sets/Full/settings.definitions.yaml` declares
`plugin.tx_academicprograms.categoryRootUids` (type `string`, default `''`,
category `academic.programs`) next to the eight settings it already declares:
a definitions file sits next to exactly one `config.yaml`, and the component
sets cannot all declare one identifier. The setting is read by the backend
only, so unlike the other settings of that file it has no TypoScript constant,
and the file comment names the exception.

Rejected: a type `int`. Several roots are a legitimate need, and the core
already splits and casts a comma-separated value.

### Decided: one setting per extension, keyed by the constant path

The setting is `plugin.tx_academicprograms.categoryRootUids`, read as
`###SITE:settings.plugin.tx_academicprograms.categoryRootUids###`. Partners
and projects follow the same pattern when they get theirs. The roots differ
per record type (one project uses three different roots for programs,
partners and projects), so one shared key cannot express them, and the other
programs changes and the listings family use the constant-path keys of
academic_jobs rather than an `academicPrograms.*` scheme. `SiteSettings`
expands dotted keys, so the longer marker path resolves.

### Corrected: an undefined setting resolves only when written as a tree

A value written to a site's settings without a settings definition reaches
`Site::getSettings()` and `Site::getConfiguration()` on v13 and v14, but
`SiteSettingsProvider::getProvidedSettings()` flattens such undefined values
with `ArrayUtility::flattenPlain()`, which escapes the dots of a key. A nested
`plugin: tx_academicprograms: categoryRootUids: '7'` is therefore found under
its path, and a flat `plugin.tx_academicprograms.categoryRootUids: '7'` is not.
The functional test pins both, and the documentation shows the nested form.
Saving through the settings editor keeps such an undefined key:
`SettingsDiff::create()` starts from the stored settings and changes only the
submitted keys. A list written in YAML stays a list and is accepted like core
accepts it, without its order, which the tree does not need.

## Risks / Trade-offs

- [A category assigned outside the root is not in the tree] → The form starts
  with the value (tested), so saving without touching the tree keeps it. The
  tree writes the field from its own selection as soon as the editor changes
  it (`select-tree-element.js`, `saveCheckboxes()`), which drops the category
  without notice. The documentation says so and advises setting the root
  before editors work.
- [A project keeps its page TSconfig] → `TcaCategory` applies
  `TCEFORM.<table>.<field>.config.treeConfig.startingPoints` after the
  provider, so page TSconfig wins until it is removed (tested).
- [A site that does not depend on the aggregate set] → It cannot edit the
  setting in the settings editor, and a value written to its `settings.yaml`
  resolves in the nested form only (see above). The documentation shows that
  form for these sites.
- [A uid from another tree] → The tree shows that branch. The documentation
  says the setting names the root of the program categories.

## Migration Plan

Nothing is migrated. The two projects set the site setting and delete their
TSconfig and TCA overrides.

## Open Questions

None.
