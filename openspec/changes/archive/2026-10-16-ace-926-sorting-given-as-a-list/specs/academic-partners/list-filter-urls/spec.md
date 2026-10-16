## ADDED Requirements

### Requirement: A sorting value given as a list is ignored

The partner list SHALL ignore a sorting, a sorting field or a sorting
direction of a request that is a list of values where the form sends one
value, like an unknown value, in a link and in a submission, on TYPO3 v13 and
v14. Where the element shows the sorting selects, the list SHALL render with
the default of the extension in its place, title, A to Z, and the redirect of
a submission SHALL carry that default. Where the element hides them, its
preset sorting SHALL apply, as for any sorting of a request. The other values
of the request SHALL apply as they do beside an unknown value.

#### Scenario: A link carries the sorting field as a list

- **WHEN** a visitor opens a partner list with a link that carries the sorting
  field as a list of one value
- **THEN** the list shows its partners sorted by title, A to Z

#### Scenario: A submission carries the sorting direction as a list

- **WHEN** a submission of the form of a partner list carries the sorting
  direction as a list of one value
- **THEN** the response is a 303 redirect whose target carries the sorting
  title, ascending
