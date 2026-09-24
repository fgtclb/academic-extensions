## Context

See `proposal.md` for the motivation. State on `main` at `7730c7995`:

- `academic-base/Classes/Settings/SettingsFileLoader.php` folds the package
  arrays with its public `merge()` since ACE-711: two maps merge key by key, a
  value that is not a map replaces, `null` removes a key, and the key order is
  the later one only when the later map names every key of the earlier one.
  `loadPackageArrays()` returns the file of every package, keyed by package and
  in loading order.
- `AcademicPersonsSettingsFactory` hands the merged array through
  `overlayLegacySettings()` into `normalize()`. The overlay reads the combined
  legacy maps of every package that ships one.
- `LegacySettingsStatus` reports one warning per package that ships a legacy
  key, and one OK status when none does. Its title label is already "Settings
  overrides".
- `MigrateSettingsCommand` folds the packages with `SettingsFileLoader::merge()`
  and prints the migrated section maps of every legacy package; it exits with 1
  when there is one.
- The shipped `Settings.yaml` header, `Documentation/Configuration/Sections`,
  `Validations`, `docs/architecture/validation-settings.md` and
  `Breaking-SettingsFilesMergeRecursively.rst` describe the per-entry merge.

Everything the first draft of this change planned for the merge is therefore
in place, and so are most of its tests: the loader tests cover lists, `~`, `{}`
and both order rules, the factory test covers a partial override, and
`test_public_profile_settings` is a delta-only fixture.

## Goals / Non-Goals

**Goals:**

- An integrator sees, per package, what its file removes and what its copied
  maps leave out, without reading YAML side by side.
- A copied file shrinks to its delta by pasting what the command prints, with
  nothing changing in the installation.

**Non-Goals:**

- Changing the merge, the normaliser or the legacy overlay.
- Writing files.

## Decisions

### Decided: one comparison, used by the report and the command

A stateless service compares each package array with the merge of the
packages loaded before it and returns, per package, the delta, the removed
entries and the omitted entries. The report and the command both walk
`loadPackageArrays()` and hand it to that service, so they cannot disagree. It
folds with `SettingsFileLoader::merge()`, the rule the runtime uses.

The first package that ships the file has nothing to be compared with and is
skipped - on an installation that is academic_persons itself.

### Decided: the delta reproduces today's effective settings exactly

The delta of a package is what differs from the packages before it:

- an entry equal to the earlier one is left out;
- two maps are compared entry by entry; a map whose comparison is empty is
  left out;
- a map against a list, a scalar or nothing is kept as it is, because it
  replaces;
- a `~` is kept where an earlier package has the key, and left out where none
  has, where it removes nothing;
- a map whose nested delta would be a list - possible with integer keys - is
  kept as it is, because a list replaces;
- where leaving entries out would change the key order of the merged map - a
  map that names every earlier entry decides the order, and dropping its
  unchanged entries would hand the order back to the earlier map - the map
  keeps every entry it names, each reduced to its own delta or, where that is
  empty, kept as it is. A partial map decides nothing, and its new entries
  are their own delta.

The invariant, pinned by the tests: merging the delta onto the earlier
packages gives the same array, key order included, as merging the package's
own file. The integrator can therefore replace the file with the delta without
any effect.

Rejected: writing `~` for every omitted entry, which reproduces what a copy
meant under the old top-level merge. It also removes every entry upstream
added after the copy was made, silently, which is the drift the merge exists
to end. Decided with the maintainer.

### Decided: an omission is reported where the package restated a map

Below the top level, a map is treated as a copy when the package restates at
least two of its earlier entries unchanged, or when the map is part of a copy:
while the files were merged per top-level key, a copied map replaced
everything below it. Unchanged means a subset in any
key order: a restated field without a key upstream added after the copy was
made, or with its keys sorted differently, still counts, because that drift is
what the report is for. Each entry of the earlier map that such a copy leaves
out is an omitted entry: the package inherits it now, and under the top-level
merge of the 3.0 development state it was gone. Maps both name are
compared at every depth, so a restated field that leaves out its `validators`
is found as well - the trap of the ACE-711 review.

A map that changes only what it names is a delta and reports nothing. The top
level is never a copy: the top-level merge never removed an unnamed top-level
map either.

The rule is a heuristic, and it is stated as one. A delta that restates one
entry by mistake is not a copy; one that restates two is, and reports the
siblings it leaves out. The delta command drops the redundant entries either
way.

Rejected, after review: a copy on one entry restated unchanged - with a subset
rule it makes every parent of a partly restated field a copy, and with an exact
rule (`===`) it misses every copy made before upstream added a key to its
fields. Also rejected: a copy on half of the upstream entries restated
unchanged, which misses exactly the copies that leave out the most, such as a
restated `contracts` map with its fields cut to four.

Rejected: comparing with the file as academic_persons ships it only. A package
after a project's own base package would report every entry of the base.

### Decided: the report entry

One status per package that removes or omits an entry, below the existing
provider, titled like the legacy status: the package key as value, the removed
and the omitted entries as dotted paths, and the command to run. The severity
is `NOTICE` when the package omits an entry - the breaking effect of ACE-711,
which the integrator has to decide on - and `INFO` when it only removes. The OK
status "no legacy keys" stays tied to the legacy keys; a package without either
kind of entry gets no status.

### Decided: `--delta` is an option of the migration command

`academic:persons:settings:migrate --delta` prints, for every package after the
first one:

```
# <package key>: Configuration/AcademicPersons/Settings.yaml
# Left out of a copied map, and inherited because the files are merged per entry.
# Add an entry with "~" where leaving it out was meant to remove it:
#   profile.middleName: ~
<the delta as YAML, "~" for a removal, "[]" for an empty list>
```

A package whose delta is empty is named with a line that it can drop the file.
A package that still ships a legacy key gets a line to migrate it first; its
delta contains the legacy key as it is, because the legacy keys merge like
every other map. The mode exits with 0: it describes, it gates nothing.

Rejected: a separate command. The migration command is the place integrators
are sent to after the 3.0 upgrade.

## Risks / Trade-offs

- [The omission rule misses a copy that restates at most one entry of a map
  unchanged and sits in no other copy, such as `contracts.fields` cut down to
  the position alone] → The delta command still prints every change the file
  makes; the entries it leaves out are what the integrator compares by hand.
- [A delta written verbosely, with two unchanged keys restated next to its
  change, is taken for a copy and gets a notice for its siblings] → The delta
  command still shrinks it to the change, and the report names more, never
  less.
- [A printed delta can look like a copy where the original file did not] → A
  map restated only to keep its order counts as unchanged, so two of them in
  one parent make that parent a copy of the delta and report its siblings. No
  map of the shipped file is affected in practice; the report then names more,
  never less.
- [Dotted paths in the comments are not pasteable YAML] → The paths name a
  nested position unambiguously and the delta shows the structure to put it
  in. A second, commented YAML document would duplicate the delta.
- [A key that contains a dot] → No shipped key does. The path is for reading,
  not for parsing.

## Migration Plan

Nothing to migrate: the change adds a report entry and a command option.

## Open Questions

None.
