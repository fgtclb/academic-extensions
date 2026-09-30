## Why

The backport of the `main` change of the same name, ACE-102, archived there as
`openspec/changes/archive/2026-09-30-ace-102-bite-jobs-request-result-events`.

`academic_bite_jobs` (`packages/fgtclb/academic-bite-jobs`) sends a fixed
request to the B-ITE API: empty filter, channel 0, locale `de`. It offers no
way to change that request or the postings it returns. The 2.1 breaking note
removed a project's custom-field filter and grouping and promised a new API
for them, and that project runs 2.x with a fork of 2.0.2. The service also
keeps the last response in a property of a shared instance, so a second
plugin on the same page whose request fails shows the postings of the first.

## What Changes

- The service keeps no response between calls. A failed request renders no
  postings.
- Before the request is sent, an event lets an extension change the request
  payload (filter, channel, locale, sort, paging), with the plugin settings,
  the current request and the context of the job list at hand.
- After the response is decoded, an event lets an extension change the
  postings (remove, enrich, add a grouping value), with the decoded response,
  the plugin settings and the context of the job list at hand. It is
  dispatched after a failed request as well.
- The configured limit applies after the listeners.
- The payload keys are documented, since they become public API.

Without listeners, the request and the output are unchanged. The behaviour
is the same on TYPO3 v12 and v13, and the same as on `main`.

Different from `main`: TYPO3 v12 has no `#[AsEventListener]` attribute, so
the manual registers the example listeners in `Services.yaml`, and so does
the fixture extension of the tests. The service is a `final` class with
`readonly` properties rather than a `readonly` class, since this branch
supports PHP 8.1. There is no extension points page in `academic_base` here
to list the events on, and no plugin view event. `docs/` has no page on the
list plugin events here, which on `main` gets a section on this pair, so the
manual of the extension is the only description of the events on this
branch. The changelog entries have labels of their own
(`feature-1790774401`, `important-1790774501`), as every entry that `main`
keeps in `3.0/` and this branch in `2.4/` does.

## Capabilities

### New Capabilities

- `academic-bite-jobs/job-postings-retrieval`: what the job list requests
  from B-ITE, how an extension changes the request and the postings, and
  what a failed request renders.

### Modified Capabilities

None.

## Impact

- `academic_bite_jobs`: the service, the controller hands a plugin context to
  the service, two new event classes, the manual with a new developer
  chapter.
- The service tests, a new fixture extension with listeners.
- No FlexForm, TypoScript or database change.

## Non-goals

- FlexForm fields for a B-ITE custom field and its values.
- A client for the B-ITE options API.
- Deriving the locale from the site language by default. A listener can.
- The plugin view event of `main`, which this branch does not have.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`listings-20`). Implements ACE-102. ACE-154 carried the identical text and
was closed as its duplicate.
