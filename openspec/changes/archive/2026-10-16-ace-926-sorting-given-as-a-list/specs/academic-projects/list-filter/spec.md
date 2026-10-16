## ADDED Requirements

### Requirement: A value given as a list is ignored

Both project list plugins SHALL ignore a sorting, a sorting field, a sorting
direction or an active state of a request that is a list of values where the
form sends one value, like an unknown value, in a submission and in a link, on
TYPO3 v12 and v13. Where the element shows that part of the form, the list
SHALL render with the default of the extension in its place, title, A to Z,
and all projects. Where the element hides that part, its preset SHALL apply,
as for any value of a request. The other values of the request SHALL apply as
they do beside an unknown value.

#### Scenario: A submission carries the active state as a list

- **WHEN** a submission of the form of a project list carries the active state
  as a list of one value
- **THEN** the list shows the projects of every state sorted by title, A to Z

#### Scenario: A link carries the sorting field as a list

- **WHEN** a visitor opens a project list with a link that carries the sorting
  field as a list of one value
- **THEN** the list shows all projects sorted by title, A to Z

#### Scenario: The other values of a submission still apply

- **WHEN** a submission carries the sorting field as a list of one value, the
  direction descending and the active state "Completed"
- **THEN** the list shows the completed projects only
