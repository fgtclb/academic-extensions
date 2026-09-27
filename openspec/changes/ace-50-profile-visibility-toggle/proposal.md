## Why

Staff decide themselves whether their profile appears in the public persons
directory (ACE-50, ACE-20). One project built a consent column of its own for
it, with a forked controller, two repository XCLASSes, a FlexForm listener and
template conditions.

Upstream already has the field for it. Every profile has carried `hidden`
since the first release, shared by all languages of the profile, and every
public output honours it: lists with their counts, pagination and letters,
the detail page, both selections and the page contacts of
`academic_contacts4pages`. What is missing is the owner's control. The profile
editor lets the owner hide a contract, a profile information row or a contact
record (ACE-524), but not the profile itself. A hidden profile is also no
longer reachable in the editor, so its owner could not show it again.

A second consent column would split one fact into two fields. This change
gives the owner the `hidden` toggle instead.

## What Changes

- The profile editor of `academic_persons_edit`
  (`packages/fgtclb/academic-persons-edit`) offers the owner a switch "Show my
  profile publicly", next to the synchronisation switch. It writes `hidden` of
  the profile, for all its languages.
- The owner keeps seeing and editing an own profile that is hidden, including
  its image, so the switch works in both directions. Start and end time and
  frontend user groups keep applying.
- An installation can take the switch away from the owner through the
  editor's `Settings.yaml` configuration, the same way as the synchronisation
  switch, so that a profile an editor hid stays hidden.
- The profile model of `academic_persons` (`packages/fgtclb/academic-persons`)
  exposes `hidden`.
- The documentation describes an internal directory behind a login that lists
  hidden profiles too, through the existing plugin option "Show hidden
  records", and its limits.

Behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-persons-edit/profile-visibility-toggle`: how a profile owner shows
  or hides the own profile in the frontend editor, and what the owner still
  reaches while it is hidden.

### Modified Capabilities

None.

## Impact

- `academic_persons_edit`: the profile header partial, one update endpoint of
  the profile controller, the lookup of the owner's profiles, one special
  field in `Settings.yaml`, labels, documentation and the 3.0 changelog.
- `academic_persons`: one model property, one repository lookup for the
  owner, the profile TCA leaving the switch's flags out of the backend, and a
  documentation section on the internal directory.
- Database: none.

## Non-goals

- A consent column next to `hidden`, a plugin option or a site setting that
  requires consent. `hidden` already keeps a profile out of every public
  output.
- An internal directory that tells an editor's hide from the owner's. It uses
  "Show hidden records" and shows both.
- Field-level visibility (ACE-474).
- Migrating a project's own consent column. The changelog shows how.
- A backport to branch `2`, whose editor predates ACE-262.

## Source

Candidate `persons-display-14` of the project differences analysis of
2026-09-12, redefined on 2026-09-27 after re-checking `main`.

Implements ACE-50. Relates to ACE-20 and ACE-474.
