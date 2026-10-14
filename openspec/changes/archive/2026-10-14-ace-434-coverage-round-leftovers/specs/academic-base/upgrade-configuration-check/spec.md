## ADDED Requirements

### Requirement: A column two persons settings fields share is reported

The check SHALL report, as a warning, every database column that two fields
of the persons settings name, with the table, the column and both fields,
when both fields belong to the profile, which includes its special fields, to
the contracts, to one contact section, or to the timeline sections of one
record type. Such a column takes the backend flags of the field applied later,
the profile fields in section order and then the special fields, and the
flags of the earlier field are lost. Two fields with the same property name,
and a special field named like the property of a profile field, count as one
field, the later one, and are not reported. A project field counts only when
its column is accepted in the TCA the flags were applied to. The check SHALL
report nothing for this when `academic_persons` is not installed. This applies
on TYPO3 v13 and v14.

#### Scenario: Two profile fields on one column

- **WHEN** the persons settings declare a second profile field that names the
  column of the teaching area, which the shipped field already names
- **THEN** the check reports a warning naming the profile table, the column
  and both fields
- **AND** the upgrade check command exits with a non-zero status

#### Scenario: Every column named once

- **WHEN** every field of the persons settings names a column of its own
- **THEN** the check reports nothing about the persons settings

#### Scenario: Persons not installed

- **WHEN** `academic_persons` is not installed
- **THEN** the check runs without this part and reports nothing for it
