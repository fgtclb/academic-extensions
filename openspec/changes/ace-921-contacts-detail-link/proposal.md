## Why

3.0 adds the setting `plugin.tx_academicpersons.detailLink` to
`academic_persons` (`packages/fgtclb/academic-persons`, ACE-807), so a site
without profile detail pages shows the names of its profiles without a link.
The contact list of `academic_contacts4pages`
(`packages/fgtclb/academic-contact4pages`) renders its contacts through the
same profile item, but its plugin settings never carry the setting, so it keeps
linking every name. On such a site the links lead to a page without a detail
element, or back to the page of the contact list (ACE-921). ACE-807 left the
contact list out on purpose, and its manual documents the gap. The setting is
not released yet, so the contact list can follow it before 3.0 is.

## What Changes

- The contact list follows the persons setting, whether the site sets it as a
  site setting of the persons set or as the TypoScript constant of the static
  template. With "no link", the names of its contacts render as text.
- The default stays "link", so every site that does not set it renders the
  contact list exactly as before.
- The contact list gets no choice of its own in its plugin options.
- The manuals of both extensions and the unreleased 3.0 entry of the persons
  setting say that the contact list follows it, instead of saying that it
  keeps linking.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `academic-contact4pages/page-contacts-list`: a requirement is added that the
  contact list follows the detail link setting of `academic_persons`. The
  existing requirements hold unchanged.

## Impact

- `academic_contacts4pages`: the TypoScript setup of the contact list
  component, a new functional test with its fixture, the flipped contact list
  test that pinned the old behaviour, the contact list and configuration
  chapters of the manual, and a 3.0 `Important` changelog entry.
- `academic_persons`: the configuration chapter of the manual and the 3.0
  `Feature` entry of the setting, documentation only.
- `docs/architecture/shared-partials.md` and
  `docs/architecture/typoscript-and-site-sets.md`, which name the contact list
  as the caller that keeps linking.
- TYPO3 v13 and v14 behave the same.
- No PHP, FlexForm, TCA, database or dependency change.

## Non-goals

- An option of the contact list content element that overrides the site
  setting, as the persons elements have. The issue leaves it open, and a site
  that wants links in one place and none in another has not been asked for.
- The page contacts data processor. It assigns contacts to a page template
  and no settings, and a page template that renders the profile item passes
  settings of its own.
- Branch `2`, which has no detail link setting.
