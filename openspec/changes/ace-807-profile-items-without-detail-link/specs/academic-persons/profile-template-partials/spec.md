## ADDED Requirements

### Requirement: Items can be rendered without a detail link

The system SHALL offer a site wide setting with the choices "link" and "no
link" that decides whether the name of a profile item of the list, card,
selected profiles and selected contracts elements links to the detail view,
in every view mode of those elements.
It SHALL default to "link", so that every site links its items as before.
Each of those four elements SHALL offer the same choice in its plugin options,
empty by default. An empty choice SHALL use the site setting, and a chosen
value SHALL win over the site setting in both directions. The list-and-detail
element SHALL link its items whatever the settings say, and an item rendered
with an explicitly passed detail page SHALL link to that page. This SHALL
apply on TYPO3 v13 and v14.

#### Scenario: A site without detail pages

- **WHEN** a site sets the site setting to "no link" and a persons list with
  an empty choice shows a profile
- **THEN** the profile's name is shown without a link

#### Scenario: A list shown as a table

- **WHEN** a site sets the site setting to "no link" and a persons list with
  an empty choice shows its profiles as a table
- **THEN** the name column shows the names without a link

#### Scenario: Nothing is configured

- **WHEN** a site keeps the default and configures no detail page
- **THEN** every name links to the current page with the arguments of the
  detail view, as before

#### Scenario: One element without a link

- **WHEN** a site keeps the site setting at "link" and an editor chooses "no
  link" for one selected profiles element
- **THEN** that element shows the names without a link
- **AND** every other element of the site links them

#### Scenario: One element with a link

- **WHEN** a site sets the site setting to "no link" and an editor chooses
  "link" for one persons list
- **THEN** that list links the names to the detail view

#### Scenario: The list-and-detail element

- **WHEN** a site sets the site setting to "no link" and a list-and-detail
  element shows a profile
- **THEN** the profile's name still links to its detail view on that page
- **AND** the plugin options of that element offer no choice

#### Scenario: A template passes the detail page

- **WHEN** a site sets the site setting to "no link" and a template renders a
  profile item with page 42 passed as the detail page
- **THEN** the name links to the profile's detail view on page 42

#### Scenario: Site set and static template agree

- **WHEN** a site uses the persons site set and sets the site setting, or uses
  the static template and sets the TypoScript constant
- **THEN** both render the items the same way
