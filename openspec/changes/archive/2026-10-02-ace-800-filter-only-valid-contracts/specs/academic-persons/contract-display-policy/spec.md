## MODIFIED Requirements

### Requirement: Editors can show only contracts valid today

The system SHALL offer editors of the list, list-and-detail, card and
selected-profiles plugins an option that limits the shown contracts to those
whose validity period includes the date the page is rendered for. A missing start
date and a missing end date SHALL each count as open-ended.

While the option is on, a list or list-and-detail element that selects profiles
by their contracts, through its restriction to function types or
organisational units or through a visitor filter, SHALL select a profile only
through a contract valid today. Its pagination and its letter navigation SHALL
count the same profiles. Without such a condition the list SHALL select its
profiles as without the option, and a profile with no contract valid today
SHALL stay listed.

#### Scenario: Expired and current contract

- **WHEN** the option is on and a profile has one contract that ended last year
  and one without an end date
- **THEN** only the contract without an end date is shown

#### Scenario: Contract without an end date

- **WHEN** the option is on and a contract started last year and has no end
  date
- **THEN** the contract is shown

#### Scenario: Visitor filter by an ended contract

- **WHEN** the option is on and a visitor filters the list by a function type
  that a profile carries only on a contract that ended last year
- **THEN** that profile is not listed

#### Scenario: Restriction by a contract that has not started

- **WHEN** the option is on, the element is restricted to an organisational
  unit and a profile has a contract in that unit that starts next month
- **THEN** that profile is not listed until the contract starts

#### Scenario: Filter by a current contract

- **WHEN** the option is on and a visitor filters by a function type a profile
  carries on a contract valid today
- **THEN** that profile is listed

#### Scenario: Option off

- **WHEN** the option is off and a visitor filters by a function type a profile
  carries only on a contract that ended last year
- **THEN** that profile is listed, as before

#### Scenario: List without conditions on contracts

- **WHEN** the option is on, the element has no restriction, no visitor filter
  is active and a profile has only a contract that ended last year
- **THEN** that profile is listed without a contract

### Requirement: Validity takes effect on the day it changes

While the output depends on contracts being valid today, the system SHALL
cache the page no longer than until the next day on which a shown contract
ends or a contract left out because it has not started yet begins. While a
list selects its profiles only through contracts valid today, the system SHALL
also cache the page no longer than until the next day on which a contract that
meets the list's conditions on contracts starts or ends. Without a validity
option the page cache lifetime SHALL stay unchanged.

#### Scenario: Shown contract ends today

- **WHEN** a list plugin shows only contracts valid today and a listed
  contract ends today
- **THEN** the page is not served from the cache after the end of today

#### Scenario: Contract starts tomorrow

- **WHEN** a list plugin shows only contracts valid today and a contract
  starts tomorrow
- **THEN** the page rendered tomorrow shows that contract

#### Scenario: Matching contract of an unlisted profile starts tomorrow

- **WHEN** a list filtered by a function type shows only contracts valid today
  and a profile not listed today has a contract of that function type that
  starts tomorrow
- **THEN** the page rendered tomorrow lists that profile

#### Scenario: No validity option

- **WHEN** no validity option applies to any contract on the page
- **THEN** the page cache lifetime is the one the page has without the option
