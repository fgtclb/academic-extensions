# academic-bite-jobs/legacy-static-template-path Specification

## Purpose
Keeps the static TypoScript template path academic_bite_jobs offered up to
version 2.3 delivering, so an upgraded installation does not lose its plugin
configuration silently.

## Requirements

### Requirement: The 2.3 static template path delivers the extension
A template record that includes `EXT:academic_bite_jobs/Configuration/TypoScript`
SHALL deliver the same TypoScript as the static template "Academic Bite Jobs:
All components" on TYPO3 v12 and v13.

#### Scenario: Upgraded installation with a stored 2.3 include
- **WHEN** a site's template record still includes the path stored by
  version 2.3
- **THEN** the bite jobs list content element renders with the templates and
  settings of the extension, as with "All components"

### Requirement: The 2.3 path stays selectable
The static template list of a template record SHALL offer the 2.3 path,
labelled as deprecated, so that saving the record keeps the include.

#### Scenario: Integrator saves the template record
- **WHEN** an integrator opens and saves a template record that includes the
  2.3 path
- **THEN** the include is still stored afterwards

### Requirement: An import of the 2.3 files keeps working
Importing the `setup.typoscript` and `constants.typoscript` of the 2.3 path
by file SHALL deliver the TypoScript of the extension.

#### Scenario: Project imports the 2.3 setup by file
- **WHEN** a project's TypoScript imports
  `EXT:academic_bite_jobs/Configuration/TypoScript/setup.typoscript`
- **THEN** the bite jobs plugin configuration is present in the frontend
  TypoScript
