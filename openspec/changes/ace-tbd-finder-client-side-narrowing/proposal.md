## Why

ACE-91 asks the program finder to offer "only possible combinations, without
reloading". Three projects, the ACE demo among them, get there today by
reloading the whole finder through a topwire turbo frame on every change: a
dependency and a request per selection, for data that fits into one
attribute. A fourth plans the same. The finder element itself is proposed
separately as `ace-tbd-program-finder-element`; this change adds the
narrowing on top of it.

## What Changes

- The program finder of `academic_programs`
  (`packages/fgtclb/academic-programs`) disables in the browser every option
  that would lead to zero programs given the selections already made, and
  enables it again when that selection is cleared or changed.
- The submit button states how many programs match the current selection,
  and a change of that number is announced to screen reader users through a
  polite status message.
- The server-rendered finder stays complete: without JavaScript every option
  that has programs is selectable, and submitting works as before.
- `academic_programs` gets its first frontend module, built from TypeScript
  like the modules of the other extensions and published through an import
  map.

Behaviour is identical on TYPO3 v13 and v14; the change is frontend only.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `academic-programs/program-finder`: introduced by
  `ace-tbd-program-finder-element`; gains the requirements for narrowing the
  options, for the match count and for announcing it.

## Impact

- New TypeScript source, committed build output and import map in
  `academic_programs`.
- The finder action and template hand a program-to-category map to the page
  as a data attribute, and the template gains a visually hidden status
  region.
- New `testJs` coverage below
  `packages/fgtclb/academic-programs/Tests/JavaScript/`.
- No database, TCA or PHP API change.

## Non-goals

- A server round trip per selection (topwire, a JSON endpoint).
- Narrowing the filter of the program list element itself.
- OR within a category type; the finder keeps one value per type.
- Backporting to branch `2`, which has no finder.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`programs-studyplan-11`). Three of the six analysed projects carry their own
code for this today, and a fourth plans the same. No YouTrack issue is filed
yet; the change is renamed to `ace-<NNN>-finder-client-side-narrowing` when
the issue is filed after implementation.

Relates to ACE-91 (its "only possible combinations" criterion; the element
itself is `ace-tbd-program-finder-element`).
