# academic-partners/partner-selection Specification

## Purpose
Defines what an editor sees when choosing the partner of a partnership record:
which partners are offered, and in which order they appear.

## Requirements

### Requirement: The partner select is ordered alphabetically

The list of partners offered for a partnership record SHALL be ordered by the
partner title, ascending, using the collation of the language the backend is
displayed in. The order MUST NOT depend on the order in which the partner
pages were created, nor on the order in which they are arranged in the page
tree. This applies to every TYPO3 core version the branch supports.

#### Scenario: Partners are offered in alphabetical order

- **WHEN** an editor opens a partnership record and the installation has
  several partners whose titles are in a different order than the order the
  partner pages were created in
- **THEN** the select offers the partners ordered by their title, ascending

#### Scenario: The order ignores how the partner pages are arranged

- **WHEN** an editor rearranges the partner pages in the page tree and opens a
  partnership record afterwards
- **THEN** the select offers the partners in the same alphabetical order as
  before

#### Scenario: A title starting with a diacritic is ordered by its base letter

- **WHEN** the installation has a partner whose title starts with a diacritic
  such as `Ö` alongside partners starting with `O` and `P`
- **THEN** that partner appears between them rather than after every
  unaccented title

### Requirement: The placeholder entry stays at the top

The select SHALL keep its empty placeholder entry as the first entry of the
list, so that the entry standing for "no partner chosen" is not sorted in
among the partners.

#### Scenario: Placeholder before the first partner

- **WHEN** an editor opens a partnership record in an installation that has at
  least one partner
- **THEN** the first entry of the select is the empty placeholder, followed by
  the partners in alphabetical order

### Requirement: Ordering changes nothing that is stored

Ordering the select SHALL NOT change which partners are offered, and MUST NOT
change the partner a record refers to.

#### Scenario: An unchanged save keeps the stored partner

- **WHEN** an editor opens an existing partnership record and saves it without
  touching the partner field
- **THEN** the record still refers to the same partner as before

#### Scenario: Deleted partners stay out of the list

- **WHEN** an installation has deleted partner pages
- **THEN** the select does not offer them, exactly as before

#### Scenario: Hidden partners keep being offered

- **WHEN** an installation has hidden partner pages
- **THEN** the select offers them, in their place in the alphabetical order,
  exactly as before — the backend deliberately shows an editor records that are
  not publicly visible, and this change does not revisit that
