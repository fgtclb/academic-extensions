## ADDED Requirements

### Requirement: The status report names a column two fields share

The persons settings status SHALL name, as a warning, every database column
that two fields of the merged persons settings name, when both belong to the
profile, which includes its special fields, to the contracts, to one contact
section, or to the timeline sections of one record type. It SHALL list the
table, the column and both fields, and say that the column takes the flags of
the field applied later, the profile fields in section order and then the
special fields. Two fields with the same property name, and a special field
named like the property of a profile field, count as one field, the later one,
and are not named. A project field counts only when its column is accepted in
the TCA the flags were applied to. This applies on TYPO3 v13 and v14.

#### Scenario: Two profile fields on one column

- **WHEN** a package declares a second profile field that names the column of
  the teaching area, which the shipped field already names
- **THEN** the persons settings status names the profile table, the column
  and both fields as a warning

#### Scenario: Every column named once

- **WHEN** every field of the merged persons settings names a column of its
  own
- **THEN** the persons settings status lists nothing about columns
