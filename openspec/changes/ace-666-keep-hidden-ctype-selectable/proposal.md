## Why

Every academic extension hides its content types for the whole installation
through the page TSconfig it always loads, and re-enables them only where a
site uses the component set or the component's page TSconfig entry. On any
other page, an existing academic content element opens with a Type field that
has no matching option; the first option is preselected and saving the record
silently rewrites its content type. TYPO3 shows its "invalid value" option for
unknown values, but deliberately not for values hidden by page TSconfig.

This backports the change of the same name from `main`, archived there as
`openspec/changes/archive/2026-09-15-ace-666-keep-hidden-ctype-selectable/`
(commits `a125ddde8` and `66953b095`, pull request #633).

## What Changes

- An existing content element whose stored type is an academic content type
  hidden by page TSconfig on its page keeps that type as the selected option,
  labelled with the suffix "(not enabled on this page)".
- Only the stored value of the record being edited is added. New content
  elements, the new content element wizard and other content types keep core
  behaviour.
- The change lives in academic_base (`packages/fgtclb/academic-base`) and covers
  the content types of every academic extension without a change to them.
- The behaviour is identical on TYPO3 v12 and v13: the core code that drops the
  value is the same in 12.4.45 and 13.4.35, and so is the DataHandler code that
  stores the kept value unchanged.

## Capabilities

### New Capabilities

- `academic-base/hidden-content-type-preservation`: how the backend form treats
  an existing academic content element whose type is hidden on its page.

### Modified Capabilities

None.

## Impact

- academic_base: a backend form data provider, its registration in a new
  `ext_localconf.php`, and one label (English and German).
- Content elements of academic_bite_jobs, academic_contacts4pages,
  academic_jobs, academic_partners, academic_persons, academic_persons_edit,
  academic_programs, academic_projects and academic_study_plan are covered; none
  of those extensions changes.
- No database, TCA, TSconfig or dependency change.
- Differences to `main`, all consequences of this branch's versions: no ordering
  against the TYPO3 v14 backend layout restriction (v14 is not supported here),
  and a class without `readonly` (PHP 8.1).

## Non-goals

- Changing the design that hides academic content types by default.
- Content types outside the `academic` item group. The general behaviour is a
  core issue and is reported to TYPO3 instead.
- Offering a hidden type for new records or through the wizard.
- Repairing content elements that were already rewritten.
