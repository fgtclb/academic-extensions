## Purpose

Defines how the settings files that several active packages ship for the same
academic extension are combined, so that a project changes a single value
without restating the upstream file.

## ADDED Requirements

### Requirement: A later package changes only the keys it names
When more than one active package ships the same academic settings file, the
system SHALL combine the files in package loading order so that a map from a
later package changes only the keys it names, at any depth. Every key the later
package does not name SHALL keep the value of the earlier package. This applies
on TYPO3 v13 and v14.

#### Scenario: Project changes the flags of one field
- **WHEN** a project package loaded after academic_persons ships a persons
  settings file that names only the profile field `title` with new flags
- **THEN** the `title` field carries the project flags
- **AND** every other profile field, section and document section keeps the
  configuration academic_persons ships

#### Scenario: Upstream adds an entry later
- **WHEN** a later academic_persons release adds a profile field that the
  project file does not name
- **THEN** the new field is configured in the project without a change to the
  project file

#### Scenario: Project leaves an upstream entry out
- **WHEN** the project file restates the profile fields but leaves out
  `middleName`
- **THEN** `middleName` is still configured as academic_persons ships it

### Requirement: A list is replaced as a whole
The system SHALL replace a list from an earlier package with the list from the
later package, without combining their entries. An empty list SHALL replace a
non-empty one.

#### Scenario: Project replaces the flags of a field
- **WHEN** academic_persons declares the flag list `[required]` for `lastName`
  and the project declares `[readonly, disabled]`
- **THEN** `lastName` is read only and disabled, and no longer required

#### Scenario: Project clears the flags of a field
- **WHEN** the project declares an empty flag list for `title`
- **THEN** `title` carries no flag

### Requirement: A null value removes a key
The system SHALL remove a key from the combined settings when a later package
sets it to `null`, whatever value an earlier package gave it. The extension
SHALL then behave as if no package had configured that key.

#### Scenario: Project removes an upstream entry
- **WHEN** the project file sets the profile field `middleName` to `null`
- **THEN** the combined persons settings contain no `middleName` entry

### Requirement: The key order follows a complete restatement
The system SHALL order the keys of a combined map as the later package orders
them when the later map names every key of the earlier map. Otherwise the
order of the earlier map SHALL be kept, and keys only the later map names SHALL
follow it.

#### Scenario: Project ships a reordered full copy
- **WHEN** a project ships a full copy of the persons settings file with the
  document sections in a different order
- **THEN** the document sections appear in the order of the copy, exactly as
  before this change

#### Scenario: Project names a single field
- **WHEN** the project file names only `firstName` below `profile`
- **THEN** the profile fields keep the order academic_persons ships
