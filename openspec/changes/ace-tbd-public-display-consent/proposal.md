## Why

Some institutions may publish a person only with that person's consent. The
persons extension has no consent notion: the only visibility controls are
`hidden` and the plugin option "show hidden records". One project
implements opt-in display through a forked controller, two repository
XCLASSes, a FlexForm listener and template conditions. ACE-50 is the upstream
story.

## What Changes

- A profile field "Public display" (consent), off by default, maintained by
  editors in the backend and shared by all languages of a profile.
- A plugin option "Only show profiles with consent to public display", off by
  default, in every persons plugin: list, listanddetail, card, detail,
  selected profiles and selected contracts.
- A site setting "Require consent to public display", off by default, that
  requires consent in every persons plugin of the site. Consent is required
  where either the site setting or the plugin option is on.
- Where consent is required, lists and selections omit profiles without
  consent (and the contracts of such profiles), and a detail page of such a
  profile answers "page not found".
- All defaults keep today's output; nothing changes until an integrator
  opts in.

Behaviour is identical on TYPO3 v13 and v14. The option is added to both
`Core13/` and `Core14/` copies of `List.xml` and `Detail.xml`, and to
`SelectedProfiles.xml` and `SelectedContracts.xml`.

## Capabilities

### New Capabilities

- `academic-persons/public-display-consent`: which profiles the persons
  plugins show when consent to public display is required.

### Modified Capabilities

None.

## Impact

- `academic_persons` (`packages/fgtclb/academic-persons`): the profile TCA and
  model (new column `public_display`), six FlexForm files and their labels,
  one site setting in the site set and the static template, one event
  listener, the detail action of the profile controller, the documentation
  and the 3.0 changelog.
- Two follow-up changes in the same 3.0 release, proposed separately:
  consent in `academic_contacts4pages` and a consent toggle in the frontend
  editor of `academic_persons_edit`.
- Depends on `ace-tbd-profile-query-constraint-event`: the consent condition
  is added through its profile and contract query events. No repository
  method changes its signature.
- Database: one new column with default `0`.

## Non-goals

- Field-level consent (ACE-474), a change of its own later.
- A consent toggle in the frontend editor of `academic_persons_edit`
  (`packages/fgtclb/academic-persons-edit`); a follow-up change in the same
  release.
- Honouring the consent in `academic_contacts4pages`
  (`packages/fgtclb/academic-contact4pages`); a follow-up change in the same
  release.
- Mapping the consent from frontend users or imports.
- A backport to branch `2`.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`persons-display-14`). One of the six analysed projects carries its own code
for this today. The existing story carries the work, so no new issue is
needed; the change is renamed to `ace-50-public-display-consent` before
implementation.

Implements ACE-50. Relates to ACE-20 and ACE-474.
