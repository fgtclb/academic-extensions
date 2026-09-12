## Purpose

Keeps the content type of an existing academic content element intact when an
editor opens and saves it on a page where page TSconfig hides that type.

## ADDED Requirements

### Requirement: A hidden academic content type stays selected on its own record
The backend form SHALL offer the stored content type of an existing content
element as the selected option of the Type field when the type belongs to the
academic content element group and page TSconfig hides it on the element's
page. The option SHALL carry the type's label followed by "(not enabled on
this page)". This applies on TYPO3 v13 and v14.

#### Scenario: Editor opens a profile list on a page without the persons set
- **WHEN** an editor opens an existing "Profile list" content element on a
  page whose site does not enable the academic_persons component set
- **THEN** the Type field shows the profile list, with the suffix "(not
  enabled on this page)", as the selected option

#### Scenario: Editor saves without touching the type
- **WHEN** the editor saves that content element without changing the Type
  field
- **THEN** the stored content type is still the profile list

#### Scenario: Editor changes the type deliberately
- **WHEN** the editor selects "Regular text element" and saves
- **THEN** the stored content type is the text element
- **AND** on reopening, the Type field offers no academic content type

### Requirement: Hidden academic types stay hidden everywhere else
The system MUST NOT offer an academic content type that page TSconfig hides
for a new content element, or for an existing content element that stores a
different type.

#### Scenario: New content element on a page without the set
- **WHEN** an editor creates a content element on a page where academic
  content types are hidden
- **THEN** the Type field offers no academic content type

#### Scenario: Other content element on the same page
- **WHEN** an editor opens an existing text element on that page
- **THEN** the Type field offers no academic content type

### Requirement: Other content types keep core behaviour
The system MUST NOT change the Type field of a content element whose stored
type is outside the academic content element group, even when page TSconfig
hides that type.

#### Scenario: Hidden type of another extension
- **WHEN** an editor opens a content element whose type belongs to another
  extension and is hidden by page TSconfig on its page
- **THEN** the Type field behaves exactly as TYPO3 renders it without
  academic_base
