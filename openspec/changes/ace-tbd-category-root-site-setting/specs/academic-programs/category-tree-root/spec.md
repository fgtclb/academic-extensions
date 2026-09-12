## Purpose

Defines where the category trees offered to editors of program pages and of
the program list element start, configured per site.

## ADDED Requirements

### Requirement: The category tree starts at the configured root
When the site setting for the program category root holds one or more
category uids, the category tree of a program page and the category tree of
the program list element on a page of that site SHALL offer only those
categories and their descendants. This SHALL apply on TYPO3 v13 and v14.

#### Scenario: Program page on a configured site
- **WHEN** the site setting holds the uid of the category "Programs" and an
  editor opens a program page of that site
- **THEN** the category tree of the page starts at "Programs"

#### Scenario: Program list element on a configured site
- **WHEN** the same setting is set and an editor edits a program list element
  on a page of that site
- **THEN** the tree of the element's category field starts at "Programs"

#### Scenario: Several roots
- **WHEN** the setting holds the uids of two categories
- **THEN** both categories are offered as roots of the tree

### Requirement: Without the setting the whole tree is offered
The category trees of program pages and of the program list element SHALL
show the complete category tree when the setting is empty, not set, or the
record is not part of a site.

#### Scenario: Setting left empty
- **WHEN** a site does not set the program category root
- **THEN** editors see the whole category tree, as before this change

#### Scenario: A value that is not a uid
- **WHEN** the setting holds text that is not a category uid
- **THEN** the value is ignored and the whole category tree is offered
