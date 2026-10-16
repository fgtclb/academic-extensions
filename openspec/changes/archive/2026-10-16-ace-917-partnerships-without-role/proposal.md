## Why

The partnership list and the partnerships teaser of `academic_partners`
(`packages/fgtclb/academic-partners`) group the partnerships of their page by
role as soon as one of them has a role, and every partnership without a role
then disappears from the frontend (ACE-917). Filling in an optional field
removes other partnerships. Branch `2` has the same service and the same
template logic as `main`, on TYPO3 v12 and v13.

This backports the change `ace-917-partnerships-without-role` of `main`,
archived there as
`openspec/changes/archive/2026-10-16-ace-917-partnerships-without-role/`,
re-derived from the backport analysis of this branch.

## What Changes

- Both elements render the partnerships without a role after the role groups,
  in the order arranged on the page, in a row without a heading. Their
  templates receive them as a variable of their own, the way the contacts
  element of `academic_contacts4pages` does since ACE-322.
- A partnership whose role is hidden or deleted is listed with them. The
  frontend does not resolve such a role on TYPO3 v12 or v13, so the
  partnership has none there, and it vanished like one without a role before.
  Hiding the partnership itself still takes it off the page.
- The data processor of the partner page hands the same list to a page
  template, so a page template that groups by role can render them as well.
- A page whose partnerships have no role at all keeps the single list, and a
  page whose partnerships all have a role renders no additional list.
- The grouping in translated languages (ACE-430) stays as it is: the role
  groups compare the role by its uid and are headed in the visitor's language.
- The `Changelog/2.4/Important-*.rst` entry and the upgrade guide from 2.3 to
  2.4 are taken from `main` byte for byte, since both are shared between the
  branches. The page on translation synchronization in `docs/` names the list
  next to the role grouping of ACE-430.

## Capabilities

### New Capabilities

- `academic-partners/partnership-role-groups`: how the partnership list and
  teaser arrange the partnerships of a page by role, the partnerships without
  a role included.

### Modified Capabilities

None. The requirements of `academic-partners/partnership-languages` and
`academic-partners/partnership-order` hold unchanged: every partnership with a
role stays in its role group in every language, and the page order is kept.

## Impact

- `academic_partners` only: the role service, the two plugin actions and the
  page data processor, the templates of both elements, a functional test with
  its fixture and the test TypoScript of the processor, the changelog, the
  upgrade guide from 2.3 and the page on translation synchronization in
  `docs/`.
- TYPO3 v12 and v13 behave the same.
- No database, TCA, FlexForm or dependency changes. The item partials keep
  their arguments.
- A site package that overrides one of the two templates keeps its own output
  until it renders the new variable.

## Non-goals

- A configurable heading for the partnerships without a role, a non-goal on
  `main` as well.
- Changing the order of the role groups, which stays the order in which their
  roles first occur on the page.
- The manual pages, the styling chapter and the plugin view event page that
  `main` changed. This branch has no integrator or styling chapter and no
  plugin view event, so there is no warning to remove and no page to extend.
