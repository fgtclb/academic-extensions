## MODIFIED Requirements

### Requirement: Options without results can be hidden

With the setting to hide options without results enabled, the program list
SHALL leave out options that no program of the current result carries, except
a selected one. With the option to include subcategories on, a program SHALL
count as carrying a category when it carries the category or any visible
subcategory of it in the programs group. A filter whose options are all left
out SHALL still be offered, with its "all" option only.

#### Scenario: Hide options without results

- **WHEN** the setting is enabled and the tuition fee is the only category of
  its type and no listed program carries it
- **THEN** the costs filter renders its "all" option and no option for the
  tuition fee

#### Scenario: Hide options without results, subcategories included

- **WHEN** the setting is enabled and the list includes subcategories
- **AND** a listed program carries "Bachelor of Science", a child of
  "Bachelor", and none carries "Bachelor" itself
- **THEN** the degree filter offers "Bachelor"
