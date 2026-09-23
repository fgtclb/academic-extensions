## ADDED Requirements

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

## MODIFIED Requirements

### Requirement: Alias set dependencies are reported as a notice
The check SHALL report a site that depends on an alias set of an academic
extension as a notice naming the site and the set to depend on instead. An
alias set that TYPO3 cannot provide SHALL be reported as an unavailable set
only, not as a notice.

#### Scenario: Site depends on the persons alias set
- **WHEN** a site configuration depends on `fgtclb/academic-persons-default`
  and `academic_persons` is installed
- **THEN** the check reports a notice naming the site and
  `fgtclb/academic-persons`

#### Scenario: Alias set of an extension that is not installed
- **WHEN** a site configuration depends on `fgtclb/academic-persons-default`
  and `academic_persons` is not installed
- **THEN** the check reports the dependency as an unavailable set
- **AND** it reports no alias set notice for it
