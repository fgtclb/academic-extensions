## ADDED Requirements

### Requirement: The select offers every partner that is not deleted

The partner select of a partnership record SHALL offer every partner page of
the installation that is not deleted, whether it is hidden, its start time
lies in the future or its end time has passed. A backend editor chooses among
records that are not publicly visible yet or any more, as in the record
selects of the core. The set of partners offered MUST be the same on TYPO3
v12 and v13.

#### Scenario: A hidden partner is offered

- **WHEN** an installation has a hidden partner page and an editor opens a
  partnership record
- **THEN** the select offers that partner, in its place in the alphabetical
  order, on TYPO3 v12 and v13

#### Scenario: A partner before its start time is offered

- **WHEN** an installation has a partner page whose start time lies in the
  future
- **THEN** the select offers that partner

#### Scenario: A partner after its end time is offered

- **WHEN** an installation has a partner page whose end time has passed
- **THEN** the select offers that partner

#### Scenario: A partnership with a partner that is not visible keeps it

- **WHEN** an editor opens a partnership record whose partner page is hidden,
  starts in the future or has expired, and saves it without touching the
  partner field
- **THEN** the select shows that partner as chosen, and the record still
  refers to it after the save, on TYPO3 v12 and v13

#### Scenario: Deleted partners are not offered

- **WHEN** an installation has a deleted partner page
- **THEN** the select does not offer it

## MODIFIED Requirements

### Requirement: Ordering changes nothing that is stored

Ordering the select SHALL NOT change which partners are offered, and MUST NOT
change the partner a record refers to. Which partners are offered is decided
by the requirement "The select offers every partner that is not deleted".

#### Scenario: An unchanged save keeps the stored partner

- **WHEN** an editor opens an existing partnership record and saves it without
  touching the partner field
- **THEN** the record still refers to the same partner as before

#### Scenario: Deleted partners stay out of the list

- **WHEN** an installation has deleted partner pages
- **THEN** the select does not offer them, exactly as before

#### Scenario: Hidden partners keep the visibility they had, per core version

- **WHEN** an installation has hidden partner pages
- **THEN** the select offers them, in their place in the alphabetical order,
  on TYPO3 v13 as before, and on TYPO3 v12 as well, where it did not offer
  them before
