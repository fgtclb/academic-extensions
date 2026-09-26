## Why

A site changes a label of an extension through `_LOCAL_LANG` in TypoScript, under
`plugin.tx_<extension>` for every plugin of it or `plugin.tx_<extension>_<plugin>` for one.
On TYPO3 v12 and v13 almost none of these overrides reach the frontend of the academic
extensions: the templates translate with the extension key, `academic_partners`, and both
versions build the TypoScript path from that name as given, `plugin.tx_academic_partners`.
ACE-739 fixed the filter forms; every other translation is still affected, including labels
translated in PHP. Backport of the change of the same name on `main`.

## What Changes

- Every label the extensions render in the frontend reads the overrides of the extension
  and of the plugin that renders it, on TYPO3 v12 and v13; the one of the plugin wins.
  Page templates and the study plan content element, which no plugin renders, read the
  overrides of the extension.
- This holds for labels of templates and partials, labels whose key comes from a variable,
  the options of the sorting selects and of the job form, the flash messages and select
  options of the profile editor, and the country of a partner's address.
- The profile editor translated its select options with `persons_edit`, a name of no
  extension at all; they read the editor's paths now.
- Translations that named no extension name one.
- An override under the underscored path `plugin.tx_academic_<extension>` stops working. An
  `Important-` changelog entry per extension says what to move; the entries of ACE-739 that
  said to copy the filter labels are updated.

## Capabilities

### New Capabilities

- `academic-bite-jobs/label-overrides`, `academic-jobs/label-overrides`,
  `academic-partners/label-overrides`, `academic-persons/label-overrides`,
  `academic-persons-edit/label-overrides`, `academic-programs/label-overrides`,
  `academic-projects/label-overrides`, `academic-study-plan/label-overrides`: which
  TypoScript label overrides the frontend output of the extension reads.

### Modified Capabilities

None. The filter requirements of ACE-739 already say the same for the filter forms.

## Impact

- `academic_bite_jobs` (`academic-bite-jobs`), `academic_jobs` (`academic-jobs`),
  `academic_partners` (`academic-partners`), `academic_persons` (`academic-persons`),
  `academic_persons_edit` (`academic-persons-edit`), `academic_programs`
  (`academic-programs`), `academic_projects` (`academic-projects`), `academic_study_plan`
  (`academic-study-plan`): templates and partials, the three sorting select view helpers,
  the controllers of the profile editor.
- `academic_base` (`academic-base`): the trait that translates the select items of the job
  form accepts the extension key as before and translates with the extension name.
- A functional test per extension, `Important-` changelog entries in `Changelog/2.4/`.

## Non-goals

- The page module summary of `category_types`: it renders in the backend, where TypoScript
  label overrides do not apply.
- The English labels `academic_bite_jobs` lacks (ACE-741).

## Source

Backport of ACE-740 from `main`, re-derived from
`.agent/reports/project-differences-2026-09/BACKPORT-740-template-extension-name.md`.
