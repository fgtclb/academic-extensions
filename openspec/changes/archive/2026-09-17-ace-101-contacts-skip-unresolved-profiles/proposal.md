## Why

A page contact whose contract is hidden, or whose profile is hidden, expired
or restricted to a frontend user group, is still rendered: the contact row is
visible, the relation resolves to nothing, and the visitor gets an empty or
half-filled card under a role heading. Projects on the 2.x line patch this out
today, and every project that renders its own card needs guards against it.

This is the backport of ACE-101, merged on `main` for 3.0.0
(`openspec/changes/archive/2026-09-17-ace-101-contacts-skip-unresolved-profiles`
there). It is re-derived against this branch: the controller and the data
processor are identical to `main` before the fix, the contact repository is
the older plain Extbase query, and the code has to run on PHP 8.1.

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

The behaviour is identical on TYPO3 v12 and v13.

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
  changelog entry for 2.4.
- Functional tests for the plugin, the processor and the provider; this
  extension had none for the plugin or the processor on this branch.

## Non-goals

- Showing hidden contracts or profiles through the "show hidden records"
  option.
- Changing how contacts are selected per language or ordered.
- Making role grouping optional or changing the card markup.
- Letting the processor read its own TypoScript configuration.

Implements ACE-101.
