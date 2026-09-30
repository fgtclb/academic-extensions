## MODIFIED Requirements

### Requirement: Dependencies on academic sets TYPO3 cannot provide are reported

The check SHALL report a site whose configuration depends on a set of an
academic extension that TYPO3 cannot provide - because no active extension
ships it, or because a set it depends on is missing - as an error naming the
site and the set. A set of another vendor SHALL be reported the same way when
the set it misses, directly or further down, is an academic one, and the error
SHALL name that academic set. For a set a release removed, the error SHALL
say what replaced it. A set of another vendor that misses a set of another
vendor SHALL NOT be reported. This applies on TYPO3 v13 and v14.

#### Scenario: Site depends on the removed programs content-load set

- **WHEN** a site configuration depends on
  `fgtclb/academic-programs-content-load`
- **THEN** the check reports an error naming the site and the set
- **AND** the error says that 3.0 removed the set and that the dependency is
  to be removed
- **AND** the command exits with the failure status

#### Scenario: Site depends on a removed partner or project content-load set

- **WHEN** a site configuration depends on
  `fgtclb/academic-partners-content-load` or
  `fgtclb/academic-projects-content-load`
- **THEN** the check reports an error naming the site and the set
- **AND** the error says that 3.0 removed the set, that partner or project
  pages render the content of their main column without it, and that the
  dependency is to be removed

#### Scenario: Site package set depends on a missing academic set

- **WHEN** a site depends on a set of the site package that depends, directly
  or through another set of the site package, on an academic set no active
  extension ships
- **THEN** the check reports an error naming the site, the declared set and
  the missing academic set

#### Scenario: Available sets and sets of other vendors

- **WHEN** a site depends on an available academic set, on a set of another
  vendor that is not installed, and on a set of another vendor that misses a
  set of another vendor
- **THEN** the check reports none of them as unavailable
