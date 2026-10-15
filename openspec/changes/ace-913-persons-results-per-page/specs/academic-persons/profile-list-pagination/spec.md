## ADDED Requirements

### Requirement: An empty pagination field of the element takes the site value

When the person list or the person list and details element has pagination
switched on and its field "Results per Page" or "Number of Links" was saved
empty or as `0`, the system SHALL use the value the site configures, as if the
element did not carry the field. A value the editor entered SHALL win over the
value of the site. The value of the site is the TypoScript constant of the same
name on TYPO3 v12, and the constant or the site setting on TYPO3 v13. This
applies on TYPO3 v12 and v13.

#### Scenario: Empty results per page in a list

- **WHEN** a person list element with pagination switched on has an empty
  "Results per Page", the site sets two results per page, and a visitor opens
  the list of twelve profiles
- **THEN** the list renders without an error
- **AND** it shows two profiles on the first of six pages

#### Scenario: Empty results per page in a list and details element

- **WHEN** a person list and details element with pagination switched on has
  an empty "Results per Page", and the site sets two results per page
- **THEN** the list shows two profiles on the first page

#### Scenario: Empty results per page in a manual selection

- **WHEN** a person list element selects profiles by hand with pagination
  switched on and an empty "Results per Page", and the site sets two results
  per page
- **THEN** the list shows the first two profiles of the selection on the first
  page

#### Scenario: Results per page saved as zero

- **WHEN** a person list element with pagination switched on has "Results per
  Page" saved as `0`, and the site sets two results per page
- **THEN** the list shows two profiles on the first page

#### Scenario: Empty number of links

- **WHEN** a person list element with pagination switched on has an empty
  "Number of Links", the site sets three links, and the list has more pages
  than that
- **THEN** the page navigation links three page numbers

#### Scenario: The editor's value wins

- **WHEN** a person list element sets three results per page and the site sets
  two
- **THEN** the list shows three profiles on the first page

### Requirement: A number of results per page below one falls back to ten

The system SHALL paginate a person list with ten profiles per page when the
number of results per page that applies is below 1 or empty, whether it comes
from the element, the site or TypoScript, and SHALL NOT end the request with
an error. A number of page links below 1 SHALL fall back to five links. This
applies on TYPO3 v12 and v13.

#### Scenario: The site sets zero

- **WHEN** the site sets the results per page to `0` and a person list element
  with pagination switched on leaves the field empty, and the list has twelve
  profiles
- **THEN** the list renders without an error
- **AND** it shows ten profiles on the first of two pages

#### Scenario: The site sets an empty value

- **WHEN** the site sets an empty number of results per page and a person list
  element with pagination switched on leaves the field empty, and the list has
  twelve profiles
- **THEN** the list shows ten profiles on the first of two pages

#### Scenario: The element holds a negative number

- **WHEN** a person list element with pagination switched on has "Results per
  Page" saved as `-3`, and the list has twelve profiles
- **THEN** the list shows ten profiles on the first of two pages

#### Scenario: A manual selection with a number below one

- **WHEN** a person list element selects twelve profiles by hand with
  pagination switched on, the field is empty, and the site sets `0`
- **THEN** the list shows the first ten profiles of the selection on the first
  page

#### Scenario: The site sets zero links

- **WHEN** the site sets the number of links to `0` and the list has twelve
  pages
- **THEN** the page navigation links five page numbers

### Requirement: The shipped default is ten results per page

The system SHALL ship ten results per page as the default of the TypoScript
constant of the person list pagination on TYPO3 v12 and v13, and of the site
setting of the same name on TYPO3 v13. A person list element with pagination
switched on that sets no number of its own, on a site that sets none either,
SHALL show ten profiles per page. This applies on TYPO3 v12 and v13.

#### Scenario: Nothing sets the number

- **WHEN** a person list element with pagination switched on leaves "Results
  per Page" empty on a site that keeps the shipped settings, and the list has
  twelve profiles
- **THEN** the list shows ten profiles on the first of two pages

#### Scenario: An element saved without the field

- **WHEN** a person list and details element with pagination switched on was
  saved without a "Results per Page" field, on a site that keeps the shipped
  settings, and the list has twelve profiles
- **THEN** the list shows ten profiles on the first of two pages
