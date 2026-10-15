## Why

A "Person List" or "Person List and Details" element of `academic_persons`
(`packages/fgtclb/academic-persons`) with "Pagination" switched on and an empty
"Results per Page" ends the request with the exception "Argument $itemsPerPage
must be greater than 0" (ACE-913). The saved empty value replaces the site
setting, and the list hands 0 to the paginator. The site setting itself
defaults to 1, so a list that takes the value of the site shows one profile per
page.

## What Changes

- An empty "Results per Page" or "Number of Links" of the element, or a saved
  `0`, no longer replaces the site setting. The value of the site applies, as
  it does for the detail page and the detail link already.
- A number of results per page below 1, from the element, the site setting or
  TypoScript, no longer ends the request. The list falls back to 10 results
  per page, as the partner and job lists do.
- A number of links below 1 falls back to 5, the shipped default, instead of
  the default of `georgringer/numbered-pagination`, which is 10.
- The site setting and the TypoScript constant
  `plugin.tx_academicpersons.pagination.resultsPerPage` default to 10 instead
  of 1. A list that relies on the shipped default shows ten profiles per page.
- The manual, the upgrade guides from 2.3 and a new 2.4 changelog entry
  describe the fallback and the new default.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `academic-persons/profile-list-pagination`: which number of results per page
  and of page links a list uses when the element leaves the field empty, when
  a value is below 1, and when nothing configures it.

## Impact

- `academic_persons`: the list action of the profile controller, the shared
  TypoScript that names the settings an empty element value does not override,
  the site set definition and the constants of the pagination, the manual
  pages of the two list elements and of the plugin options, the upgrade guides
  from 2.3 to 2.4 and from 2.3 to 3.0, a new
  `Documentation/Changelog/2.4/Important-*.rst`.
- TYPO3 v13 and v14 behave the same. The handling of empty element values is
  the one of Extbase on both versions.
- An installation that relied on the shipped default of 1 result per page sees
  ten profiles per page after the update, unless it sets the value.
- No database, TCA, FlexForm or dependency changes. The development seed sets
  its values explicitly and is not affected.

## Non-goals

- Validating the FlexForm fields in the backend form. They stay free text
  fields, and the controller guards whatever they hold.
- The pagination of the other extensions, which already fall back to 10 and 5.
- Branch `2`, which gets its own change from the backport analysis.
