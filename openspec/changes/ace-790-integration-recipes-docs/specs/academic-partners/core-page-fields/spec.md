## Purpose

Installing academic_partners leaves the fields TYPO3 itself defines on pages
as TYPO3 defines them in the backend, on every page type.

## ADDED Requirements

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
