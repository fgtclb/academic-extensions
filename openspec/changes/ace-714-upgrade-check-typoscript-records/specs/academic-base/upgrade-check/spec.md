## MODIFIED Requirements

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
