## ADDED Requirements

### Requirement: Program field facts render as their field is edited

Wherever the facts of a program are shown, on the program page, in the program
details content element and on the program card, the job profile, performance
scope and prerequisites facts SHALL render their value as HTML when their field
has the rich text editor enabled for program pages, and as escaped text with
its line breaks kept when it has not. The decision SHALL follow the field
configuration of the program page type, so that a project switching the rich
text editor off or on, for the field on every page type or for the program
page type only, gets the matching output without overriding a template. The
credit points fact SHALL always render as text. This SHALL hold on TYPO3 v13
and v14.

#### Scenario: Shipped configuration

- **WHEN** the program page type keeps the shipped configuration and a
  program's prerequisites are stored as `<p>Good <strong>maths</strong></p>`
- **THEN** the prerequisites fact shows a paragraph with "maths" in bold, on
  the program page, in the details content element and on a card that lists
  `prerequisites`

#### Scenario: Rich text editor switched off for program pages only

- **WHEN** a site package switches the rich text editor of the job profile off
  for the program page type only, and a program's job profile is stored as
  `Research & teaching` and `<b>Industry</b>` on two lines
- **THEN** the job profile fact shows "Research & teaching", a line break and
  the literal text `<b>Industry</b>`
- **AND** no element of the stored text is interpreted as markup

#### Scenario: Rich text editor switched off for the field

- **WHEN** a site package switches the rich text editor of the performance
  scope field off for every page type
- **THEN** the performance scope fact of a program renders its stored text
  escaped, with its line breaks kept

#### Scenario: Rich text editor switched on again for program pages

- **WHEN** a site package switches the rich text editor of the prerequisites
  field off for every page type and on again for the program page type
- **THEN** the prerequisites fact of a program renders its stored HTML

#### Scenario: Credit points

- **WHEN** a program with 180 credit points is shown and the facts list
  `creditPoints`
- **THEN** the credit points fact shows 180 as text

### Requirement: Rich text facts are marked for the content styles

The element that holds the value of a fact SHALL carry the class
`ce-bodytext` when the fact renders rich text, so that the styles a site gives
to the body text of content elements apply to it. It SHALL NOT carry that class
for a category type fact, for the credit points fact or for a text fact whose
field has no rich text editor. This SHALL hold wherever the facts are shown,
on TYPO3 v13 and v14.

#### Scenario: Rich text fact

- **WHEN** the program page shows the job profile fact with the shipped
  configuration
- **THEN** the value of that fact carries the class `ce-bodytext`

#### Scenario: Category type and credit points facts

- **WHEN** the program page shows the degree and the credit points facts
- **THEN** neither value carries the class `ce-bodytext`

#### Scenario: Text fact without rich text editor

- **WHEN** a site package switches the rich text editor of the prerequisites
  off for the program page type
- **THEN** the value of the prerequisites fact does not carry the class
  `ce-bodytext`
