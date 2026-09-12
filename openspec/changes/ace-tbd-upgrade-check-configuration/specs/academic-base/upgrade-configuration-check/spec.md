## Purpose

Tells an integrator which parts of an installation's stored configuration no
longer reach the academic extensions after an upgrade, without changing any of
it.

## ADDED Requirements

### Requirement: Stored static templates that deliver nothing are reported
The check SHALL report every TypoScript record that includes a static template
of an academic extension which the installation does not register, or whose
folder holds no TypoScript, as a warning naming the record and the stored path.
It SHALL behave identically on TYPO3 v13 and v14.

#### Scenario: Static template path without TypoScript
- **WHEN** a TypoScript record includes an academic static template path that
  the installed extension no longer provides
- **THEN** the check reports one warning naming the record uid and that path

#### Scenario: Registered static template
- **WHEN** a TypoScript record includes only static templates that the
  installed academic extensions register
- **THEN** the check reports nothing for that record

### Requirement: Unresolved academic TSconfig imports are reported
The check SHALL report every page whose page TSconfig, or whose selected page
TSconfig includes, reference a file of an academic extension that does not
exist, as a warning naming the page and the reference.

#### Scenario: Import of a renamed folder
- **WHEN** a page's TSconfig imports a page TSconfig file from a folder of an
  academic extension that no longer exists
- **THEN** the check reports one warning naming the page uid and the import

#### Scenario: Import that resolves
- **WHEN** a page's TSconfig imports an existing academic page TSconfig file
- **THEN** the check reports nothing for that page

### Requirement: Alias set dependencies are reported as a notice
The check SHALL report a site that depends on an alias set of an academic
extension as a notice naming the site and the set to depend on instead.

#### Scenario: Site depends on the persons alias set
- **WHEN** a site configuration depends on `fgtclb/academic-persons-default`
- **THEN** the check reports a notice naming the site and
  `fgtclb/academic-persons`

### Requirement: Set and static template of one extension on one site are reported
The check SHALL report a site that depends on a set of an academic extension
while a TypoScript record on the site's root page includes a static template of
the same extension, as a warning that links the documentation on using one
mechanism per site.

#### Scenario: Both mechanisms for one extension
- **WHEN** a site depends on a set of `academic_jobs` and a TypoScript record
  on its root page includes a static template of `academic_jobs`
- **THEN** the check reports one warning naming the site and the extension

#### Scenario: One mechanism per extension
- **WHEN** a site depends on a set of `academic_jobs` and includes only a
  static template of `academic_persons`
- **THEN** the check reports nothing for that site

### Requirement: XCLASS registrations of academic classes are reported
The check SHALL report an XCLASS registration for a class of an academic
extension as a warning, and as an error when the replaced class is final.

#### Scenario: XCLASS of a final class
- **WHEN** an installation registers an XCLASS for a final academic class
- **THEN** the check reports an error naming both classes

#### Scenario: XCLASS of a class that is not final
- **WHEN** an installation registers an XCLASS for an academic class that is
  not final
- **THEN** the check reports a warning naming both classes

### Requirement: Findings are visible in the status report and on the command line
The check SHALL be listed in the backend status report when EXT:reports is
active, with one entry per finding, or a single OK entry without findings. The
upgrade check command SHALL run the same check, list the same findings, and
exit with a non-zero status when at least one warning or error is found; a
notice alone SHALL NOT change the exit status. An installation without
EXT:reports SHALL work unchanged.

#### Scenario: No findings
- **WHEN** an administrator opens the status report of an installation without
  stale configuration
- **THEN** the academic upgrade section shows a single OK entry

#### Scenario: Warning on the command line
- **WHEN** the upgrade check command finds one warning
- **THEN** it lists the warning and exits with a non-zero status

#### Scenario: Notice only
- **WHEN** the upgrade check command finds only a notice
- **THEN** it lists the notice and exits with status zero

#### Scenario: EXT:reports not installed
- **WHEN** EXT:reports is not active
- **THEN** the backend and the upgrade check command work without errors

### Requirement: The check changes nothing
The check MUST NOT modify any record, file or site configuration.

#### Scenario: Running the check with findings
- **WHEN** the check runs and reports findings
- **THEN** every TypoScript record, page and site configuration is unchanged
