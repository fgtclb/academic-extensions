## Why

The persons settings files of all active packages are merged per entry since
ACE-711 (`ace-711-settings-loader-deep-merge`): maps merge key by key, lists
replace, and `~` removes an entry. A project therefore needs only a delta, and
five of the six analysed projects still ship full copies of the upstream
settings - ace-demo copies all of `profile` to drop `required` from `gender`
and `validFrom` (ACE-536). Four still use the pre-3.0 keys; migrating them
prints complete maps, copies again.

Those copies have to be shrunk by hand, and a copy in the 3.0 shape is not
harmless in the meantime: under the per-entry merge an entry it leaves out is
inherited from upstream instead of removed. Nothing tells the integrator which
entries a copy leaves out, and nothing produces the delta the copy stands
for.

## What Changes

- The Reports module status of academic_persons lists, per package, the
  entries it removes with `~` and the upstream entries its copied maps leave
  out, which the package now inherits.
- `academic:persons:settings:migrate --delta` prints, for every package after
  the first one, the smallest file that produces the same effective settings,
  followed by the entries its copied maps leave out as comments to be turned
  into a `~` where the omission meant a removal. It never writes a file and
  exits with 0.
- The default mode of the command, its output and its exit codes are
  unchanged.

The merge itself is not touched. Behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-persons/settings-overrides`: how an integrator sees what the
  settings file of each package changes, and obtains the smallest file for it.

### Modified Capabilities

None.

## Impact

- `academic_persons` (`packages/fgtclb/academic-persons`): a comparison of each
  package's settings with the packages loaded before it, the legacy settings
  status report, the settings migration command, its labels, the integrator
  documentation and a 3.0 `Feature-` changelog entry.
- `academic_base` is consumed unchanged; the comparison folds the packages with
  the loader's own merge.
- No database, TCA, cache or dependency change. Not breaking: the breaking part
  is ACE-711's.

## Non-goals

- The merge rules (ACE-711) and the `academic_jobs` settings file (ACE-508).
- Writing a settings file: the files live in version-controlled site packages.
- Deciding whether an omission meant a removal; only the integrator knows
  whether an entry was dropped on purpose or added upstream after the copy.
- A backport to branch `2`, which has the pre-3.0 flat shape.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`persons-display-15`, with `persons-data-09` merged into it).

Reduced on 2026-09-24 with the maintainer: the first draft proposed the
per-entry merge as a breaking change, and ACE-711 was merged before it with
that merge, its changelog and its documentation. What stayed is making the
copies visible and printing their deltas.

Filed as ACE-724 after the implementation; it relates to ACE-536, ACE-109,
ACE-161 and ACE-711. The slug keeps the name of the first draft, under which
the analysis tracks it.
