## Context

`Profile/Contract/Field.html` renders one contract field per call. Position,
room and office hours share one branch that prints `{contract.{fieldName}}`,
which Fluid escapes. The frontend editor stores office hours as HTML, the
backend form and imports as plain text. The detail view of ACE-754 renders the
same value as `{f:format.nl2br(value: contract.officeHours) -> f:sanitize.html()}`.
Both view helpers exist on TYPO3 v13 and v14.

## Goals / Non-Goals

**Goals:**

- Office hours render in the list items the way the detail view renders them.

**Non-Goals:**

- Changing position and room, which are plain text fields everywhere.

## Decisions

### Office hours get their own branch, rendered like the detail view

Office hours leave the shared branch of position and room and get a branch of
their own with the chain the detail view uses: line breaks become `<br>`, then
the core sanitizer with its default build keeps paragraphs, lists, emphasis and
links, removes event handler attributes and prints an element it does not
allow as escaped text.

Rejected: `f:format.html`, which needs `lib.parseFunc_RTE` and a TypoScript
setup the plugin cannot rely on. Also rejected: `f:format.raw`, which renders
stored markup unfiltered.

### No shared partial for the value

The chain is one line in two partials. A partial of its own would add a file an
integrator has to know about, for no gain.

### No backport to branch `2`

Branch `2` has the same partial, but its frontend editor loads the rich text
editor on the profile page only, and office hours are edited on the contract
pages, which keep a plain textarea. Only the line break part of the defect
shows there as shipped, and the fix would trade it for the `<` of plain text.
A project that loads the editor on the contract pages would also get a `<br>`
after every block, because that editor writes a line break after each one.

## Risks / Trade-offs

- [Plain text with a `<` is read as the start of a tag] → The same as in the
  detail view since ACE-754, and documented there. Accepted.
- [An override of `Field.html` keeps the escaped output] → Named in the
  changelog entry.
