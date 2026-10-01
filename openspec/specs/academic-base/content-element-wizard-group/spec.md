# academic-base/content-element-wizard-group Specification

## Purpose
How the group of the academic content elements appears in the new content
element wizard of TYPO3, and what a site can change about it.

## Requirements

### Requirement: The academic group follows the order of TYPO3
The wizard SHALL show the academic content elements in a group labelled
"Academic", and the extension MUST NOT give that group a position of its own.
On an installation where no extension and no site positions a group, the
groups SHALL follow the order of their registration, with "Typical page
content" first and "Academic" after the groups of TYPO3. This applies on
TYPO3 v13 and v14.

#### Scenario: Editor opens the wizard on an installation without positions
- **WHEN** an editor opens the new content element wizard on a page of an
  installation where no group has a position
- **THEN** the first group is "Typical page content"
- **AND** "Academic" follows the groups of TYPO3

#### Scenario: Site places the group first
- **WHEN** the page TSconfig of a site places the academic group before
  "Typical page content"
- **THEN** the wizard shows "Academic" as the first group

#### Scenario: Site restores the placement of earlier versions
- **WHEN** the page TSconfig of a site places the academic group after
  "Special elements"
- **THEN** the wizard shows "Special elements" first and "Academic" second

### Requirement: A site changes the academic group by page TSconfig
A site SHALL be able to rename the academic group, rename an academic
element, hide an academic element and hide the whole group through page
TSconfig of the wizard, on TYPO3 v13 and v14. On TYPO3 v14 a site SHALL also
be able to order the elements of the group by naming the element they come
before or after.

#### Scenario: Site renames the group
- **WHEN** the page TSconfig of a site sets a header for the academic group
- **THEN** the wizard labels the group with that header

#### Scenario: Site hides one element
- **WHEN** the page TSconfig of a site removes one academic element from the
  group
- **THEN** the wizard offers the other academic elements and not that one

#### Scenario: Site orders the elements on TYPO3 v14
- **WHEN** the page TSconfig of a site places one academic element before
  another on TYPO3 v14
- **THEN** the wizard shows that element first
