## Why

The three address selects of a page contact offer every record of its
contract in every workspace, including the version row of an address changed
in a workspace, under the label of the live one. A selected version row never
matches in the frontend and is deleted on publish, so the contact shows no
address of that kind. Because TYPO3 does not know the values are relations, a
copied contract or profile leaves its contacts selecting the source's
addresses, and the reference index knows nothing of a selection. The publish
mechanism the issue first named, records changing their uid, does not exist on
TYPO3 v13 or v14 (ACE-481, retitled by the board triage of 2026-10-12).

## What Changes

- The e-mail address, phone number and postal address selects of a page
  contact in `academic_contacts4pages` (`packages/fgtclb/academic-contact4pages`)
  are declared as relations to the address tables of `academic_persons`,
  limited to the records of the contact's contract.
- The selects offer each address of the contract once, as the editor's
  workspace shows it, never a record of another workspace, and store the uid
  that stays valid after publishing.
- Copying a contract, or a profile with its contracts, makes the copied
  contacts select the copied addresses.
- A selected address is recorded in the reference index.
- "Display all" and "display none" keep their stored values, and the selects
  stay visible on TYPO3 v14 when the contract has no address of that kind.
- A selection that matches no offered address stays stored and is shown as
  invalid, instead of being written away on the next save.
- The ACE-477 changelog note on the publish mechanism is corrected.

No schema change and no upgrade wizard: stored values keep their meaning,
and no release ships the dedicated selection yet. Behaviour is the same on v13
and v14, except that v14.2 hides an empty relation select unless it opts out.

## Capabilities

### New Capabilities

- `academic-contact4pages/contact-address-selection`: what an editor is
  offered, what is stored, and what survives publishing, copying and deleting.

### Modified Capabilities

None.

## Impact

- TCA of `tx_academiccontacts4pages_domain_model_contact`, the items handler
  of the three selects, an `Important-` entry in the 3.0 changelog, the
  corrected ACE-477 entry and `docs/architecture/backend-select-items.md`.
- Existing contacts get their reference index rows on the next save or a
  reference index update.

## Non-goals

- The contract select: it is the inline parent pointer, and its items
  already hold live uids.
- Repairing copies made before the fix: no release carries them.

## Backport

Branch `2` has the same TCA and handler: the copy and reference index part
applies there, the workspace part does not (no versionable address tables).
Backport only on a report, as a change of its own from a backport analysis.
The dedicated selection is in no release yet (latest tag 2.3.4), so a backport
before 2.4.0 keeps the copy defect out of every release.

## Source

Board triage of 2026-10-12, report `ACE-481`. Related: ACE-477, ACE-236,
ACE-476.
