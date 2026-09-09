# academic-persons-edit/profile-editing-dates Specification

## Purpose
Defines how a date is entered, hinted, submitted and refused in the frontend
profile editor, for the timeline entries and for the two contract dates alike.

## Requirements

### Requirement: A date is edited in the browser's own date control
Every date field of the editor SHALL be rendered as the browser's native date
control, so that the browser's calendar opens on it. This SHALL apply to the
dates of a timeline entry and to the two contract dates alike, and SHALL be the
same control whether the field is rendered directly or built from a prototype.

#### Scenario: A timeline entry is edited
- **WHEN** an editor opens a timeline entry for editing
- **THEN** each of its dates offers the browser's date control

#### Scenario: A contract is edited
- **WHEN** an editor opens a contract for editing
- **THEN** the valid-from and valid-to fields offer the browser's date control
  and no longer show a `dd.mm.yyyy` format hint

### Requirement: An integrator chooses the granularity a date is entered at
For every date field the integrator SHALL be able to choose whether the editor
is asked for a full date, for a year and a month, or for a year alone. When the
editor is asked for less than a full date, the parts that were not asked for
SHALL be completed by a rule the integrator chooses: the first or the last day
of the month, and the first or the last month of the year. When the integrator
configures nothing, the editor SHALL be asked for a full date.

#### Scenario: A year and a month are asked for
- **WHEN** a field asks for a year and a month, completing to the first day of
  the month
- **AND** an editor enters March 2019
- **THEN** the entry stores 1 March 2019

#### Scenario: A year alone is asked for, completed to the end
- **WHEN** a field asks for a year alone, completing to the last month of the
  year and the last day of the month
- **AND** an editor enters 2019
- **THEN** the entry stores 31 December 2019

#### Scenario: Nothing is configured
- **WHEN** a date field carries no input configuration
- **THEN** the editor is asked for a full date and nothing is completed

### Requirement: An editor is told what a visitor will see
When a date field shows a visitor less than the editor enters, the editor SHALL
be told so at the field.

#### Scenario: The year alone is published
- **WHEN** a field is displayed as the year alone but entered as a full date
- **THEN** the editor is shown a hint at that field saying that only the year
  is published

#### Scenario: Everything entered is published
- **WHEN** a field displays year, month and day
- **THEN** no such hint is shown

### Requirement: A date is submitted and answered in an unambiguous format
A submitted date SHALL be accepted in the format the browser's date control
submits. A date that cannot be read as a real calendar date SHALL be refused
with an error at that field, and the record SHALL stay unchanged. An empty
value SHALL clear the date unless the field is required.

#### Scenario: A well-formed date is stored
- **WHEN** a date field is submitted as `2019-03-14`
- **THEN** the entry stores 14 March 2019 and the response reports the stored
  date back

#### Scenario: An impossible date is refused
- **WHEN** a date field is submitted as `2019-02-31`
- **THEN** the submission is refused with an error at that field and nothing is
  written

#### Scenario: A date is cleared
- **WHEN** an optional date field is submitted empty
- **THEN** the stored date is cleared

#### Scenario: A required date is not given
- **WHEN** a required date field is submitted empty
- **THEN** the submission is refused with an error at that field

### Requirement: The editor shows a stored date in the site's notation
A stored date that the editor displays rather than offers for entry SHALL be
formatted for the locale of the matched site language and SHALL show only the
parts the field publishes.

#### Scenario: A row summarises an entry
- **WHEN** a timeline row shows an entry whose field publishes the year alone
- **THEN** the row shows the year alone

#### Scenario: A row follows the site language
- **WHEN** the same full date is shown on the German and on the US English site
  language
- **THEN** each shows it in that language's own notation
