## Why

The contract fields of the list, list and detail, card, selected profiles and
selected contracts plugins of `academic_persons`
(`packages/fgtclb/academic-persons`) print the office hours as escaped text. The
frontend profile editor of `academic_persons_edit` stores office hours as HTML
from a rich text editor. A visitor therefore reads the markup itself, for
example `<p>Tuesday 10:00 to 12:00</p>`, whenever office hours are among the
fields to show, which they are by default. Plain text office hours from the
backend form or an import lose their line breaks in the same place.

The detail view renders office hours as HTML since ACE-754. The item fields
still do not.

## What Changes

- The office hours field of the list items renders the paragraphs, lists and
  links an editor wrote, and keeps the line breaks of plain text.
- The value goes through the core HTML sanitizer, the same way as in the
  detail view: event handler attributes are removed, and an element it does
  not allow, such as a script, is printed as escaped text and never runs.
- No new setting and no new field.

The behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `academic-persons/contract-field-rendering`: the office hours field renders
  sanitized HTML instead of escaped text.

## Impact

- `academic_persons`: `Resources/Private/Partials/Profile/Contract/Field.html`,
  the functional card tests, the documentation and a 3.0 changelog entry.
- Plugins `list`, `listanddetail`, `card`, `selectedprofiles` and
  `selectedcontracts`, and the contacts of `academic_contacts4pages`, which
  render the same partial.
- No schema change, no configuration change.
- `academic_contacts4pages` (`packages/fgtclb/academic-contact4pages`): one
  functional test, since its contacts element renders the default fields.

## Non-goals

- A rich text editor for office hours in the backend form, which stays a plain
  text field.
- Any change to the other contract fields of the partial.
- A backport to branch `2`. Its partial is the same, but its frontend editor
  loads the rich text editor on the profile page only, so the contract pages
  where office hours are edited store plain text. Only the line break part of
  the defect shows there as shipped, and the fix would trade it for the `<` of
  plain text and, with the editor loaded, add a `<br>` after every block.

## Source

Found in the review of ACE-754 (#774) and filed as ACE-755. Adopted into the
project differences pipeline as #102.

Implements ACE-755.
