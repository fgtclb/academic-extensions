## Why

Editors maintain office hours on every contract, but the public profile never
shows them. The position line prints only the contract's position, so a
contract with a function type and no position shows nothing. Five
projects rewrote the detail templates for these two rows.

## What Changes

- The detail contact block shows the office hours of each contract, with an
  icon and a label, when they are set.
- Office hours keep the paragraphs, line breaks and links an editor entered;
  unsafe markup is never rendered.
- A new Settings.yaml list `profile.details.position.fields` selects what the
  position line shows: `position`, `functionType` and `organisationalUnit`.
  The default `[position]` is today's output.
- The function type uses the name for the profile's gender where one is
  maintained, and the general name otherwise.
- The list and card plugins offer the function type in "fields to show".

Behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-persons/public-profile-contact`: which contract data the public
  profile and the list items show in the contact block and position line.

### Modified Capabilities

None.

## Impact

- `academic_persons` (`packages/fgtclb/academic-persons`):
  `Partials/Profile/PublicProfile/Contact.html` and `Position.html`, a new
  function type name partial, `Partials/Profile/Contract/Field.html`, the
  "fields to show" items, a new icon, labels, the Settings.yaml layout
  normalisation and its shipped defaults, the documentation and the 3.0
  changelog.
- Plugins `detail` and `listanddetail`, and `list`, `listanddetail` and
  `card` for the "fields to show" item.
- The function type item depends on `ace-tbd-contract-field-partial-defects`,
  which makes the contract field partial render relation values.
- No schema change.

## Non-goals

- Office hours in the list and card; the existing "fields to show" item
  already covers that.
- An RTE for office hours in the backend form, which stays a plain text field.
- Contract selection (first, valid, matching), which belongs to
  `ace-tbd-contract-display-policy`.
- A backport to branch `2`, whose detail view has a different structure.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`persons-display-16`). Five of the six analysed projects carry their own code
for this today, the ACE demo among them. No YouTrack issue is filed yet; the
change is renamed to `ace-<NNN>-detail-contact-office-hours-function` when the
issue is filed after implementation.
