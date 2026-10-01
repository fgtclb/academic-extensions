# academic-partners/core-page-fields Specification

## Purpose
Installing academic_partners leaves the fields TYPO3 itself defines on pages
as TYPO3 defines them in the backend, on every page type.

## Requirements

### Requirement: The target of a link page keeps the definition of TYPO3
On TYPO3 v14, the target field of the page type "Link" SHALL keep the
definition of TYPO3 when academic_partners is installed: it is required, it
is editable for every editor group that may edit pages, and it offers the
link options of TYPO3.

#### Scenario: Editor edits a link page on TYPO3 v14
- **WHEN** an editor of a group that may edit pages, without further allowed
  fields, opens a page of the type "Link" on an installation with
  academic_partners on TYPO3 v14
- **THEN** the target field is shown and is required

### Requirement: The page description keeps the definition of TYPO3
The description of a page SHALL keep the definition of TYPO3 when
academic_partners is installed, on TYPO3 v13 and v14: it is a field an editor
group needs to be allowed, and a translation of a page gets the prefix of
TYPO3 in it. On a partner page the field SHALL be labelled as the description
of the partner.

#### Scenario: Editor without the allowed field opens a standard page
- **WHEN** an editor of a group that may edit pages, without the description
  in its allowed fields, opens a standard page on an installation with
  academic_partners
- **THEN** the description is not shown

#### Scenario: Editor opens a partner page
- **WHEN** an editor allowed to edit the description opens a partner page
- **THEN** the field is labelled as the description of the partner
