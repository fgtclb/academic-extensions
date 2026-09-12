## Purpose

Defines how a visitor chooses a function type or organisational unit filter
for the persons list, and which URL the filtered list has.

## ADDED Requirements

### Requirement: The filter form appears only for enabled filters
The list SHALL render a filter form above the profiles when at least one
visitor filter is enabled for the plugin, with one choice per enabled filter.
Without an enabled filter the list MUST render no filter form. This applies
on TYPO3 v13 and v14.

#### Scenario: Function type filter enabled
- **WHEN** an editor enabled only the function type filter
- **THEN** the list shows a form with a function type choice and no
  organisational unit choice

#### Scenario: No filter enabled
- **WHEN** no visitor filter is enabled
- **THEN** the list renders no filter form

### Requirement: The choices offer every allowed value by name
Each choice SHALL offer an "all" entry first and then the allowed records in
alphabetical order of their names, and SHALL preselect the active filter.

#### Scenario: Editor restriction
- **WHEN** the editor restricted the plugin to two function types
- **THEN** the choice offers "all" and exactly those two, ordered by name

#### Scenario: Active filter preselected
- **WHEN** a visitor opens a list filtered by one organisational unit
- **THEN** that unit is the selected entry of the unit choice

### Requirement: Filtering works without JavaScript
Submitting the form SHALL lead to the URL of the filtered list without
relying on JavaScript. The form MUST contain no inline script or event
handler attribute.

#### Scenario: Submission with JavaScript disabled
- **WHEN** a visitor without JavaScript chooses a function type and submits
- **THEN** the browser shows the list filtered by that function type

### Requirement: Filtered lists have speaking URLs
With the shipped route enhancer imported, the URL of a filtered list SHALL
contain the filter's name and the slug of the chosen record, also together
with a page number, with a letter, and with a chosen view mode. A record
without a slug SHALL remain reachable through a URL with query parameters.

#### Scenario: Speaking filter URL
- **WHEN** a visitor filters by the function type with the slug "professor"
- **THEN** the URL ends with a function type segment followed by "professor"
  and resolves to the filtered list

#### Scenario: Filter combined with a letter
- **WHEN** a visitor filtered by the function type "professor" and selects
  the letter B
- **THEN** the URL carries the function type segment, "professor" and the
  letter, and resolves to the filtered list of last names starting with B

#### Scenario: Filter in the table view
- **WHEN** a visitor switched the list to the table and filters it by the
  function type "professor"
- **THEN** the URL carries the function type segment, "professor" and the
  view mode segment, and resolves to the filtered list rendered as a table

#### Scenario: Unknown slug
- **WHEN** a visitor requests a filter URL with a slug no record carries
- **THEN** the site answers with its page-not-found response

#### Scenario: Record without a slug
- **WHEN** a visitor filters by a record whose slug is empty
- **THEN** the filtered list is shown under a URL with query parameters

### Requirement: Existing filter records get their slugs in one step
The system SHALL offer a console command that gives every function type and
organisational unit without a slug the slug a save would generate from its
name. It MUST NOT change a slug that is already set, and running it again
SHALL change nothing. The upgrade itself SHALL NOT require an upgrade wizard.

#### Scenario: Command run after the upgrade
- **WHEN** an integrator runs the command on an installation whose function
  types have no slugs
- **THEN** every function type has a slug, and its filtered list has a
  speaking URL

#### Scenario: Slug already set
- **WHEN** an editor already set the slug of an organisational unit and the
  integrator runs the command
- **THEN** that slug is unchanged
