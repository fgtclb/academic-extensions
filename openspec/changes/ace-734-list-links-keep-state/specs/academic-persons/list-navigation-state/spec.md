## Purpose

Defines which choices of a visitor survive a click on a pagination or letter
link of the persons list and listanddetail plugins.

## ADDED Requirements

### Requirement: Pagination links keep the visitor's list choices
Every pagination link SHALL carry every list value the plugin accepted from
the visitor on the current page, such as a view mode or a filter, and SHALL
change only the page number. This applies on TYPO3 v13 and v14.

#### Scenario: Paging a list shown in another view mode
- **WHEN** a visitor chose a view mode other than the default and follows the
  link to page 2
- **THEN** page 2 is rendered in the chosen view mode

#### Scenario: Paging a filtered list
- **WHEN** a visitor filtered the list and follows the link to the next page
- **THEN** the next page shows the next profiles of the filtered list

### Requirement: Letter links keep the other list choices
Every letter link and the link back to all letters SHALL carry the visitor's
other accepted list values, SHALL change only the letter, and SHALL lead to
the first page.

#### Scenario: Choosing a letter in a filtered list
- **WHEN** a visitor filtered the list and selects the letter B
- **THEN** the list shows the filtered profiles whose last name starts with B

#### Scenario: Returning to all letters
- **WHEN** a visitor with an active letter and a chosen view mode follows the
  link back to all letters
- **THEN** the unfiltered list is rendered in the chosen view mode

### Requirement: Only accepted values are carried into links
Pagination and letter links MUST NOT carry query parameters the plugin does
not accept from a visitor.

#### Scenario: Foreign query parameter on the list page
- **WHEN** a visitor opens the list page with an unrelated query parameter
- **THEN** no pagination or letter link contains that parameter

#### Scenario: Plugin value an editor controls
- **WHEN** a request carries a sorting value that only the content element
  may set
- **THEN** no pagination or letter link contains that value
