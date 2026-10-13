## Why

Publishing a workspace edit of a profile, contract, organisational unit,
contacts role, partner role or page can move its inline children to a record
that no longer exists. TYPO3 treats the pointer of each child to its parent as
a relation and rewrites it to the parent's version row, which publishing
deletes. The workspace module repairs most of it after publishing, but a
workspace published on its publish date (`workspace:autopublish`) leaves a
profile without contracts and a page without its page contacts and
partnerships, and so does the module when a page and the role of its contacts
or partnerships were edited in the same workspace (ACE-907, found while fixing
ACE-481).

## What Changes

- The pointer of every inline child to its parent no longer follows the
  parent's version row, in three extensions:
  - `academic_persons` (`packages/fgtclb/academic-persons`): the contract of
    e-mail addresses, phone numbers and postal addresses, the profile and the
    organisational unit of a contract, the profile of the profile information
    records.
  - `academic_contacts4pages` (`packages/fgtclb/academic-contact4pages`): the
    page and the role of a page contact.
  - `academic_partners` (`packages/fgtclb/academic-partners`): the page and
    the role of a partnership.
- Copying a parent, and translating a profile or a contract, still give the
  copied or translated children the copied or translated parent.
- A test fails for any inline child whose pointer to its parent is declared
  as a relation and still follows the version row, so a new relation cannot
  bring the defect back.
- An `Important-` changelog entry per extension explains the defect and gives
  the queries that find children whose parent no longer exists.

Behaviour and fix are the same on TYPO3 v13 and v14 (measured on 13.4.36 and
14.3.9). No schema change.

## Capabilities

### New Capabilities

- `academic-persons/workspace-publishing`: contracts, addresses and profile
  information keep their parent when it is published, copied or translated.
- `academic-contact4pages/workspace-publishing`: page contacts keep their page
  and role when either is published or copied.
- `academic-partners/workspace-publishing`: partnerships keep their page and
  role when either is published or copied.

### Modified Capabilities

- `academic-contact4pages/contact-address-selection`: a page contact whose
  contract was edited in a workspace is shown with its selected address after
  publishing, not only stored with it.

## Impact

- TCA of ten pointer columns in seven tables of three extensions.
- One functional test per relation, and one test over the whole TCA.
- `Important-` entries in the 3.0 changelog of the three extensions,
  `docs/architecture/backend-select-items.md`.

## Non-goals

- Repairing children that already point at a deleted version row. Core logs
  the version uid only when publishing, in `sys_log` and on v13 and v14 also
  in `sys_history`, and both are routinely cleaned, so a repair cannot be
  reliable. The changelog entries give the queries instead.
- The study plan pointers and the contract of a page contact: they are no
  relations to TYPO3 (`passthrough`, or a `select` without `foreign_table`)
  and keep their parent already.
- Branch `2`. It has the same defect for the page and role pointers of page
  contacts and partnerships, and it is not fixed there.

## Source

ACE-907, found while implementing ACE-481. Related: ACE-477, which made the
persons tables workspace aware on `main`.
