# academic-jobs/validation-settings Specification

## Purpose
Defines how the validation settings file of academic_jobs is combined across
packages, and what its flags do in the new-job form, in the check of a
submitted job and in the backend record editor of a job.

## Requirements

### Requirement: Settings files of several packages are merged per field

When more than one active package ships the jobs validation settings file,
the system SHALL combine them in package loading order so that a later
package changes only the fields it names, at any depth, and a `null` value
removes a field. Every field the later package does not name SHALL keep the
flags of the earlier package. This applies on TYPO3 v13 and v14.

#### Scenario: Project changes one field

- **WHEN** a project package loaded after academic_jobs ships a jobs settings
  file that names only `companyName` with an empty list of flags
- **THEN** the company name is optional in the new-job form
- **AND** the title is still required, as academic_jobs ships it

#### Scenario: Project empties a field

- **WHEN** the project file names `employmentStartDate` with an empty list
- **THEN** the start date is optional in the new-job form and in the backend
  record editor

#### Scenario: Project leaves a field out

- **WHEN** the project file restates the shipped fields but leaves out
  `description`
- **THEN** the description is still required, as academic_jobs ships it

### Requirement: Flags are read without regard to case

The system SHALL read a flag regardless of its case and surrounding spaces,
and SHALL ignore a flag it does not know.

#### Scenario: Flag in capitals

- **WHEN** the settings mark `companyName` with `Required`
- **THEN** the company name is required in the new-job form and when a job is
  submitted

#### Scenario: Unknown flag

- **WHEN** the settings mark `title` with `disabledd`
- **THEN** the title renders as a text input in the new-job form, and the
  unknown flag changes nothing else

### Requirement: The new-job form marks fields and chooses inputs from the flags

The new-job form SHALL mark a field as required when its flags make it
required, and SHALL render a text field with the input type its flags
choose: `email` an e-mail input, `url` a URL input, `number` a number input,
`tel` a telephone input, any other combination a text input. `readonly` and
`disabled` SHALL remove the required mark and leave the field in the form.

#### Scenario: Shipped settings

- **WHEN** a visitor opens the new-job form of an installation that keeps the
  shipped settings
- **THEN** title, employment type, job type, company name, start date and
  description are marked as required
- **AND** the contact e-mail renders as an e-mail input, the link as a URL
  input and the contact phone as a telephone input

#### Scenario: International phone number

- **WHEN** a visitor enters `+49 30 123` as the contact phone and submits a
  job with every required field filled
- **THEN** the job is created with that phone number

#### Scenario: Locked field

- **WHEN** the settings mark `companyName` with `required` and `readonly`
- **THEN** the new-job form shows the company name field without a required
  mark and accepts a job without it

### Requirement: A submitted job is checked against the flags

The system SHALL refuse a submitted job whose value of a field breaks a flag:
an empty value of a required field, where `0` counts as empty for a field the
job stores as a whole number, a value of an `email` field that is not
an e-mail address, a value of a `url` field that is not a URL. Every broken
flag of one submission SHALL be reported on its own field. An empty value of
a field that is not required SHALL be accepted. `number`, `tel` and the lock
flags SHALL NOT refuse a value. A field the job has no property for SHALL be
left out of the check.

#### Scenario: Required field empty

- **WHEN** a visitor submits a job without a title
- **THEN** the form is shown again with an error on the title, and no job is
  created

#### Scenario: Required select left on "Please choose"

- **WHEN** a visitor submits a job with the working hours or the job type left
  on "Please choose", which submits `0`
- **THEN** the form is shown again with an error on that field, and no job is
  created

#### Scenario: Invalid e-mail address

- **WHEN** a visitor submits a job with `not-an-address` as the contact e-mail
- **THEN** the form is shown again with an error on the contact e-mail

#### Scenario: Field without a property

- **WHEN** the settings mark a field `salary`, which the job does not have, as
  required
- **THEN** a job with every other required field filled is created

### Requirement: A rejected field shows its message

When the new-job form is shown again after a refused submission, every refused
field SHALL be marked as invalid, for sighted visitors and for assistive
technology, and SHALL be followed by its message in the language of the site
language. A label an integrator sets for the field and the error SHALL take
precedence over the shipped one and SHALL be able to name the refused value. A
field that was accepted SHALL carry no mark and no message. The form SHALL keep
the values the visitor entered. This SHALL behave the same on TYPO3 v13 and
v14.

#### Scenario: Invalid e-mail address on a German site language

- **WHEN** a visitor submits a job with `not-an-email` as the contact e-mail on
  a German site language
- **THEN** the contact e-mail field is marked invalid and announced as invalid
- **AND** it is followed by "Bitte geben Sie eine gültige E-Mail-Adresse ein."

#### Scenario: Label of a field and an error naming the value

- **WHEN** an integrator sets the label for the employment start date and the
  error of a date that cannot be read to `"%s" is no date.`, and a visitor
  enters `soon`
- **THEN** the field is followed by `"soon" is no date.`

### Requirement: The backend record editor applies the same flags

The system SHALL apply the flags of the jobs settings to the backend record
editor of a job, after every TCA override of an installation: a required
field SHALL be required there, `readonly` and `disabled` SHALL make the field
read only there, and `email` and `number` SHALL set the field type. A field
the job table does not have SHALL be left out. For every configured field the
settings SHALL decide whether it is required and read only, over a TCA
override of the installation. This applies on TYPO3 v13 and v14.

#### Scenario: Shipped settings in the backend

- **WHEN** an editor opens a job record in the backend of an installation that
  keeps the shipped settings
- **THEN** company name and description are required, as well as title,
  employment type, job type and start date

#### Scenario: Locked in the backend

- **WHEN** the settings mark `contactEmail` with `readonly`
- **THEN** the contact e-mail of a job is read only in the backend record
  editor
- **AND** the new-job form still offers the field

#### Scenario: Project TCA override of a configured field

- **WHEN** a project TCA override makes the link of a job required, and the
  settings mark `link` with `url` alone
- **THEN** the link is not required in the backend record editor

#### Scenario: Field the table does not have

- **WHEN** the settings name a field `salary` that the job table does not have
- **THEN** the backend record editor and the TCA are unchanged by it
