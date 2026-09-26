## Context

`f:translate` and `LocalizationUtility::translate()` read `_LOCAL_LANG` of the extension
name they are given. TYPO3 v12 and v13 build `plugin.tx_` + the name, lowercased. The
templates passed the extension key, so they read `plugin.tx_academic_<extension>`. ACE-739
changed the filter partials; 249 calls in 70 templates were left, plus PHP that translates
itself: the three sorting select view helpers, the select items trait of `academic_base`
used by the job form, and the profile editor - its flash messages with the key, the
options of its selects with `persons_edit`.

The facts are measured on `main` and again here, with the label tests failing on v12 and
v13 before the change (details in `docs/architecture/label-overrides.md`):

- `AcademicPartners` reads the documented paths and the language file.
- `academicpartners` reads the paths but loses the language file, which is found through
  the camel case of the name.
- The overrides of the name apply to a full `LLL:EXT:` key too.
- The core keeps the labels of a language file, overrides included, for the whole request,
  so a correctly named translation leaks its overrides into later ones.

## Goals / Non-Goals

**Goals:**

- Every frontend label of the eight extensions reads the override of the extension and of
  the rendering plugin on v12 and v13, the plugin winning.
- A test per extension that proves each kind of call, and a check that keeps new templates
  from passing an underscored name or none.

**Non-Goals:**

- The page module template of `category_types`: backend, no `_LOCAL_LANG`. It is converted
  to `CategoryTypes` anyway so that the check needs no exception.

## Decisions

- **UpperCamelCase in every template**, by the same mechanical rewrite as on `main`.
  Rejected: the lowercase name without underscores - it loses the language file.
- **No request handling.** `main` hands the Extbase request to `translate()` because TYPO3
  v14 reads the plugin path only from it. v12 and v13 take it from the configuration
  manager, so the view helpers and controllers here only change the name.
- **The select items trait of `academic_base` converts the key itself**, as on `main`.
- **Every translation names its extension.** The two job property values that passed no
  name now pass `AcademicJobs`, as on `main`.
- **Tests shaped around the leak**: a case proves its own call only as the first
  translation of its file in the request. The list fixtures hide the category filter and
  render the sorting select from a partial of the test that holds the select alone; the
  cases after the first one are held by the static check, which also rejects an
  underscored name handed to `translate()` in PHP.
- **The words before a single year of the profile editor translate with the name of
  academic_persons**, now `AcademicPersons`: `detail.since` and `detail.till` are labels of
  that extension's file, shared with the profile detail, so they read its path and not the
  editor's; the spec names the exception.

## Risks / Trade-offs

- A site that put overrides under `plugin.tx_academic_<extension>`, or under
  `plugin.tx_persons_edit` for the editor's options, loses them with the update. The
  `Important-` entries say to move them.
