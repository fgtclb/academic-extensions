## ADDED Requirements

### Requirement: A sorting value given as a list is ignored

The program list SHALL ignore a sorting, a sorting field or a sorting
direction of a request that is a list of values where the form sends one
value, like an unknown value, in a submission and in a link, on TYPO3 v12 and
v13. Where the element shows the sorting selects, the list SHALL render with
the default of the extension in its place, title, A to Z. Where the element
hides them, its preset sorting SHALL apply, as for any sorting of a request.
The other values of the request SHALL apply as they do beside an unknown
value.

#### Scenario: A submission carries the sorting field as a list

- **WHEN** a submission of the form of a program list carries the sorting
  field as a list of one value
- **THEN** the list shows its programs sorted by title, A to Z

#### Scenario: A link carries the sorting direction as a list

- **WHEN** a visitor opens a program list with a link that carries the sorting
  direction as a list of one value
- **THEN** the list shows its programs sorted by title, A to Z

#### Scenario: The direction beside a sorting field given as a list

- **WHEN** a submission carries the sorting field as a list of one value and
  the direction descending
- **THEN** the list shows its programs sorted by title, Z to A
