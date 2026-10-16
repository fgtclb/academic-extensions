## Purpose

Defines which profiles the list and list-and-detail elements of
`academic_persons` show when an editor picks them by hand in "List of
profiles", and what of the element still applies to them.

## ADDED Requirements

### Requirement: Picked profiles are shown wherever they are stored

When an editor picks profiles by hand in a list or list-and-detail element,
the system SHALL show every picked profile, whether or not it is stored in a
folder of the element's Startingpoint, in the order of the selection. A list
without picked profiles SHALL keep showing only the profiles of its
Startingpoint. This applies on TYPO3 v13 and v14.

#### Scenario: Picked profile in another folder

- **WHEN** a list element has the folder "Staff" as its Startingpoint, and the
  editor picks a profile stored in the folder "Guests" followed by a profile
  stored in "Staff"
- **THEN** the list shows both profiles, the one from "Guests" first

#### Scenario: Paginated selection across folders

- **WHEN** a list-and-detail element with the Startingpoint "Staff" picks
  three profiles from "Guests" and "Staff" with pagination at two per page
- **THEN** the first page shows the first two picked profiles in the order of
  the selection, whichever folder they are stored in

#### Scenario: List without a selection

- **WHEN** a list element has the folder "Staff" as its Startingpoint and no
  picked profiles
- **THEN** the list shows the profiles stored in "Staff" and none stored in
  another folder

### Requirement: The visibility of a picked profile still applies

The system SHALL leave out a picked profile that is deleted, or hidden while
the element does not show hidden records, wherever it is stored. With "Show
hidden records" switched on, the system SHALL show a hidden picked profile
from any folder. This applies on TYPO3 v13 and v14.

#### Scenario: Hidden profile in another folder

- **WHEN** a list element with the Startingpoint "Staff" picks a hidden and a
  deleted profile stored in "Guests", next to visible profiles
- **THEN** the list shows the visible profiles only

#### Scenario: Show hidden records

- **WHEN** the same element has "Show hidden records" switched on
- **THEN** the list also shows the hidden profile, at its place in the
  selection, and still not the deleted one
