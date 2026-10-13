## Purpose

Defines that the page contacts of a page or a contacts role stay with that page
or role when it is published from a workspace or copied.

## ADDED Requirements

### Requirement: Page contacts keep their page and role when either is published
When a page or a contacts role is edited in a workspace and the workspace is
published, from the workspace module or on its publish date, and also when the
page and the role were edited in the same workspace, its page contacts SHALL
still belong to it, on TYPO3 v13 and v14, and the contacts list SHALL show
them.

#### Scenario: Page edited in a workspace
- **WHEN** an editor changes a page with page contacts in a workspace and
  publishes the workspace
- **THEN** the page still has its page contacts, and its contacts list shows
  them

#### Scenario: Contacts role edited in a workspace
- **WHEN** an editor changes a contacts role in a workspace and publishes the
  workspace
- **THEN** the page contacts with that role still have it

#### Scenario: Page and role edited in the same workspace
- **WHEN** an editor changes a page and the contacts role of its page
  contacts in the same workspace and publishes it from the workspace module
- **THEN** the page contacts still belong to the page and have the role

#### Scenario: Workspace published on its publish date
- **WHEN** an editor changes a page with page contacts in a workspace and the
  workspace is published on its publish date
- **THEN** the page still has its page contacts

### Requirement: Copies get their own page contacts
Copying a page or a contacts role SHALL give the copied page contacts the
copied page or role, and SHALL leave the page contacts of the original with the
original, on TYPO3 v13 and v14.

#### Scenario: Page copied
- **WHEN** an editor copies a page with page contacts
- **THEN** the copied page contacts belong to the copied page, and those of the
  original to the original
