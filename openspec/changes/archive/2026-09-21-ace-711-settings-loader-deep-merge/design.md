## Context

See `proposal.md` for the motivation. The current state on `main`:

- `SettingsFileLoader::loadMergedArray()`
  (`packages/fgtclb/academic-base/Classes/Settings/SettingsFileLoader.php:61-68`)
  folds `loadPackageArrays()` with `array_merge()`, and the class docblock
  says "there is no deep merge". The class is `@internal`, final and stateless.
- The only consumer of the merged array is
  `AcademicPersonsSettingsFactory::get()` through `load()`.
  `LegacySettingsStatus` and `MigrateSettingsCommand` read
  `loadPackageArrays()`, the per-package view.
- Map order is display order in the persons file. The comment block of
  `academic-persons/Configuration/AcademicPersons/Settings.yaml` says so for
  the `profile` entries, `special.<component>.fields`, `contracts.fields`,
  `contracts.contactSections` and `documentSections`. The lists in it are the
  flag lists of each field and `profile.structure.<column>`.
- The shipped file contains no `null` value, so giving `null` a meaning
  changes no shipped configuration.
- The top-level merge is documented in
  `academic-persons/Documentation/Configuration/Sections/Index.rst` (the "top
  level only" paragraph), `Validations/Index.rst` and
  `docs/architecture/validation-settings.md` ("Restate the whole top-level
  map"), and pinned by `SettingsFileLoaderTest::aLaterPackageReplacesTheWholeTopLevelKey()`.

## Goals / Non-Goals

**Goals:**

- A project file states only what differs from upstream.
- A copy that is complete at every depth, key order included, keeps giving
  exactly its current result.
- The loader stays generic: it knows nothing of the persons schema.

**Non-Goals:**

- Any change to `loadPackageArrays()`, the cache round trip or the normaliser.

## Decisions

### Merge in the loader, not in the persons factory

`loadMergedArray()` folds the package arrays with a private recursive merge
helper in the same `@internal` class. Rejected: merging in
`AcademicPersonsSettingsFactory`. Every further settings file of the generic
settings system (ACE-109, ACE-161) would repeat it.

### Lists replace, maps merge, told apart with `array_is_list()`

A PHP list (a YAML sequence) from a later package replaces the earlier value;
two maps merge; any other pair of types (list and map, scalar and array) is
replaced by the later value. An empty sequence is a list, which is how a flag
list is cleared. Rejected: merging lists by value, since a project could never
drop `required` from `[required]`; and merging by position, which mixes two
orders silently. A YAML map whose keys happen to be `0 … n-1` counts as a
list. The persons file has no such map; the documentation says so.

### `null` removes the key

Rejected: a reserved `remove: true` key as in `CategoryTypes.yaml`. In a
free-form schema a reserved key collides with a field named `remove`, while
`null` has no other meaning in these files.

### `null` is dropped at any depth, not only where an earlier package configured the key

A `null` means "not configured", so it is removed whether or not an earlier
package named the key. The alternative - keeping a `null` that only one package
ships, because there is nothing to remove - would make the meaning of `~` depend
on which packages are installed, and hand the normaliser a value the merge
promises it never sees.

### Key order follows a complete restatement

When the later map names every key of the earlier map, the result takes the
later order; otherwise the earlier order stays and new keys are appended. The
rule applies at every level. Rejected: always the earlier order
(`array_replace_recursive()`), which would silently change the display order
of every reordered full copy. Rejected: always the later order, which moves a
single overridden key to the front.

### Rejected: an `overrides:` key with dotted paths

The persons data family proposed an `overrides:` block with dotted per-field
keys (candidate `persons-data-09`, for example `profile.title.required:
false`). It is a second grammar next to YAML, needs escaping for keys that
contain dots, and does not help the maps projects already copy. The recursive
merge gives the same result with the plain file structure.

### Legacy keys are not special-cased

`LegacySettingsMigrator::LEGACY_KEYS` (`validations`,
`profileInformationsTypes`) are no longer shipped upstream. With the recursive
merge, two project packages that both still ship one contribute a merged map
instead of the last one. The migrator docblock ("carries the last package's
value") is corrected. Rejected: special-casing them in the generic loader, for
keys that go away in 4.0.

### Decided: maps merge, lists replace, `null` removes

The merge semantics stay as designed above: maps merge recursively, a list
replaces the earlier list as a whole, and `null` (`~` in YAML) removes a key.
The validators of the persons settings file are plain flag lists
(`required`, `email`, `url`) with no identifier to merge by, and a union of
lists would make dropping `required` impossible.
`ace-tbd-settings-per-field-merge` builds its `~` removal recipe on exactly
these semantics.

### Decided: no replace-only keys in the loader

The loader gets no parameter for per-extension replace-only keys, and
`ace-tbd-settings-per-field-merge` no longer expects one. The shipped persons
settings file carries neither legacy key, so a replace-only rule would only
matter when two project packages both ship a legacy map, which the merge
already handles by combining them.

### Decided: tracked under the generic settings system

The issue is filed as a sub-issue of ACE-109, with ACE-161 linked to it as
the duplicate. The loader this change touches is the generic part of that
settings system. Both keys are verified in YouTrack before the change is
renamed to `ace-<NNN>-settings-loader-deep-merge`.

## Verified while implementing

Every premise of the Context section was re-read on `main` at `6e1b29bd9`:

- `SettingsFileLoader::loadMergedArray()` folded with `array_merge()` and the
  class docblock said "there is no deep merge". Both are replaced.
- `AcademicPersonsSettingsFactory::get()` is the only consumer of the merged
  array; `LegacySettingsStatus` and `MigrateSettingsCommand` read
  `loadPackageArrays()`, which is untouched.
- The shipped persons settings file contains no `null` value and no map with
  consecutive integer keys, measured by walking the parsed file. Giving both a
  meaning therefore changes no shipped configuration.
- `academic_jobs` still folds its own settings file with `array_merge()` in
  `AcademicJobsSettingsLoader`. It has a loader of its own and is out of scope;
  ACE-508 is where the two implementations meet.

## Added while implementing

Six manual and changelog pages claimed the shallow merge beyond the three the
change named, and are corrected with it - together with the comment blocks and
docblocks of the last bullet:

- `academic-persons/Documentation/Upgrade/Index.rst`, step 6: the migration
  command prints every key the overlay produced, so it carries what the
  installation runs on today, and reducing the override to its deltas is a
  step of its own - after the omissions it inherits from now on are settled.
- `academic-persons/Documentation/Changelog/3.0/Breaking-SectionBasedAcademicPersonsSettings.rst`,
  migration step 2 ("Keep every map the override declares complete").
- `academic-persons/Documentation/Changelog/3.0/Feature-ConfigurablePublicProfile.rst`,
  which told an integrator to repeat `structure` and `details` completely. The
  maps merge now; the lists inside them are still replaced as a whole.
- `academic-persons/Documentation/Changelog/3.0/Feature-ConfigurableDocumentRowsAndActions.rst`,
  which said an override restates `rowFields` and `actions` for every section
  it declares.
- `academic-base/Documentation/Changelog/3.0/Feature-SharedValidationSettings.rst`,
  the changelog entry of the loader itself.
- `academic-persons/Documentation/Changelog/3.0/Important-ValidationPrimitivesMovedToAcademicBase.rst`,
  which promised "the package walk and top-level merge … are all as before".
- The comment block of the shipped `Configuration/AcademicPersons/Settings.yaml`,
  `academic-persons-edit/Documentation/Configuration/Settings/Index.rst`, and the
  class docblocks of two test classes and two test fixture extensions.

`MigrateSettingsCommand` folds the package arrays itself, with an
`array_merge()` of its own, so it printed maps the runtime would not produce as
soon as two packages before the legacy one configured the same top-level key
partially. `SettingsFileLoader::merge()` is therefore public - the class stays
`@internal` - and the command folds with it, both for the section maps and for
the legacy keys it overlays. Its docblock no longer claims agreement by
accident; it says the two views differ only in how far the package list has
been walked.

The command is pinned by a unit test rather than a functional one, because the
defect only shows for a specific package order and `getActivePackages()` does
not follow the order a functional test lists its fixture extensions in. The
partial-override fixture it shares with `AcademicPersonsSettingsFactoryTest`
is therefore a plain package directory under `Tests/Unit/Fixtures/Packages/`,
not a fixture extension - only `test_legacy_settings` has to be a real
extension, because functional tests load it.

Two functional test fixtures existed only because of the shallow merge and are
migrated with it: `test_contract_contact_actions` of `academic_persons_edit`
restated the whole `contracts` map to take its `actions` away and is now the
one list it changes - its create posts the fields the shipped settings require
- and `test_public_profile_settings` of `academic_persons` keeps its two
layout keys while the editable fields it used to erase now survive.

## Risks / Trade-offs

- [A project removed upstream entries by leaving them out; one analysed
  project drops `streetNumber`, `country` and `middleName` that way] → The
  Breaking changelog shows the `null` migration with a before and after
  example.
- [A copy that is complete on the top level but omits a key **inside** a
  restated entry inherits that key, which the first draft of the changelog
  called unaffected. The manual's own recipe for unlocking the profile names
  has that shape: it restates every field of `profile` and leaves `validators`
  off the three name fields, so they would silently stay locked] → The Breaking
  entry names exactly that recipe, tells the reader to compare key by key
  rather than map by map, and gives `validators: []` next to the `~` removal.
  `Documentation/Configuration/Validations/Index.rst` ships the new recipe.
- [A reordered full copy decides the display order only while it names every
  key, so the next upstream addition hands the order back to upstream] → An
  `important` box next to the order rule in the Breaking entry and a paragraph
  in `docs/architecture/validation-settings.md`. The rule is kept: always the
  earlier order loses every project's order at once, always the later order
  moves a single overridden key to the front.
- [List and map are told apart wrongly] → Unit tests for an empty list, a
  list against a map, and a map with integer keys.
- [A later persons-display change builds its settings examples on this merge
  (candidate `persons-display-15`)] → That change documents the persons
  examples; this one documents the rules only.

## Migration Plan

1. Deploy and flush caches; the merged settings are rebuilt.
2. Projects reduce their copies to the lines that differ, and set every
   entry they want removed to `null`.
3. Rollback is a code revert. A complete copy gives the same result under both
   versions, so a project that has not reduced its copy yet is unaffected.

## Open Questions

None.
