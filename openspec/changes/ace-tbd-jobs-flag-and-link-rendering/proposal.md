## Why

The job detail view prints the two job flags "internationals welcome" and
"recommended by alumni" with an empty label and the raw value `1`, and prints
the job's link as plain text, which for a page link is a literal `t3://`
reference. Projects replace the labels and the link handling in overrides.

## What Changes

- The detail view shows a translated label (English and German) for each of
  the two flags, and only when the flag is set; the raw value is not printed.
- The job's link is rendered as a working link with a translated link text,
  for external URLs as well as links to pages, files or records.
- The job list renders the flags and the link of each job the same way; its
  item partial carries the same defect.
- The row label "Link" stays as it is and precedes the new anchor.

The behaviour is identical on TYPO3 v13 and v14.

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
- Functional tests of the list and detail plugins; an `Important-` changelog
  entry.

## Non-goals

- German labels for the backend (plugin titles, job type setting); tracked
  separately.
- A configurable list of detail fields.
- Offering the flags in the new-job form; that is a change of its own.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`listings-05`). Two of the six analysed projects carry their own code for this
today. The change is carried by ACE-596, whose scope grows by the link
rendering; it is renamed to `ace-596-jobs-flag-and-link-rendering` once the
key is verified.

Implements ACE-596. ACE-371 is closed as its duplicate. Relates to ACE-368.
