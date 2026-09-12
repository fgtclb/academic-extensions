## Why

`academic_bite_jobs` (`packages/fgtclb/academic-bite-jobs`) sends a fixed
request to the B-ITE API: empty filter, channel 0, locale `de`. It offers no
way to change that request or the postings it returns. The 2.1 breaking note
removed a project's custom-field filter and grouping and promised a new API
for them; ACE-102 asks for it, and that project still runs a fork.
The service also keeps the last response in a property of a shared instance,
so a second
plugin on the same page whose request fails shows the postings of the first.

## What Changes

- The service keeps no response between calls. A failed request renders no
  postings.
- Before the request is sent, an event lets an extension change the request
  payload (filter, channel, locale, sort, paging), with the plugin settings
  and the current request at hand.
- After the response is decoded, an event lets an extension change the
  postings (remove, enrich, add a grouping value), with the decoded response
  and the plugin settings at hand.
- The configured limit applies after the listeners.
- The payload keys are documented, since they become public API.

Without listeners, the request and the output are unchanged. The behaviour
is the same on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-bite-jobs/job-postings-retrieval`: what the job list requests
  from B-ITE, how an extension changes the request and the postings, and
  what a failed request renders.

### Modified Capabilities

None.

## Impact

- `academic_bite_jobs`: the service, two new event classes, the manual.
- The service tests and the HTTP stub fixture.
- No FlexForm, TypoScript or database change. The FlexForm read inside the
  service stays as it is; its API is one of the TYPO3 v15 blockers named in
  `AGENTS.md`.

## Non-goals

- FlexForm fields for a B-ITE custom field and its values.
- The grouping in the template; that is candidate `listings-04` (change
  `ace-tbd-bite-jobs-list-grouping-and-views`).
- A client for the B-ITE options API.
- Deriving the locale from the site language by default; a listener can.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`listings-20`). Three of the six analysed projects carry their own code for
this today.

Implements ACE-102; the change is renamed to
`ace-102-bite-jobs-request-result-events`. ACE-154 carries the identical text
and is closed as its duplicate (see `design.md` for the one check before the
rename).
