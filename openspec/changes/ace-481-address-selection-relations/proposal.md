## Why

A page contact selects one e-mail address, phone number or postal address of
its contract, or "display all" or "display none". The three selects store bare
uids, so TYPO3 does not know them as relations: a copied contract or profile
leaves its page contacts selecting the addresses of the original, the
reference index knows nothing of a selection, and a selection that matches no
offered address is written away on the next save. The dedicated selection is
new in 2.4 (ACE-236) and in no release yet, latest tag 2.3.4, so fixing it
before 2.4.0 keeps the copy defect out of every release. `main` fixed it in
`ace-481-address-selection-live-records` (ACE-481).

## What Changes

- The e-mail address, phone number and postal address selects of a page
  contact in `academic_contacts4pages` (`packages/fgtclb/academic-contact4pages`)
  are declared as relations to the address tables of `academic_persons`,
  limited to the records of the contact's contract.
- Copying a contract, or a profile with its contracts, makes the copied page
  contacts select the copied addresses. Translating a contract keeps its page
  contacts on the default language addresses.
- A selected address is recorded in the reference index.
- A selection that matches no offered address stays stored and is shown as
  invalid, instead of being written away on the next save.
- The selects still offer each address of the contract once, regardless of
  the editor's access to the folder of the person records.

No schema change and no upgrade wizard: stored values keep their meaning. The
behaviour is the same on TYPO3 v12 and v13.

## Capabilities

### New Capabilities

- `academic-contact4pages/contact-address-selection`: what an editor is
  offered, what is stored, and how a selection survives copying and
  translating.

### Modified Capabilities

None.

## Impact

- TCA of `tx_academiccontacts4pages_domain_model_contact`, the items handler
  of the three selects, a `DataHandler` hook for copies registered in
  `ext_localconf.php` and `Services.yaml`, and one `Important-` entry in
  `Changelog/2.4/`.
- Existing contacts get their reference index rows on the next save or a
  reference index update.

## Non-goals

- The workspace part of `main`: the address tables, the contract and the
  profile are not workspace aware on this branch, so no version row can be
  offered or selected.
- `showIfEmpty`: TYPO3 v12 and v13 do not remove an empty relation select.
- The contract select, as on `main`.
- ACE-907, the parent pointers of inline children remapped on a workspace
  edit, a change of its own.

## Source

Backport of `ace-481-address-selection-live-records` on `main`. Analysis:
the backport section of the ACE-481 prompt. Related: ACE-236, ACE-907.
