## Why

A "Person List" or "Person List and Details" element of `academic_persons`
(`packages/fgtclb/academic-persons`) with "Pagination" switched on and an empty
"Results per Page" ends the request with the exception "Argument $itemsPerPage
must be greater than 0" (ACE-913), on TYPO3 v12 and v13. The saved empty value
replaces the value of the site, and the list hands 0 to the paginator. The
shipped default is 1, so a list that takes the value of the site shows one
profile per page.

This change backports the change `ace-913-persons-results-per-page` of `main`,
archived there as
`openspec/changes/archive/2026-10-15-ace-913-persons-results-per-page/`.

## What Changes

- An empty "Results per Page" or "Number of Links" of the element, or a saved
  `0`, no longer replaces the value of the site. The value of the site
  applies, as it does for the detail page already.
- A number of results per page below 1, from the element, the site setting or
  TypoScript, no longer ends the request. The list falls back to 10 results
  per page.
- A number of links below 1 falls back to 5, the shipped default, instead of
  the default of `georgringer/numbered-pagination`, which is 10.
- The TypoScript constant `plugin.tx_academicpersons.pagination.resultsPerPage`
  and, on TYPO3 v13, the site setting of the same name default to 10 instead
  of 1.
- The upgrade guide from 2.3 to 2.4 and a new 2.4 changelog entry describe the
  fallback and the new default, byte for byte the files of `main`.

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
  the constants of the pagination, the definition of the site set `Full`, the
  site set test of TYPO3 v13, the upgrade guide from 2.3 to 2.4 and a new
  `Documentation/Changelog/2.4/Important-*.rst`.
- TYPO3 v12 and v13 behave the same. Site sets exist on v13 only, so on v12
  the constant is the value of the site.
- An installation that relied on the shipped default of 1 result per page sees
  ten profiles per page after the update, unless it sets the value.
- No database, TCA, FlexForm or dependency changes. The development seed sets
  its values explicitly and is not affected.

## Non-goals

- Validating the FlexForm fields in the backend form. They stay free text
  fields, and the controller guards whatever they hold.
- The manual pages of the list elements and the upgrade guide from 2.3 to 3.0,
  which exist on `main` only.
- A paragraph in `docs/`. This branch has no section on
  `ignoreFlexFormSettingsIfEmpty` that it would extend.
