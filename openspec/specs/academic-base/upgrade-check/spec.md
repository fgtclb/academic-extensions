# academic-base/upgrade-check Specification

## Purpose
Lets an integrator find project template overrides that no longer take effect
or only freeze the upstream markup after an academic extension upgrade.

## Requirements

### Requirement: The integrator names the extension and the override folders
The command `academic:upgrade:check` SHALL accept one academic extension key
and one or more override folders, given as `EXT:` paths or absolute paths. Each
override folder SHALL be compared with the extension's `Resources/Private/`
folder, or with an upstream folder the integrator names instead. Naming an
override folder, an upstream folder or a site without the extension key SHALL
be invalid input, and so SHALL naming the extension key without an override
folder or a site. Naming an upstream folder without an override folder SHALL be
invalid input as well, because the upstream folder applies to no other option.
Without any of them the command SHALL check the stored configuration of the
installation and no override folder. This applies on TYPO3 v13 and v14.

#### Scenario: Override folder mirrors the extension
- **WHEN** an integrator runs the command for `academic_persons_edit` with an
  override folder that contains `Templates/` and `Partials/`
- **THEN** each Fluid file in it is compared with the file of the same
  relative path below `EXT:academic_persons_edit/Resources/Private/`

#### Scenario: Unknown extension or missing folder
- **WHEN** the extension is not active or an override folder does not exist
- **THEN** the command names the invalid input and exits with an error status
  without reporting findings

#### Scenario: A folder without the extension key
- **WHEN** an integrator names an override folder, an upstream folder or a
  site but no extension key
- **THEN** the command names the invalid input and exits with an error status

#### Scenario: An upstream folder without an override folder
- **WHEN** an integrator names an extension key and an upstream folder, with a
  site or with nothing else, but no override folder
- **THEN** the command names the invalid input and exits with an error status
  rather than ignoring the upstream folder

#### Scenario: Neither an extension nor a folder
- **WHEN** an integrator runs the command without an extension key and without
  any option
- **THEN** no override folder is checked and no invalid input is reported

### Requirement: A site's TypoScript can name the override folders
With the option `--site` and a site identifier, the command SHALL take the
override folders from the template, partial and layout root paths that the
site's TypoScript configures for the extension's plugins, excluding every
folder the extensions ship themselves, and SHALL compare each with the
upstream folder of the same kind. The option SHALL be combinable with explicit
override folders. This applies on TYPO3 v13 and v14.

#### Scenario: Site with a project partial root path
- **WHEN** a site's TypoScript adds a project partial root path for the
  persons plugins and the integrator runs the command for `academic_persons`
  with that site
- **THEN** each Fluid file in that folder is compared with
  `EXT:academic_persons/Resources/Private/Partials/`
- **AND** the extension's own folders produce no finding

#### Scenario: A root path of another extension
- **WHEN** a site's TypoScript names the partial folder of another academic
  extension or of a TYPO3 system extension in the same root path list
- **THEN** no finding is reported for it

#### Scenario: Unknown site
- **WHEN** the integrator names a site identifier that does not exist
- **THEN** the command names the invalid input and exits with an error status
  without reporting findings

#### Scenario: Site that does not configure the extension
- **WHEN** the site's TypoScript configures no view root path for the
  extension's plugins at all
- **THEN** the command names the invalid input and exits with an error status
  rather than reporting that nothing is wrong

### Requirement: An override without upstream counterpart is a problem
The command SHALL report `missing-upstream` for an override file that has no
upstream file at the same relative path, not even one differing in case.

#### Scenario: Override of a removed template
- **WHEN** the override folder holds `Templates/Profile/Show.html` and the
  extension no longer ships that file
- **THEN** the command reports `missing-upstream Templates/Profile/Show.html`

### Requirement: A case-only difference is a problem
The command SHALL report `case-mismatch` with the upstream name for an
override file whose relative path matches an upstream file only when case is
ignored.

#### Scenario: Lowercased page template name
- **WHEN** the override holds `Pages/Academicprogram.html` and upstream ships
  `Pages/AcademicProgram.html`
- **THEN** the command reports `case-mismatch` naming both names

### Requirement: A byte-identical copy is a notice
The command SHALL report `identical` as a notice for an override file whose
content equals the upstream file byte for byte.

#### Scenario: Frozen copy
- **WHEN** an override file is an unchanged copy of the upstream file
- **THEN** the command reports `identical` for it

### Requirement: The exit status reflects problems only
The command SHALL exit with a non-zero status when at least one
`missing-upstream` or `case-mismatch` is reported, or when any other check
group of the command reports a problem, and with zero otherwise, including
when only notices are reported. It MUST NOT modify any file.

#### Scenario: Only notices
- **WHEN** the only finding of any check group is an `identical` copy
- **THEN** the command exits with status zero

#### Scenario: A problem in CI
- **WHEN** a `missing-upstream` finding is reported
- **THEN** the command exits with a non-zero status and every override file
  is unchanged

#### Scenario: A problem of another check group
- **WHEN** no override file is a problem and another check group of the
  command reports one
- **THEN** the command exits with a non-zero status
