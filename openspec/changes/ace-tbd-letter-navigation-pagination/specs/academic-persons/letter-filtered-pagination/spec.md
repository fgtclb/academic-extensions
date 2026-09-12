## Purpose

Defines how the persons list and listanddetail plugins page through the
profiles of an active letter, and which URL a page of a letter has.

## ADDED Requirements

### Requirement: A list with an active letter is paginated
When the editor enabled pagination, the list SHALL paginate the profiles of
the active letter with the configured number of results per page, exactly as
it paginates a list without a letter. When pagination is disabled, the list
SHALL show every profile of the letter on one page, as before. This applies
on TYPO3 v13 and v14.

#### Scenario: Letter with more profiles than one page holds
- **WHEN** pagination is enabled with two results per page and three profiles
  have a last name starting with M
- **THEN** the list filtered by M shows two profiles and a page navigation

#### Scenario: Pagination disabled
- **WHEN** pagination is disabled and three profiles have a last name
  starting with M
- **THEN** the list filtered by M shows all three profiles and no page
  navigation

### Requirement: The page count reflects the letter's result
The number of pages SHALL be derived from the profiles of the active letter
alone. A page beyond the last page of the letter SHALL show the last page of
the letter.

#### Scenario: Page count of a letter
- **WHEN** pagination is enabled with two results per page, the list holds
  seven profiles and three of them have a last name starting with M
- **THEN** the list filtered by M offers exactly two pages

#### Scenario: Page number beyond the letter's pages
- **WHEN** a visitor requests page 5 of the list filtered by M, which has two
  pages
- **THEN** the list shows the second page of M

### Requirement: Page links under an active letter keep the letter
Every page link rendered under an active letter SHALL lead to the requested
page of the same letter.

#### Scenario: Following the link to the next page
- **WHEN** a visitor on the first page of the list filtered by M follows the
  link to the next page
- **THEN** the second page of the list filtered by M is shown

### Requirement: Changing the letter starts at the first page
A link to another letter, and the link back to all letters, SHALL lead to the
first page of the resulting list, whichever page the visitor is on.

#### Scenario: Choosing another letter on page 2
- **WHEN** a visitor on page 2 of the list filtered by M selects the letter S
- **THEN** the first page of the list filtered by S is shown

#### Scenario: Back to all letters from page 2
- **WHEN** a visitor on page 2 of the list filtered by M follows the link
  back to all letters
- **THEN** the first page of the unfiltered list is shown

### Requirement: A page of a letter has a readable URL
With the shipped route enhancer of the list or listanddetail plugin imported,
the URL of a page of an active letter SHALL name the letter followed by the
page, in the page word of the site language, and SHALL resolve to that page of
that letter. The URLs of a letter alone and of a page alone SHALL stay as they
are.

#### Scenario: Readable URL of a letter page
- **WHEN** the list page has the slug `/persons` in an English site and a
  visitor follows the link to page 2 under the letter M
- **THEN** the URL is `/persons/m/page-2`, and requesting it shows page 2 of
  the list filtered by M

#### Scenario: Readable URL in a German site language
- **WHEN** the same list is shown in a German site language
- **THEN** the URL of page 2 under the letter M ends with `/m/seite-2`

#### Scenario: Letter and page alone
- **WHEN** a visitor opens the list filtered by M, or page 2 of the list
  without a letter
- **THEN** the URLs are `/persons/m` and `/persons/page-2`, as before

#### Scenario: Detail URL of the listanddetail plugin
- **WHEN** a visitor follows a profile link of a listanddetail plugin whose
  enhancer carries the letter and page route
- **THEN** the profile detail is shown under its own speaking URL
