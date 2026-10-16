## ADDED Requirements

### Requirement: Each selected element keeps its own view mode

The system SHALL apply a view mode a visitor chooses in a selected profiles or
selected contracts element to that element only. Another element of the same
type on the page SHALL keep rendering its default mode, and its switch link
SHALL NOT carry the choice made in the first element. An element of the other
type SHALL stay unaffected as before. A translated element SHALL answer to the
same link as its record in the default language, whether the site overlays the
translation or shows it in its own language. This applies on TYPO3 v13 and
v14.

#### Scenario: Two selected profiles elements on one page

- **WHEN** a page holds two selected profiles elements that both offer the
  switch, and a visitor chooses "Table" in the first one
- **THEN** the first element is shown as a table and the second one as tiles

#### Scenario: Two selected contracts elements on one page

- **WHEN** a page holds two selected contracts elements that both offer the
  switch, and a visitor chooses "Table" in the first one
- **THEN** the first element is shown as a table and the second one as tiles

#### Scenario: Choosing in the second element

- **WHEN** the first element is shown as a table and the visitor chooses
  "Table" in the second element
- **THEN** the second element is shown as a table and the first one in its
  default mode

#### Scenario: Elements of different types

- **WHEN** a page holds a selected profiles and a selected contracts element,
  and a visitor chooses "Table" in one of them
- **THEN** only that element is shown as a table

#### Scenario: Translated page

- **WHEN** a visitor chooses "Table" in the first selected element of a
  translated page
- **THEN** that element is shown as a table and the second one as tiles

#### Scenario: Link of the default language on the translated page

- **WHEN** a visitor opens the translated page with the arguments of the
  "Table" link of the same element in the default language
- **THEN** that element is shown as a table on the translated page
