## Purpose

Keeps the static TypoScript template path academic_study_plan offered before
3.0 delivering, so an upgraded installation does not lose its content element
configuration silently.

## ADDED Requirements

### Requirement: The 2.x static template path delivers the extension
A template record that includes
`EXT:academic_study_plan/Configuration/TypoScript/Default` SHALL deliver the
same TypoScript as the static template "Academic Study Plan: All components"
on TYPO3 v13 and v14.

#### Scenario: Upgraded installation with a stored 2.x include
- **WHEN** a site's template record still includes the path stored by
  version 2.3
- **THEN** the study plan content element renders with the templates of the
  extension, as with "All components"

### Requirement: The 2.x path stays selectable
The static template list of a template record SHALL offer the 2.x path,
labelled as deprecated, so that saving the record keeps the include.

#### Scenario: Integrator saves the template record
- **WHEN** an integrator opens and saves a template record that includes the
  2.x path
- **THEN** the include is still stored afterwards

### Requirement: An import of the 2.x file keeps working
Importing the `setup.typoscript` of the 2.x path by file SHALL deliver the
TypoScript of the extension.

#### Scenario: Project imports the 2.x setup by file
- **WHEN** a project's TypoScript imports
  `EXT:academic_study_plan/Configuration/TypoScript/Default/setup.typoscript`
- **THEN** the study plan content element configuration is present in the
  frontend TypoScript
