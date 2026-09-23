# academic-base/upgrade-configuration-check Specification

## Purpose
Tells an integrator which parts of an installation's stored configuration no
longer reach the academic extensions after an upgrade, without changing any of
it.

## Requirements

### Requirement: Stored static templates that deliver nothing are reported
The check SHALL report every visible TypoScript record that includes a static
template of an academic extension the installation does not have, or whose
folder delivers no TypoScript in the installed version, as a warning naming the
record and the stored path. A record that is deleted, hidden or outside its
start and end time SHALL NOT be reported, because it delivers nothing to
anybody. It SHALL behave identically on TYPO3 v13 and v14.

#### Scenario: Static template path without TypoScript
- **WHEN** a TypoScript record includes an academic static template path that
  the installed extension no longer provides
- **THEN** the check reports one warning naming the record uid and that path

#### Scenario: Static template of an extension that is gone
- **WHEN** a TypoScript record includes a static template of an academic
  extension that is not installed
- **THEN** the check reports one warning naming the record uid and the
  extension

#### Scenario: Working static template
- **WHEN** a TypoScript record includes only academic static templates whose
  folders deliver TypoScript in the installed version
- **THEN** the check reports nothing for that record

#### Scenario: Hidden TypoScript record
- **WHEN** a hidden TypoScript record includes an academic static template that
  delivers nothing
- **THEN** the check reports nothing for that record

### Requirement: Unresolved academic TSconfig imports are reported
The check SHALL report every page whose page TSconfig, or whose selected page
TSconfig includes, reference page TSconfig of an academic extension that TYPO3
does not read, as a warning naming the page and the reference. A selected value
naming something of an academic extension other than a file - a folder, for
instance - SHALL be reported as well, because TYPO3 reads nothing from it. It SHALL read the page TSconfig
of a site the same way and name the site. A reference that resolves SHALL be
followed, so that an unresolved academic reference inside the file it names is
reported with the page or site that leads to it. The page TSconfig of a hidden
page SHALL be read, because TYPO3 reads it too; that of a deleted page SHALL
NOT.

#### Scenario: Import of a renamed folder
- **WHEN** a page's TSconfig imports a page TSconfig file from a folder of an
  academic extension that no longer exists
- **THEN** the check reports one warning naming the page uid and the import

#### Scenario: Import that resolves
- **WHEN** a page's TSconfig imports an existing academic page TSconfig file
  that imports nothing unresolved itself
- **THEN** the check reports nothing for that page

#### Scenario: Import in the page TSconfig of a site
- **WHEN** the page TSconfig stored next to a site configuration imports an
  academic page TSconfig file that does not exist
- **THEN** the check reports one warning naming the site and the import

#### Scenario: A file that resolves and imports one that does not
- **WHEN** a page's TSconfig imports a file that exists, and that file imports
  a page TSconfig file of an academic extension that does not exist
- **THEN** the check reports one warning naming the page and the unresolved
  reference inside the included file

#### Scenario: A selected value naming a folder
- **WHEN** a page selects a page TSconfig value that names a folder of an
  academic extension rather than a file
- **THEN** the check reports one warning naming the page and the folder
- **AND** reports nothing about the files inside it, because TYPO3 reads none
  of them

#### Scenario: Files that import each other
- **WHEN** two page TSconfig files import one another
- **THEN** the check reports each unresolved reference at most once and
  terminates

### Requirement: Academic includes in a syntax TYPO3 v14 dropped are reported
The check SHALL report every page TSconfig that includes a file of an academic
extension with the `<INCLUDE_TYPOSCRIPT:` syntax as a warning naming the page or
site and the reference, whether or not the referenced file exists.

#### Scenario: Legacy include of a file that exists
- **WHEN** a page's TSconfig includes an existing academic page TSconfig file
  with `<INCLUDE_TYPOSCRIPT:`
- **THEN** the check reports one warning naming the page and the reference,
  because TYPO3 v14 ignores the line without a message

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

#### Scenario: XCLASS of a class the installed version does not have
- **WHEN** an installation registers an XCLASS for a class of an academic
  extension that the installed version no longer ships
- **THEN** the check reports an error naming both classes

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

### Requirement: Unresolved academic imports in a TypoScript record are reported
The check SHALL report every visible TypoScript record whose Constants or Setup
field imports a TypoScript file of an academic extension that the installation
does not resolve, as a warning naming the record, the field and the reference.
It SHALL behave identically on TYPO3 v13 and v14.

#### Scenario: Setup imports a removed file
- **WHEN** the Setup field of a TypoScript record imports a TypoScript file of
  an academic extension that the installed version no longer ships
- **THEN** the check reports one warning naming the record uid, the Setup field
  and the import

#### Scenario: Constants import that resolves
- **WHEN** the Constants field of a TypoScript record imports a TypoScript file
  of an academic extension that the installed version ships
- **THEN** the check reports nothing for that field

#### Scenario: Import of another extension
- **WHEN** a TypoScript record imports a file of an extension that is not one
  of the academic extensions
- **THEN** the check reports nothing for it

### Requirement: Academic includes in a TypoScript record in the dropped syntax are reported
The check SHALL report every visible TypoScript record whose Constants or Setup
field includes a file of an academic extension with the `<INCLUDE_TYPOSCRIPT:`
syntax as a warning naming the record, the field and the reference, whether or
not the referenced file exists.

#### Scenario: Legacy include of a file that exists
- **WHEN** the Setup field of a TypoScript record includes an existing
  TypoScript file of an academic extension with `<INCLUDE_TYPOSCRIPT:`
- **THEN** the check reports one warning naming the record, the Setup field and
  the reference, because TYPO3 v14 ignores the line without a message

### Requirement: A clear flag that discards a site set contribution is reported
The check SHALL report a TypoScript record on the root page of a site that
delivers TypoScript through site sets, when the record clears the Constants or
the Setup branch, as a warning naming the site, the record and the branch that
is discarded.

#### Scenario: Root record clears what the sets deliver
- **WHEN** a site depends on site sets and a TypoScript record on its root page
  clears the Setup branch
- **THEN** the check reports one warning naming the site, the record and the
  Setup branch

#### Scenario: Clear flag on a site without sets
- **WHEN** a site delivers no TypoScript of its own and a TypoScript record on
  its root page carries a clear flag
- **THEN** the check reports nothing, because the flag discards nothing

#### Scenario: Site with sets and a record that clears nothing
- **WHEN** a site depends on site sets and a TypoScript record on its root page
  carries no clear flag
- **THEN** the check reports nothing for that record

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
