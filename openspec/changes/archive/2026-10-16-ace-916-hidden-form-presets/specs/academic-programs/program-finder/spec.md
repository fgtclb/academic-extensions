## MODIFIED Requirements

### Requirement: Submitting opens the list pre-filtered
Submitting the program finder SHALL open the target page, whose program list
SHALL show only the programs matching every selected category, in the
sorting configured for that list, and SHALL show the selection in its own
filter unless the list hides it. The list SHALL keep its default categories of
every category type the finder does not offer. A list that hides its category
filter SHALL keep all of its default categories, and the selection of the
finder SHALL narrow it further. This applies to TYPO3 v13 and v14 alike.

#### Scenario: Visitor searches for Master programs
- **WHEN** a visitor selects the degree "Master" and submits the finder
- **THEN** the target page opens, its program list shows only Master
  programs, and its degree filter shows "Master"

#### Scenario: The target list is sorted by title, descending
- **WHEN** the target list is configured to sort by title, descending, and a
  visitor submits the finder
- **THEN** the list shows the matching programs by title, descending

#### Scenario: The target list presets a category type the finder does not offer
- **WHEN** the finder offers the degree and the topic, the target list shows
  its category filter and presets the location "Berlin"
- **AND** a visitor selects the degree "Master of Science" and submits the
  finder
- **THEN** the list shows only Master of Science programs in Berlin

#### Scenario: The target list presets a category type the finder offers
- **WHEN** the target list shows its category filter and presets the degree
  "Bachelor of Science"
- **AND** a visitor selects the degree "Master of Science" and submits the
  finder
- **THEN** the list shows the Master of Science programs, and its degree
  filter shows "Master of Science"

#### Scenario: The target list hides its category filter
- **WHEN** the target list hides its category filter and presets the location
  "Berlin"
- **AND** a visitor selects the degree "Master of Science" and submits the
  finder
- **THEN** the list shows only Master of Science programs in Berlin
