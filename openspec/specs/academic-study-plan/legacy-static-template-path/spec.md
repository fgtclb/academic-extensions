# academic-study-plan/legacy-static-template-path Specification

## Purpose
Keeps the static TypoScript template path academic_study_plan offered up to
version 2.3 delivering, so an upgraded installation does not lose its content
element configuration silently.

## Requirements

### Requirement: The 2.3 static template path delivers the extension
A template record that includes
`EXT:academic_study_plan/Configuration/TypoScript/Default` SHALL deliver the
same TypoScript as the static template "Academic Study Plan: All components"
on TYPO3 v12 and v13.

#### Scenario: Upgraded installation with a stored 2.3 include
- **WHEN** a site's template record still includes the path stored by
  version 2.3
- **THEN** the study plan content element renders with the templates of the
  extension, as with "All components"

### Requirement: The 2.3 path stays selectable
The static template list of a template record SHALL offer the 2.3 path,
labelled as deprecated, so that saving the record keeps the include.

#### Scenario: Integrator saves the template record
- **WHEN** an integrator opens and saves a template record that includes the
  2.3 path
- **THEN** the include is still stored afterwards

### Requirement: An import of the 2.3 files keeps working
Importing the `setup.typoscript` and `constants.typoscript` of the 2.3 path
by file SHALL deliver the TypoScript of the extension, as an import of the
files of the content element component does. Version 2.3 had no constants
file at this path, so a project that imports only the setup file SHALL be
told to import the constants file as well.

#### Scenario: Project imports the 2.3 files by file
- **WHEN** a project's TypoScript imports
  `EXT:academic_study_plan/Configuration/TypoScript/Default/setup.typoscript`
  and, in its constants,
  `EXT:academic_study_plan/Configuration/TypoScript/Default/constants.typoscript`
- **THEN** the study plan content element configuration is present in the
  frontend TypoScript

#### Scenario: Project imports only the setup of version 2.3
- **WHEN** an integrator reads the deprecation entry of version 2.4
- **THEN** it says that the constants file has to be imported as well
