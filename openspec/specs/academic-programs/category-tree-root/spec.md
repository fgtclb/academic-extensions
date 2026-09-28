# academic-programs/category-tree-root Specification

## Purpose

Defines where the category trees offered to editors of program pages, of the
program list element and of the program finder element start, configured per
site.

## Requirements

### Requirement: The category tree starts at the configured root
When the site setting for the program category root holds one or more
category uids, the category tree of a program page, the tree of the default
categories of the program list element and the tree of the preselected
categories of the program finder element on a page of that site SHALL offer
only those categories and their descendants. This SHALL apply on TYPO3 v13 and
v14.

#### Scenario: Program page on a configured site
- **WHEN** the site setting holds the uid of the category "Programs" and an
  editor opens a program page of that site
- **THEN** the category tree of the page starts at "Programs", and "Programs"
  itself can be selected

#### Scenario: Program list and program finder on a configured site
- **WHEN** the same setting is set and an editor edits a program list element
  or a program finder element on a page of that site
- **THEN** the tree of the element's category field starts at "Programs"

#### Scenario: Several roots
- **WHEN** the setting holds the uids of two categories
- **THEN** both categories are offered, each selectable, below a top node that
  cannot be selected

#### Scenario: A site that depends on a component set only
- **WHEN** a site depends on the program list set only and writes the setting
  to its settings as a nested tree
- **THEN** the trees of its program pages and elements start at the named
  category

#### Scenario: Page TSconfig of a project
- **WHEN** page TSconfig sets the starting points of the page category field
  and the site setting names another category
- **THEN** the tree starts where page TSconfig says

### Requirement: Without a category in the setting the tree stays as it was
The category trees of program pages, of the program list element and of the
program finder element SHALL show the complete category tree, with a top node
that cannot be selected, when the setting is empty, not set, names no category
uid, or the record is not part of a site. The category tree of a standard page
SHALL never be affected by the setting.

#### Scenario: Setting left empty
- **WHEN** a site does not set the program category root
- **THEN** editors see the whole category tree, as before this change

#### Scenario: A value that is not a uid
- **WHEN** the setting holds text that is not a category uid
- **THEN** the value is ignored and the whole category tree is offered

#### Scenario: A standard page on a configured site
- **WHEN** the site setting is set and an editor opens a standard page of that
  site
- **THEN** the page's category tree is the whole tree

### Requirement: A category outside of the root survives an untouched tree
A category a program page carries outside of the configured root SHALL stay
assigned when the page is saved in the backend form without a change to the
category tree.

#### Scenario: Saving a page with a category outside of the root
- **WHEN** a program page carries a category outside of the configured root
  and an editor opens and saves it without changing the category selection
- **THEN** the category is still assigned, although the tree does not show it
