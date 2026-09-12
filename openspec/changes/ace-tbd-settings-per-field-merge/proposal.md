## Why

The persons `Configuration/AcademicPersons/Settings.yaml` keeps the public
profile layout and every field definition below four top-level maps. Package
files are folded by top-level key, so changing one field forces a copy of the
whole map. ace-demo copies all of `profile` to drop `required` from `gender`
and `validFrom` (ACE-536). Four other projects carry full copies that
silently miss every upstream addition.

## What Changes

- **BREAKING** The persons settings are merged per entry, on top of the
  generic deep merge of `ace-tbd-settings-loader-deep-merge`:
  - fields below `profile`, `special`, `contracts.fields`,
    `contracts.contactSections.*` and `documentSections.*` merge per field;
  - lists such as `validators`, `profile.structure.*`, `rowFields` and
    `actions` are replaced as a whole;
  - `~` (YAML null) removes an upstream field, section or layout entry.

  A package that removed an upstream field by omitting it from its copy gets
  it back until it writes `~`; one project drops `streetNumber`, `country`
  and `middleName` that way.
- The pre-3.0 top-level keys `validations` and `profileInformationsTypes`
  are merged like every other map. The shipped `Settings.yaml` carries
  neither, so the difference shows only where two packages ship the same
  legacy key.
- The Reports module status entry lists, per package, the entries it removes
  and the upstream entries its copied maps omit.
- `academic:persons:settings:migrate --delta` prints, for every package, the
  minimal file that reproduces its current effective settings.
- The header comment of the shipped `Settings.yaml` and the documentation
  describe the new rule.

Behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-persons/settings-file-merge`: how the persons settings files of
  several packages combine into the effective settings.

### Modified Capabilities

None.

## Impact

- `academic_persons` (`packages/fgtclb/academic-persons`): the settings
  factory, the legacy settings status report, the settings migration command,
  the shipped `Settings.yaml` header, the integrator documentation and a 3.0
  `Breaking-` changelog.
- `academic_base` (`packages/fgtclb/academic-base`): the merge of
  `ace-tbd-settings-loader-deep-merge` is consumed unchanged. This change
  needs no loader parameter and adds no merge of its own.
- Every installation with its own persons `Settings.yaml`.

## Non-goals

- The generic merge mechanics of the loader (ACE-109, ACE-161).
- The `academic_jobs` settings file.
- A mechanism for reordering upstream fields. A map that restates every
  upstream key takes its own order, as the loader change rules, and the
  public display order comes from the layout lists, which replace.
- A backport to branch `2`, which has the pre-3.0 flat shape.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`persons-display-15`, with `persons-data-09` merged into it). Five of the
six analysed projects carry their own code for this today, the ACE demo
among them. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-settings-per-field-merge` when the issue is filed after
implementation.

Relates to ACE-536, ACE-109 and ACE-161.
