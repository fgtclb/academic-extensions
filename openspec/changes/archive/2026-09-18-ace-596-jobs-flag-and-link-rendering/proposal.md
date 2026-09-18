## Why

The job detail view and the job list print the two job flags "internationals
welcome" and "recommended by alumni" with an empty label and the raw value `1`,
and print the job's link as plain text, which for a page link is a literal
`t3://` reference. Projects replace the labels and the link handling in
overrides.

This is the backport of ACE-596, merged on `main` for 3.0.0
(`openspec/changes/archive/2026-09-18-ace-596-jobs-flag-and-link-rendering`
there). It is re-derived against this branch: both partials and both templates
are byte-identical to `main` before the fix, the XLF files are indented with
tabs here, and this extension has no list and detail plugin test on this branch,
so that test class is written as part of the backport.

## What Changes

- The detail view shows a translated label (English and German) for each of
  the two flags, and only when the flag is set; the raw value is not printed.
- The job's link is rendered as a working link with a translated link text,
  for external URLs as well as links to pages, files or records.
- The job list renders the flags and the link of each job the same way; its
  item partial carries the same defect.
- The row label "Link" stays as it is and precedes the new anchor.

The behaviour is identical on TYPO3 v12 and v13.

## Capabilities

### New Capabilities

- `academic-jobs/job-detail`: how the job detail view and the job list
  present the job's flags and link.

### Modified Capabilities

None.

## Impact

- `academic_jobs` (`packages/fgtclb/academic-jobs`): the partials
  `Resources/Private/Partials/Job/Information.html` and
  `Resources/Private/Partials/Job/Item.html`, and the frontend labels
  `Resources/Private/Language/locallang.xlf` and `de.locallang.xlf`.
- Visible output: in the detail view and the list, the link text changes
  from the stored value to a label.
- The first list and detail plugin test of this extension on this branch, and
  an `Important-` changelog entry in `Documentation/Changelog/2.4/`.

## Non-goals

- German labels for the backend (plugin titles, job type setting); tracked
  separately as ACE-368.
- A configurable list of detail fields.
- Offering the flags in the new-job form.

## Source

Backport of ACE-596, derived from the project differences analysis of
2026-09-12 (candidate `listings-05`). The file-level analysis is
`.agent/reports/project-differences-2026-09/BACKPORT-596-jobs-flag-and-link.md`
in the working tree of the maintainer.
