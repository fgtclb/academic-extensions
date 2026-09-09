# academic-persons/profile-information-dates Specification

## Purpose
Defines what a profile timeline entry stores for its point in time, and how
much of that stored date a visitor of the public profile is shown.

## Requirements

### Requirement: A timeline entry stores calendar dates
A timeline entry SHALL store a single date, a start date and an end date as
calendar dates rather than as years. Each of the three SHALL be optional and
SHALL be empty when nothing was entered. An entry that carries no date at all
SHALL remain valid.

#### Scenario: An entry keeps the day it was given
- **WHEN** an editor stores a timeline entry with the date 14 March 2019
- **THEN** the entry keeps that day, month and year, and returns all three
  unchanged when it is read again

#### Scenario: An entry without a date
- **WHEN** a timeline entry is stored with none of its three dates filled in
- **THEN** the entry is accepted and each of its three dates is empty

### Requirement: An integrator chooses how much of a date a visitor sees
For every date of a timeline section the integrator SHALL be able to switch the
year, the month and the day of the public display on and off independently.
The parts that are switched on SHALL be shown in the order and the notation
that the matched site language's locale uses for them; the parts that are
switched off SHALL not be shown at all. When the integrator configures nothing,
all three parts SHALL be shown.

#### Scenario: Only the year is shown
- **WHEN** a section's date is configured to show the year alone
- **AND** an entry stores 14 March 2019
- **THEN** the public profile shows `2019`

#### Scenario: The notation follows the site language
- **WHEN** a section's date is configured to show year, month and day
- **AND** an entry stores 14 March 2019
- **THEN** a visitor of the German site language is shown `14.03.2019` and a
  visitor of the US English site language is shown `Mar 14, 2019`

#### Scenario: The browser's own locale is ignored
- **WHEN** a visitor whose browser is set to a different language than the site
  opens the public profile
- **THEN** the displayed date still follows the matched site language

#### Scenario: No parts are switched off
- **WHEN** a section's date carries no display configuration
- **THEN** the public profile shows year, month and day

### Requirement: An empty date renders as nothing
A date that is empty SHALL produce no output at all, and SHALL NOT be replaced
by a placeholder date, by the current date or by a zero value.

#### Scenario: An entry with a start date and no end date
- **WHEN** an entry stores a start date and no end date
- **THEN** the public profile shows the start date and shows nothing in place
  of the end date

### Requirement: The old year fields are gone
The integer year, start year and end year of a timeline entry SHALL NOT exist
any more, in the record or in the templates. The current configuration
vocabulary SHALL NOT accept the former year field names either, so a section
configured with one of them is configured with an unknown field name.

A site package still written in the 2.x settings shape SHALL keep working: the
legacy overlay SHALL translate the year names it brings onto the date fields
that replaced them before the vocabulary sees them, and SHALL drop the numeric
flag such a set declares for them, which no longer describes a date.

#### Scenario: A current configuration naming a year field
- **WHEN** a site package configures a timeline section in the current
  vocabulary and names a field by its former year name
- **THEN** that entry has no effect, exactly as any other unknown field name

#### Scenario: A 2.x site package is upgraded
- **WHEN** a site package still ships the 2.x settings shape, whose validation
  set names the year properties of a timeline entry
- **THEN** the flags it declares for them reach the date fields that replaced
  them, the numeric flag among them is dropped, and the site package keeps
  configuring what it configured before

### Requirement: A project can migrate its own year values
The extension SHALL ship a reusable, unregistered starting point for migrating
existing year values into the new date columns, whose completion rules a
project chooses for itself. The extension SHALL NOT migrate any installation's
data on its own and SHALL NOT assume a month or a day.

#### Scenario: An installation is upgraded without acting
- **WHEN** an installation applies the schema change without providing its own
  migration
- **THEN** no timeline date is invented, and the upgrade wizard list offers
  nothing that would invent one

#### Scenario: A project supplies its own migration
- **WHEN** a project registers its own migration built on the shipped starting
  point, choosing which day and month complete a year
- **THEN** it converts the installation's year values with those rules
