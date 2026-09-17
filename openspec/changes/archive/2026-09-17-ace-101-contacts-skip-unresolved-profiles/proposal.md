## Why

A page contact whose contract is hidden, or whose profile is hidden, expired
or restricted to a frontend user group, is still rendered: the contact row is
visible, the relation resolves to nothing, and the visitor gets an empty or
half-filled card under a role heading. Projects patch this out today, and
every project that renders its own card needs guards against it.

## What Changes

- The contacts plugin and the page contacts data processor leave out every
  contact whose contract or profile is not visible to the current visitor.
- Role headings and the "contacts without role" group are built only from
  the remaining contacts, so a role whose contacts are all hidden disappears.
- The "show hidden records" option of the plugin keeps its meaning: it shows
  hidden contact rows, never hidden contracts or profiles.
- The plugin and the processor share one implementation of this rule.
- The `contacts` value handed to templates becomes a plain list instead of a
  query result. Fluid loops and counts behave the same.

The behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-contact4pages/page-contacts`: which contacts of a page are shown
  to a visitor, in the plugin and through the page data processor.

### Modified Capabilities

None.

## Impact

- `academic_contacts4pages` (`packages/fgtclb/academic-contact4pages`): the
  contacts controller, the page contacts data processor, a new internal
  service shared by both.
- Reads profile and contract visibility from `academic_persons`
  (`packages/fgtclb/academic-persons`); no change there.
- PHP code that reads the processor output or the `contacts` view variable as
  a query result has to accept a list. Documented in an `Important-`
  changelog entry.
- Functional tests for the plugin and the processor.

## Non-goals

- Showing hidden contracts or profiles through the "show hidden records"
  option.
- Changing how contacts are selected per language or ordered.
- Making role grouping optional or changing the card markup.
- Letting the processor read its own TypoScript configuration.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`listings-02`). Two of the six analysed projects carry their own code for
this today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-contacts-skip-unresolved-profiles` when the issue is filed after
implementation.

Implements ACE-101.
