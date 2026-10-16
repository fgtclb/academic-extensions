## MODIFIED Requirements

### Requirement: The finder offers the configured category types in order
The program finder SHALL render one select per configured category type, in
the configured order. When the editor configured none, it SHALL offer the
site-wide filter types of the program list, and the degree and the topic, in
that order, when those are not set either. When neither the degree nor the
topic has a category, the finder SHALL offer every category type with
categories instead, in the type order of the group, as the program list does
without filter types. Category types the editor or the site configured SHALL
NOT be replaced that way. This applies to TYPO3 v13 and v14 alike.

#### Scenario: Default configuration
- **WHEN** an editor adds a finder and leaves the category types empty
- **AND** the site sets no filter types for the program list
- **THEN** the finder shows a degree select followed by a topic select

#### Scenario: Default configuration without a topic
- **WHEN** an editor leaves the category types of a finder empty
- **AND** the site sets no filter types for the program list
- **AND** no category of the site is a topic
- **THEN** the finder shows a degree select and no other select

#### Scenario: Default configuration on a site that names its types differently
- **WHEN** an editor leaves the category types of a finder empty
- **AND** the site sets no filter types for the program list
- **AND** the site has no degree and no topic, but categories of a
  qualification, a subject and a location type
- **THEN** the finder shows a select for each of those three types, in the
  type order of the group

#### Scenario: Site-wide filter types
- **WHEN** an editor leaves the category types of a finder empty
- **AND** the site-wide filter types of the program list name location and
  degree, in that order
- **THEN** the finder shows a location select followed by a degree select

#### Scenario: Site-wide filter types of which only one exists
- **WHEN** an editor leaves the category types of a finder empty
- **AND** the site-wide filter types of the program list name location and
  degree, and the site has no degree
- **THEN** the finder shows a location select and no other select

#### Scenario: Site-wide filter types none of which has a category
- **WHEN** an editor leaves the category types of a finder empty
- **AND** the site-wide filter types of the program list name the degree
  only, and the site has no degree
- **THEN** the finder renders no form

#### Scenario: Configured types
- **WHEN** the editor chooses the types topic and degree, in that order
- **AND** the site-wide filter types of the program list name location and
  degree
- **THEN** the finder shows a topic select followed by a degree select and no
  other select

#### Scenario: No offered type has a category
- **WHEN** none of the category types the finder offers has a category
- **THEN** the finder renders no form
