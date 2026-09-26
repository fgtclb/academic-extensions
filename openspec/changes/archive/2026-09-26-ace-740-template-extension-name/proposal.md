## Why

A site changes a label of an extension through `_LOCAL_LANG` in TypoScript, under
`plugin.tx_<extension>` for every plugin of it or `plugin.tx_<extension>_<plugin>` for one.
On TYPO3 v13 almost none of these overrides reach the frontend of the academic extensions:
the templates translate with the extension key, `academic_partners`, and v13 builds the
TypoScript path from that name as given, `plugin.tx_academic_partners`. TYPO3 v14 strips the
underscores and reads the documented path. ACE-739 fixed the filter forms; every other
translation is still affected, including labels translated in PHP.

## What Changes

- Every label the extensions render in the frontend reads the overrides of the extension
  and of the plugin that renders it, on TYPO3 v13 and v14 alike; the one of the plugin wins.
  Page templates and the study plan content element, which no plugin renders, read the
  overrides of the extension.
- This holds for labels of templates and partials, labels whose key comes from a variable
  or from PHP, the options of the sorting selects and of the job form, the option and field
  labels of the profile editor, and the country of a partner's address.
- Translations that named no extension name one: without a name TYPO3 v13 and v14 read
  different paths for a full reference into another extension's file (the help texts of
  the profile editor), and in PHP no path at all.
- On TYPO3 v14 the overrides of a plugin now also reach the labels translated in PHP; they
  reached only the ones translated in templates.
- On TYPO3 v13 an override under the underscored path `plugin.tx_academic_<extension>` stops
  working. An `Important-` changelog entry per extension says what to move; the entries of
  ACE-739 that said to copy the filter labels are updated.
- The documentation names `LANG.resourceOverrides` next to `SYS.locallangXMLOverride`, each
  with its core version, wherever it named only the old key.

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
  the profile editor controller and its option service.
- `academic_base` (`academic-base`): the trait that translates the select items of the job
  form accepts the extension key as before and translates with the extension name.
- A functional test per extension, `Important-` changelog entries in `Changelog/2.4/` - the
  change is backported to branch `2`, where TYPO3 v12 behaves as v13.

## Non-goals

- The page module summary of `category_types`: it renders in the backend, where TypoScript
  label overrides do not apply.
- Translation domains (`domain:`), which TYPO3 v14 recommends and v13 does not know.
- The English labels `academic_bite_jobs` lacks (ACE-741).

## Source

Found while implementing ACE-739. Implemented as ACE-740.
