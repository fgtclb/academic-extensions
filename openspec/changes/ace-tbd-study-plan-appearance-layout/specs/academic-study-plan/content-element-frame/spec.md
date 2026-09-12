## Purpose

Defines how the study plan content element applies the appearance settings
that all content elements share: frame, spacing, anchor and header.

## ADDED Requirements

### Requirement: Appearance settings are applied
The study plan element SHALL apply the frame, space before and space after
the editor chose on the Appearance tab in the same way the site's other
content elements do, on TYPO3 v13 and v14.

#### Scenario: Frame and spacing chosen
- **WHEN** an editor sets the frame "Ruler before" and the space before
  "Large" on a study plan element
- **THEN** the rendered element carries the frame and spacing classes the
  site's layout uses for those choices

#### Scenario: No frame
- **WHEN** an editor sets the frame "No frame"
- **THEN** the element renders without the frame wrapper, as other content
  elements do

### Requirement: The element can be linked to
The rendered study plan element SHALL carry the anchor `c<uid>` of its
record, so a link to the element jumps to it.

#### Scenario: Link to the element
- **WHEN** an editor links to the study plan element from another page
- **THEN** the target page contains the anchor of that element

### Requirement: The header is rendered once
The study plan element SHALL render its header exactly once, honouring the
header layout and the hidden header setting the editor chose.

#### Scenario: Header with a layout
- **WHEN** the element has a header with header layout 2
- **THEN** the page contains that header once, as a level 2 heading

#### Scenario: Hidden header
- **WHEN** the header is set to hidden
- **THEN** the page does not render the header

### Requirement: The interaction is unchanged
Rendering through the layout SHALL NOT change the category filter, the
semester toggles or the module dialogs of the study plan.

#### Scenario: Filtering after the change
- **WHEN** a visitor activates a category of the filter
- **THEN** the matching modules are highlighted as before
