## Why

Five projects integrate the academic extensions with the same third-party
tools, and each got it wrong in its own way. Four EXT:solr profile indexes
read `free_field`, a column no version of `academic_persons` has, and build
the detail links by hand. Two projects maintain permission sets by hand. And
there is no documented way to relabel or reorder the academic content elements
in the new content element wizard, which TYPO3 v13 and v14 build from TCA.

## What Changes

- A new `Documentation/Integration/` chapter in `academic_base`
  (`packages/fgtclb/academic-base`) with three pages:
  - the new content element wizard: where the academic group appears and
    why, moving and relabelling it, relabelling and hiding its elements, and
    ordering them, with `before` and `after` on TYPO3 v14 and by
    defining the elements again on TYPO3 v13.
  - EXT:solr 13.1: an index queue for the profiles of `academic_persons`
    (`packages/fgtclb/academic-persons`) using columns that exist, with the
    detail link built from the detail plugin's arguments, and page queues for
    program pages (doktype 20, `academic_programs`,
    `packages/fgtclb/academic-programs`), project pages (doktype 30,
    `academic_projects`, `packages/fgtclb/academic-projects`) and partner
    pages (doktype 40, `academic_partners`,
    `packages/fgtclb/academic-partners`).
  - permission sets for `b13/permission-sets`: one example per extension
    with its tables, the `exclude` fields it adds to tables of TYPO3, its
    content types and its page types, as documentation only.
- Links to the chapter from the persons, programs, projects and partners
  manuals.
- A functional test in `academic_base` pinning the wizard behaviour the
  wizard page describes, on TYPO3 v13 and v14.
- ACE-792: the page TSconfig of `academic_base` no longer places the academic
  group after "Special elements". That position made TYPO3 order every group
  by position, so the wizard opened on "Special elements" on every
  installation. The group now follows the groups of TYPO3, in the order of
  their registration.
- ACE-791: on TYPO3 v14, `academic_partners` no longer replaces the field
  `link` TYPO3 defines on `pages` for the page type "Link". It adds its own
  column of that name on TYPO3 v13 only, which has none.

## Capabilities

### New Capabilities

- `academic-base/content-element-wizard-group`: where the academic group
  appears in the new content element wizard, and what a site changes about it
  by page TSconfig.
- `academic-partners/core-page-fields`: the fields TYPO3 defines on `pages`
  keep their definition when `academic_partners` is installed.

### Modified Capabilities

None.

## Impact

- A documentation chapter in `academic_base`, links in four more manuals.
- The page TSconfig of the academic group in `academic_base`, with an
  `Important` changelog entry.
- The TCA of `pages` in `academic_partners`, with an `Important` changelog
  entry. No database change: the stored values stay where they are.
- One functional test and one fixture extension in `academic_base`, one
  functional test in `academic_partners`, and the test counts in
  `docs/testing/functional-tests.md`.
- No PHP class and no dependency change.

## Non-goals

- Shipping EXT:solr or permission set configuration, as files or as opt-in
  sets. That adds dependencies on third-party release cycles.
- Numbering the academic content elements upstream (ACE-286).
- Testing the EXT:solr and permission set recipes in this repository, which
  installs neither package.
- A backport to branch `2`: the recipes are written against 3.0 paths.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`cross-cutting-18`). Five of the six analysed projects carry their own code for
this today. Filed as ACE-790.

Relates to ACE-286.
