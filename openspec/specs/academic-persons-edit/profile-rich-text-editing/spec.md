# academic-persons-edit/profile-rich-text-editing Specification

## Purpose
Defines what the rich text fields of the frontend profile editor of
`academic_persons_edit` offer a person editing their own profile, and what the
value they store looks like, on TYPO3 v12 and v13.

## Requirements

### Requirement: A link can be added in a rich text field
The system SHALL offer a control for adding and removing a link in every rich
text field of the profile edit form, and SHALL store a link a person added
unchanged.

#### Scenario: A link is offered
- **WHEN** a person opens the profile edit form
- **THEN** each rich text field offers a control for adding a link and one for
  removing it, and offers neither an anchor nor the advanced properties of a
  link

#### Scenario: A stored link survives the save
- **WHEN** a person saves the profile with a link in a rich text field
- **THEN** the stored value carries that link, and reopening the form shows it
  again

### Requirement: A rendered link is limited to the protocols the site renders
The system SHALL render a stored link on the public profile only for a protocol
the rendering accepts, and SHALL NOT render a `javascript:` or a `data:` URI as
a link.

#### Scenario: An https link is shown
- **WHEN** a visitor opens the profile detail view of a profile whose rich text
  field holds a link to an `https` address
- **THEN** the link is rendered

#### Scenario: A dangerous URI is not shown
- **WHEN** a visitor opens the profile detail view of a profile whose rich text
  field holds a link to a `javascript:` or a `data:` URI
- **THEN** no such link is rendered

### Requirement: The editor speaks the language of the page
The system SHALL present the rich text editor's own controls in the language the
page declares, and SHALL fall back to English for a language the editor does not
ship.

#### Scenario: A page in another language
- **WHEN** a person opens the profile edit form on a page that declares a
  language other than English
- **THEN** the editor's own controls are presented in that language

### Requirement: Stored markup is readable
The system SHALL store the markup of a rich text field with each block element
on its own line, and SHALL NOT indent it.

#### Scenario: Several paragraphs
- **WHEN** a person saves a rich text field holding several paragraphs or a list
- **THEN** the stored value carries a line break between the block elements
