## ADDED Requirements

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

## MODIFIED Requirements

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
