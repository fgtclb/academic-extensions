## Why

The page data processor of `academic_contacts4pages`
(`packages/fgtclb/academic-contact4pages`) hands page templates less than the
content element renders. It returns no list of contacts without a role (the
fix of ACE-322 covered only the plugin), ignores its own configuration, never
shows hidden contacts and always reads the current page. Nothing lets an
extension change which contacts a page shows, so one project copied the
whole controller into a second plugin.

## What Changes

- The page output also provides the contacts without a role, so a page
  template can render what the content element renders.
- The processor honours `as` (one variable holding contacts, roles and
  contacts without role), `showHiddenRecords` (default off) and `pageUid`
  (default the current page).
- One event lets an extension change the contacts of a page before either
  output renders them, and tells it whether the content element or the page
  output asked.
- Plugin and processor read the contacts through the same page contacts
  provider that candidate `listings-02` introduces.
- `docs/` and the extension manual explain how to attach the processor to
  further page objects.

The behaviour is the same on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-contact4pages/page-contacts-data`: which contacts of a page the
  page output provides, how an integrator configures it, and how an extension
  changes the contacts of both outputs.

### Modified Capabilities

None.

## Impact

- `academic_contacts4pages`: the data processor, the controller, the page
  contacts provider of `listings-02`, a new event class,
  `Configuration/Services.yaml`, `Configuration/TypoScript/List/setup.typoscript`.
- Page templates keep today's top-level variables while `as` is not set.
- No database change.

## Non-goals

- A site setting that lists the page types to attach the processor to.
- A shipped page template for the contacts.
- The cal.com appointment widget of one project, which stays in the project.
- Backporting to branch `2`.

Depends on candidate `listings-02` (change
`ace-tbd-contacts-skip-unresolved-profiles`).

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`listings-19`). Three of the six analysed projects carry their own code for
this today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-<slug>` when the issue is filed after implementation.
