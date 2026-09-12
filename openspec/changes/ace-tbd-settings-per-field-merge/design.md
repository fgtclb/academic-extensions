## Context

See `proposal.md` for the motivation. State on `main`:

- `academic-base/Classes/Settings/SettingsFileLoader.php`:
  `loadMergedArray()` folds `loadPackageArrays()` with `array_merge()`, and
  the class doc says "the last package wins per top-level key, there is no
  deep merge".
- `AcademicPersonsSettingsFactory::get()` passes the merged array through
  `overlayLegacySettings()` into `normalize()`. The overlay relies on "the
  merged array carries the last package's value of each legacy key".
- `LegacySettingsStatus` and `MigrateSettingsCommand`
  (`academic:persons:settings:migrate`) walk `loadPackageArrays()`. The
  command folds them again with its own `array_merge()`.
- The shipped `Settings.yaml` has the top-level maps `profile` (layout keys
  `structure` and `details` next to the field definitions), `special`,
  `contracts` and `documentSections`. Its header comment documents the
  top-level replacement.
- `ace-tbd-settings-loader-deep-merge` (cross-cutting, ACE-109/ACE-161) makes
  the loader merge maps recursively, replace lists, and remove a key on
  `null`.

Evidence of the copies:

- ace-demo-12 copies all of `profile` for two validator lines (ACE-536);
- four other projects copy the maps to adjust the frontend validations and
  read-only flags;
- one of them also replaces `profileInformationsTypes`.

One analysed project ships no persons `Settings.yaml` copy.

## Goals / Non-Goals

**Goals:**

- A delta file is enough for every project above.
- The legacy overlay (ACE-504) keeps working until 4.0; while at most one
  package ships a legacy key, its input is unchanged.
- The effect of the breaking change is visible before anything breaks.

**Non-Goals:**

- Changing `normalize()`; only its input changes.

## Decisions

### Decided: consume the generic merge unchanged, `~` removes

Persons consumes the merge of `ace-tbd-settings-loader-deep-merge` as it is:
maps merge recursively, lists replace, and `~` (YAML null) removes an entry.
There is no replace-only parameter for `validations` and
`profileInformationsTypes`, and no merge of its own in the persons factory.
The legacy keys therefore merge like every other map.

The loader change already makes null the removal marker and explicitly
rejects special-casing the legacy keys. The shipped `Settings.yaml` carries
neither legacy key, so a replace-only rule would only matter where two
project packages ship the same legacy map. The first draft expected a
replace-only parameter from the loader, which the loader change does not
plan.

Rejected: persons deep-merging on its own from `loadPackageArrays()`. That
gives two merge rules in `academic_base` consumers, and `academic_jobs` uses
the same loader. Also rejected: the string `__UNSET` of
`ArrayUtility::mergeRecursiveWithOverrule()` as removal marker, which is a
second convention inside YAML.

### Decided: deep merge, not an explicit `overrides:` key

The persons maps are deep merged, as a breaking change. The non-breaking
`overrides:` key described below is not adopted.

The loader change already rejects the `overrides:` grammar for the shared
loader: it is a second grammar, and its dotted keys need escaping. Choosing it
here would contradict the loader this change builds on.

### Considered alternative: an explicit `overrides:` key (persons-data-09)

`persons-data-09` proposed an optional top-level `overrides:` map that is
applied with `mergeRecursiveWithOverrule()` after the unchanged top-level
merge, with `__UNSET` as the removal marker. It is not breaking: full copies
keep working, and a project opts in.

It is not chosen, for two reasons. It adds a second grammar next to the
top-level maps. And the five existing full copies keep drifting silently,
because nothing forces them to shrink.

### Decided: order follows the loader's rule, no reorder mechanism

The order of merged maps is the rule of `ace-tbd-settings-loader-deep-merge`:
a map that restates every key of the earlier map takes the later order;
otherwise the earlier order stays and new keys are appended. For the persons
maps that means upstream field order stays and project fields follow, and a
project that needs another order restates every key of that map. There is no
further mechanism for reordering, and it is documented rather than solved.

The public display order does not depend on map order at all: it comes from
the `profile.structure` and `profile.details` lists, which replace as a
whole. What a restatement reorders is the order of the field definitions,
for example in the frontend editor forms.

The documentation warns about one trap of this rule: an empty map `{}`
parses to an empty array, counts as a list, and therefore replaces the entry
instead of leaving it alone. `~` is the only way to remove an entry, and
leaving the key out is the way to keep it.

### Status report and delta command

`LegacySettingsStatus` compares each package array with the merge of the
packages before it. It reports entries set to `~` and upstream entries a
copied map omits, so the breaking effect is visible in the Reports module
before an integrator notices a reappearing field.

`MigrateSettingsCommand` gets a `--delta` option that prints the minimal
file per package, writing `~` for omitted entries. Its fold switches from
`array_merge()` to the loader's merge. The default behaviour and exit codes
stay.

Rejected: a separate command. The migration command is already the place
integrators are sent to after the 3.0 upgrade.

## Risks / Trade-offs

- [Merge rules are invisible] → The status entry and `--delta` show the
  effective result per package.
- [Reappearing fields after the upgrade] → The `Breaking-` changelog gives
  the recipe (`~`) and names the pattern of dropping a field by omission.
- [Two packages ship the same legacy key] → Their maps are merged instead of
  the last one winning. The `Breaking-` changelog names it; the recipe is the
  same `~`.
- [Dependency on another change's loader] → Task 1.1 verifies the merge rules
  in the merged loader before any code here is written.

## Migration Plan

1. Upgrade and flush caches.
2. Check the persons settings entry in the Reports module.
3. Run `academic:persons:settings:migrate --delta`, and replace each copied
   file with the printed delta.
4. Flush caches.

Rollback: restore the previous files; a full copy produces the same result
under both rules, except for the entries it omits.

## Open Questions

None.
