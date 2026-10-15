## Context

`ProfileController::listAction()` of `academic_persons` serves both list
elements. It reads `settings.pagination.resultsPerPage` and
`settings.pagination.numberOfLinks` with `(int)`, after Extbase merged the
FlexForm of the element over the TypoScript settings. The shared TypoScript
`Configuration/TypoScript/Shared/allow_typoscript_override_empty_flexform_settings.typoscript`
names the settings Extbase drops from the FlexForm while they are empty
(`ignoreFlexFormSettingsIfEmpty`), and the two pagination settings are not in
it. `FrontendConfigurationManager::removeIgnoredFlexFormSettingsIfEmpty()`
resolves every entry as a dotted path below `settings` and drops a value of
`''` or `'0'`, on TYPO3 v13 and v14 alike (and on v12, where the option was
introduced with 12.3).

`ArrayPaginator` and `QueryResultPaginator` throw for fewer than one item per
page. `NumberedPagination` of `georgringer/numbered-pagination` treats a
number of links below 1 as its own default of 10.

## Goals / Non-Goals

**Goals:**

- One rule for both list elements, both paginators and both pagination
  classes.
- The same fallback values as the partner and job lists.

**Non-Goals:**

- Normalising the settings the plugin context, the view and the listeners of
  the plugin view event see. They keep the configured value, the paginator
  carries the one that applies.

## Decisions

1. **Both pagination settings in `ignoreFlexFormSettingsIfEmpty`.** An empty or
   `0` field of the element now means "the site value", which is what the
   manual already tells an editor for the detail page. Rejected: a FlexForm
   default of 10, which would only reach elements saved after the change and
   would hide the site setting from new elements as well.
2. **A guard in the controller, with 10 and 5 as the fallback.** The site
   setting or a TypoScript constant can still hold `0`, an empty value or a
   negative number, and a FlexForm field can hold `-3`, which Extbase does not
   drop. The controller reads the value with `?? 0` and replaces anything below
   1, the pattern of `PartnerController::assignPagination()` and
   `JobController`. Rejected: falling back to the site value, which is the
   value that was just found to be below 1, and falling back to 1, which is
   the old default and the reason the issue reports a list of one profile per
   page.
3. **The shipped default of the site setting and of the constant is 10.**
   Both mechanisms declare the same value, as the site set test demands. The
   constant keeps its type and label.
4. **No FlexForm `eval`.** The fields stay free text. A backend validation
   would not repair stored values, and the controller guard covers them.

## Risks / Trade-offs

- [An installation relied on the default of one profile per page] → The 2.4
  changelog entry and both upgrade guides from 2.3 name the change and the
  setting that restores the old value.
- [An element saved with `0` to mean something] → `0` never worked, it threw.
  It now takes the site value.
