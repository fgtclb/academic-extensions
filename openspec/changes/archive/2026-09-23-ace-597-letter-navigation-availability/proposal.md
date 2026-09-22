## Why

The letter navigation of the persons list hard-codes the letters a to z and
links every one of them, so a visitor lands on empty result pages. It is also
rendered when the plugin shows a manual selection of profiles, where the
letter filter is ignored and a click changes nothing. Five projects override
the navigation for this. The upstream work is already split into ACE-597,
ACE-598 and ACE-599.

## What Changes

- Letter availability is computed under the same constraints as the list,
  without the active letter (ACE-597).
- A letter without profiles is rendered disabled, without a link, and
  announced to assistive technology as having no profiles; the navigation
  gets an accessible name, and the active letter and "A-Z" are marked as
  current (ACE-598).
- No letter navigation is rendered when the plugin shows a manual selection
  (ACE-599).
- New option `plugin.tx_academicpersons.alphabet.activeLetterResets`, default
  off. When on, the active letter links back to the unfiltered list, which is
  what one project built.

Behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-persons/letter-navigation`: which letters the persons list
  offers, which are selectable, and when the navigation is shown.

### Modified Capabilities

None.

## Impact

- `academic_persons` (`packages/fgtclb/academic-persons`): the profile
  repository (one extra query per uncached list rendering), the profile
  controller's list action, `Templates/Profile/List.html`,
  `Partials/Profile/List/AlphabetPagination.html`, two labels, the site
  settings and constants of the shared plugin block, the documentation and
  the 3.0 changelog.
- Plugins `list` and `listanddetail`.
- Overrides of `AlphabetPagination.html` keep working but show no
  availability until they read the new variable. Overrides of `List.html`
  that do not pass it keep every letter a link.

## Non-goals

- Letters outside a to z, such as umlauts or digits; today's letter set
  stays.
- A different letter matching rule than "last name starts with".
- Pagination under an active letter.
- A backport to branch `2`; the issues are tagged `[3.x]`.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`persons-display-11`). Five of the six analysed projects carry their own code
for this today. The existing issues carry the work, and the change is named
after the first of them, `ace-597-letter-navigation-availability`. The reset
option, which none of them covers, was filed as ACE-718.

Implements ACE-597, ACE-598, ACE-599 and ACE-718. Relates to ACE-525.
