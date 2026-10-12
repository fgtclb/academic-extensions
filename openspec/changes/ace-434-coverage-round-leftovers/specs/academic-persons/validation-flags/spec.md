## ADDED Requirements

### Requirement: Two fields on one column combine their flags

When the persons settings declare two fields that name the same database
column, the backend form SHALL treat that column as if one field carried the
flags of both: the column SHALL be read only when either field locks it, and
required when either field requires it and neither locks it. A field without
flags SHALL take nothing away from the other. This applies on TYPO3 v13 and
v14.

#### Scenario: A second field without flags

- **WHEN** the settings declare two profile fields on the column of the
  teaching area, the first one required and the second one without flags
- **THEN** the backend form requires the teaching area

#### Scenario: One field requires the column, the other locks it

- **WHEN** the settings declare two profile fields on the column of the
  teaching area, the first one required and the second one read only
- **THEN** the backend form shows the teaching area read only and does not
  require it, as for a single field with both flags

#### Scenario: A single field on its column

- **WHEN** the settings declare one required field on the column of the
  teaching area
- **THEN** the backend form requires the teaching area, as before
