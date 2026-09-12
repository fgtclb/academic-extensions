## Why

Every academic extension hides its content types for the whole installation
through the page TSconfig it always loads, and re-enables them only where a
site uses the component set. On any other page, such as a site configured
through `sys_template`, the `/legacy/` tree, or a project that removes academic
types itself, an existing academic content element opens with a Type field
that has no matching option. The browser preselects the first option, and
saving the record silently rewrites its content type. TYPO3 shows its "invalid
value" option for unknown values, but deliberately not for values hidden by
page TSconfig, so nothing warns the editor.

## What Changes

- An existing content element whose stored type is an academic content type
  hidden by page TSconfig on its page keeps that type as the selected option,
  labelled with the suffix "(not enabled on this page)".
- Only the stored value of the record being edited is added. New content
  elements, the new content element wizard and other content types keep core
  behaviour.
- The change lives in academic_base (`packages/fgtclb/academic-base`) and covers
  the content types of every academic extension without a change to them.
- The behaviour is identical on TYPO3 v13 and v14; the core code that drops the
  value is the same in 13.4.35 and 14.3.6.

## Capabilities

### New Capabilities

- `academic-base/hidden-content-type-preservation`: how the backend form treats
  an existing academic content element whose type is hidden on its page.

### Modified Capabilities

None.

## Impact

- academic_base: a backend form data provider, its registration in a new
  `ext_localconf.php`, and one label.
- Content elements of academic_bite_jobs, academic_contacts4pages,
  academic_jobs, academic_partners, academic_persons, academic_persons_edit,
  academic_programs, academic_projects and academic_study_plan are covered; none
  of those extensions changes.
- No database, TCA, TSconfig or dependency change.

## Non-goals

- Changing the design that hides academic content types by default.
- Content types outside the `academic` item group. The general behaviour is
  a core issue and is reported to TYPO3 on forge instead.
- Offering a hidden type for new records or through the wizard.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`cross-cutting-06`). Three of the six analysed projects carry their own code
for this today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-keep-hidden-ctype-selectable` when the issue is filed after
implementation.
