## Context

See proposal.md for the defect. `ProfileController::listAction()` of
`academic_persons` serves both list elements. It reads
`settings.pagination.resultsPerPage` and `settings.pagination.numberOfLinks`
with `(int)`, after Extbase merged the FlexForm of the element over the
TypoScript settings. The shared TypoScript
`Configuration/TypoScript/Shared/allow_typoscript_override_empty_flexform_settings.typoscript`
names the settings Extbase drops from the FlexForm while they are empty
(`ignoreFlexFormSettingsIfEmpty`). On this branch it lists `detailPid`,
`pageTitleFormat` and `showFields`, not the two pagination settings.

`FrontendConfigurationManager::removeIgnoredFlexFormSettingsIfEmpty()` resolves
every entry as a dotted path below `settings` and drops a value of `''` or
`'0'`. Verified in the vendor tree of TYPO3 12.4.45, the option exists since
12.3 and the floor of this branch is 12.4.22, and the method is the same on
13.4. `AbstractPaginator::setItemsPerPage()` throws 1573061766 for fewer than
one item per page on both versions. `NumberedPagination` of
`georgringer/numbered-pagination` 2.2 treats a number of links below 1 as its
own default of 10.

The fix is the one of `main` (see the archived change named in proposal.md).
The touched code differs from `main` only in its surroundings, so no line of
it needs a version switch.

## Goals / Non-Goals

**Goals:**

- One rule for both list elements, both paginators and both pagination
  classes, on TYPO3 v12 and v13 alike.
- The same behaviour as on `main`, so the 2.4 changelog entry and the upgrade
  guide from 2.3 to 2.4 stay byte-identical between the branches.

**Non-Goals:**

- Normalising the settings the plugin context, the view and the listeners of
  the profile list event see. They keep the configured value, the paginator
  carries the one that applies.

## Decisions

1. **Both pagination settings in `ignoreFlexFormSettingsIfEmpty`**, appended
   after `showFields`. An empty or `0` field of the element now means "the
   value of the site". Rejected: a FlexForm default of 10, which would only
   reach elements saved after the change and would hide the value of the site
   from new elements as well.
2. **A guard in the controller, with 10 and 5 as the fallback.** The constant
   or the site setting can still hold `0`, an empty value or a negative number,
   and a FlexForm field can hold `-3`, which Extbase does not drop. The
   controller reads the value with `?? 0` and replaces anything below 1.
   Rejected: falling back to the value of the site, which is the value that
   was just found to be below 1, and falling back to 1, the old default.
3. **The shipped default is 10 in the constant and in the definition of the
   site set `Full`.** Site sets exist on TYPO3 v13 only, so on v12 the
   constant alone carries it. Both mechanisms declare the same value, which
   the site set test of v13 asserts.
4. **No `docs/` paragraph.** On `main` the change extends a section of
   `docs/architecture/typoscript-and-site-sets.md` about a site value an
   element overrides in both directions. That section belongs to a change
   that is not on this branch, and the paragraph names the partner and job
   list pagination, which this branch does not have. The extension's
   changelog entry and upgrade guide carry the integrator facing part.

## Risks / Trade-offs

- [An installation relied on the default of one profile per page] → The 2.4
  changelog entry and the upgrade guide from 2.3 to 2.4 name the change and
  the setting that restores the old value.
- [An element saved with `0` to mean something] → `0` never worked, it threw.
  It now takes the value of the site.
