# academic-contact4pages/legacy-static-template-path Specification

## Purpose
Keeps the static TypoScript template path academic_contacts4pages offered up
to version 2.3 delivering, so an upgraded installation does not lose its
plugin configuration silently.

## Requirements

### Requirement: The 2.3 static template path delivers the extension
A template record that includes
`EXT:academic_contacts4pages/Configuration/TypoScript/` SHALL deliver the
TypoScript of academic_contacts4pages, as the static template "Academic
Contacts4Pages: All components" delivers it, on TYPO3 v12 and v13. It SHALL NOT
bring the academic_persons TypoScript along, which "All components" does: a
record of version 2.3 stores that as an entry of its own, and reading it twice
resets its constants.

#### Scenario: Upgraded installation with a stored 2.3 include
- **WHEN** a site's template record still includes the path stored by
  version 2.3, next to the academic_persons entry it stored with it
- **THEN** the contacts content element renders with the templates and
  settings of the extension, as with "All components"
- **AND** the academic_persons TypoScript is read once

### Requirement: The 2.3 path stays selectable
The static template list of a template record SHALL offer the 2.3 path,
labelled as deprecated, so that saving the record keeps the include.

#### Scenario: Integrator saves the template record
- **WHEN** an integrator opens and saves a template record that includes the
  2.3 path
- **THEN** the include is still stored afterwards

### Requirement: An import of the 2.3 files keeps working
Importing the `setup.typoscript` and `constants.typoscript` of the 2.3 path
by file SHALL deliver the TypoScript of academic_contacts4pages, as an import
of the files of its contact list component does. Version 2.3 had no constants
file at this path, so a project that imports only the setup file SHALL be
told to import the constants file as well.

#### Scenario: Project imports the 2.3 files by file
- **WHEN** a project's TypoScript imports
  `EXT:academic_contacts4pages/Configuration/TypoScript/setup.typoscript`
  and, in its constants,
  `EXT:academic_contacts4pages/Configuration/TypoScript/constants.typoscript`
- **THEN** the contacts plugin configuration is present in the frontend
  TypoScript

#### Scenario: Project imports only the setup of version 2.3
- **WHEN** an integrator reads the deprecation entry of version 2.4
- **THEN** it says that the constants file has to be imported as well
