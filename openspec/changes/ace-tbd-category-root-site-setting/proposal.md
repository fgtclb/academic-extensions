## Why

Editors of program pages and of the program list element pick categories from
the whole `sys_category` tree, although only one branch of it holds program
categories. One project hard-codes that branch per application context in
TSconfig (one uid live, another in development), and another hard-codes a uid
in TCA. Neither survives copying a database between environments, and both
are project code for something the core already resolves from site settings.

## What Changes

- New site setting `plugin.tx_academicprograms.categoryRootUids`
  (comma-separated category uids, empty by default) in `academic_programs`
  (`packages/fgtclb/academic-programs`), keyed by the constant path like the
  other programs settings.
- The category tree of program pages (page type 20) and the category tree of
  the program list element start at those categories on pages of that site.
- Without the setting both trees show the whole category tree, as today.

Behaviour is identical on TYPO3 v13 and v14. The core feature it relies on was
verified in the installed 13.4.35 and 14.3.6 sources; the candidate listed v14
as unverified.

## Capabilities

### New Capabilities

- `academic-programs/category-tree-root`: where the category trees of program
  pages and of the program list element start, per site.

### Modified Capabilities

None.

## Impact

- `Configuration/TCA/Overrides/pages.php` (an override of the `categories`
  field for page type 20) and `Configuration/FlexForms/ProgramListSettings.xml`.
- A new `settings.definitions.yaml` on the aggregate set
  `fgtclb/academic-programs`.
- No database change.

## Non-goals

- The same setting for `academic_partners` and `academic_projects`; the
  listings family proposes it for their page types, one setting per
  extension under the same pattern.
- Restricting which categories the frontend filter offers; that follows the
  programs in storage.
- Backporting to branch `2`: TYPO3 v12 has no site settings definitions.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`programs-studyplan-14`). Two of the six analysed projects carry their own
code for this today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-category-root-site-setting` when the issue is filed after
implementation.
