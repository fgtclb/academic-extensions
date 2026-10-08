# academic-persons/profile-detail Specification

## Purpose
Defines how the detail view of the "Persons Detail" and "Persons List and
Detail" content elements answers a visitor who asks for a profile it cannot
show.

## Requirements

### Requirement: A detail page without a profile answers not found

The detail view SHALL end a request without a profile it can show, because no
profile was requested, the profile does not exist or the profile is hidden,
with the "page not found" handling of the site and the status 404, instead of
the page. The error page MUST NOT be rendered inside the content element. This
SHALL behave the same on TYPO3 v13 and v14.

#### Scenario: Detail page without a profile

- **WHEN** a visitor opens the profile detail page without a profile
- **THEN** the answer has the status 404 and is the error page of the site
- **AND** it does not contain the content element of the detail page

#### Scenario: Speaking detail URL another plugin resolves

- **WHEN** a visitor opens a speaking profile URL on a detail page whose route
  enhancer is not limited to its own page, so the profile reaches another
  plugin
- **THEN** the detail page answers with the status 404
