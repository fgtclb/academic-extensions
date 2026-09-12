## Why

The job list renders every job of the configured type on one page. Portals
with many open positions get a long page, and one project built pagination
for its own clone of the job list and states the job portal needs it too.
`academic_persons` already paginates its list.

## What Changes

- The job list content element of `academic_jobs`
  (`packages/fgtclb/academic-jobs`) gains an optional pagination:
  - a FlexForm switch "Enable pagination" (off by default) and a "Results per
    page" field;
  - a site setting for the number of page links, as `academic_persons` has.
- With pagination enabled, the list shows one page of jobs and a pagination
  navigation below it, only when there is more than one page. Numbered page
  links are used when `georgringer/numbered-pagination` is installed,
  previous/next links otherwise.
- The page is a plain plugin argument; there is no filter to keep.
- Behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-jobs/list-pagination`: optional pagination of the job list.

### Modified Capabilities

None.

## Impact

- `Configuration/FlexForms/PluginList.xml`, the list action, a new
  `Job/Pagination.html` partial rendered by `Templates/Job/List.html`,
  TypoScript and site settings, labels.
- An overridden `List.html` keeps rendering all jobs of the page it gets,
  without a navigation.
- `georgringer/numbered-pagination` becomes a `suggest` of the extension.
- No schema changes.

## Non-goals

- A job demand object, filters or sorting for the job list.
- A route enhancer for the page argument.
- The new-job form and the B-ITE job list.
- Backporting to branch `2`.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`listings-16`). One of the six analysed projects carries its own code for
this today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-jobs-list-pagination` when the issue is filed after implementation.

Implements ACE-256.
