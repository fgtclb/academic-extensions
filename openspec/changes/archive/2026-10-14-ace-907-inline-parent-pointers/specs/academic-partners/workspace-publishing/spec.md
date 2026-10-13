## Purpose

Defines that the partnerships of a partner page or a partner role stay with
that page or role when it is published from a workspace or copied.

## ADDED Requirements

### Requirement: Partnerships keep their page and role when either is published
When a partner page or a partner role is edited in a workspace and the
workspace is published, from the workspace module or on its publish date, and
also when the page and the role were edited in the same workspace, its
partnerships SHALL still belong to it, on TYPO3 v13 and v14.

#### Scenario: Partner page edited in a workspace
- **WHEN** an editor changes a partner page with partnerships in a workspace
  and publishes the workspace
- **THEN** the page still has its partnerships

#### Scenario: Partner role edited in a workspace
- **WHEN** an editor changes a partner role in a workspace and publishes the
  workspace
- **THEN** the partnerships with that role still have it

#### Scenario: Page and role edited in the same workspace
- **WHEN** an editor changes a partner page and the partner role of its
  partnerships in the same workspace and publishes it from the workspace
  module
- **THEN** the partnerships still belong to the page and have the role

#### Scenario: Workspace published on its publish date
- **WHEN** an editor changes a partner page with partnerships in a workspace
  and the workspace is published on its publish date
- **THEN** the page still has its partnerships

### Requirement: Copies get their own partnerships
Copying a partner page or a partner role SHALL give the copied partnerships the
copied page or role, and SHALL leave the partnerships of the original with the
original, on TYPO3 v13 and v14.

#### Scenario: Partner page copied
- **WHEN** an editor copies a partner page with partnerships
- **THEN** the copied partnerships belong to the copied page, and those of the
  original to the original
