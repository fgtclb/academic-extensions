## Why

The partnership list ("Partners Linked") and the partner logo teaser of
`academic_partners` (`packages/fgtclb/academic-partners`) group the
partnerships of their page by role as soon as one of them has a role, and
every partnership without a role then disappears from the frontend (ACE-917).
Filling in an optional field removes other partnerships, and the manuals can
only warn about it.

## What Changes

- Both elements render the partnerships without a role after the role groups,
  in the order arranged on the page, in an item list without a heading. Their
  templates receive them as a variable of their own, the way the contacts
  element of `academic_contacts4pages` does since ACE-322.
- A partnership whose role is hidden or deleted is listed with them. The
  frontend does not resolve such a role, so the partnership has none there,
  and it vanished like one without a role before. Hiding the partnership
  itself still takes it off the page.
- The data processor of the partner page hands the same list to a page
  template, so a page template that groups by role can render them as well.
- A page whose partnerships have no role at all keeps the single list, and a
  page whose partnerships all have a role renders no additional list.
- The grouping in translated languages (ACE-430) stays as it is: the role
  groups compare the role by its uid and are headed in the visitor's language.
- The manual pages of both elements describe the behaviour instead of the
  warning. A `Changelog/2.4/Important-*.rst` entry, written to hold on branch
  `2` as well, names the new variable for template overrides, and the upgrade
  guides from 2.3 list it for both templates.

## Capabilities

### New Capabilities

- `academic-partners/partnership-role-groups`: how the partnership list and
  teaser arrange the partnerships of a page by role, the partnerships without
  a role included.

### Modified Capabilities

None. The requirements of `academic-partners/partnership-languages` hold
unchanged: every partnership with a role stays in its role group in every
language.

## Impact

- `academic_partners` only: the role service, the two plugin actions and the
  page data processor, the templates of both elements, a functional test with
  its fixture and the test TypoScript of the processor, the manuals, the
  changelog and the upgrade guides from 2.3.
- TYPO3 v13 and v14 behave the same.
- No database, TCA, FlexForm or dependency changes. The item partials keep
  their arguments.
- A site package that overrides one of the two templates keeps its own output
  until it renders the new variable.

## Non-goals

- A configurable heading for the partnerships without a role. The issue named
  it as an option, the maintainer decided against it to match the contacts
  element.
- Changing the order of the role groups, which stays the order in which their
  roles first occur on the page.
- Branch `2`, which gets its own change from the backport analysis.
