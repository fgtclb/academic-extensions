## 1. Premises

- [x] 1.1 Count every `f:translate` call with an underscored extension name, inline, as a
  tag, over several lines and escaped inside another view helper's argument: 274 in 76
  templates on `main`, all of them translations. Find the PHP that translates with an
  underscored name: the three sorting select view helpers, the profile editor controller
  (seven calls) and option service, the select items trait of `academic_base`.
- [x] 1.2 Probe the casing on both core versions with the partner test: underscored reads
  the wrong path on v13, lowercase loses the language file on both, UpperCamelCase works on
  both. Recorded in `design.md` and `docs/architecture/label-overrides.md`.
- [x] 1.3 Read v13 and v14 `LocalizationUtility`: v13 applies overrides to full `LLL:` keys
  too; v14 reads the plugin path only from a request handed to `translate()`.

## 2. Tests first

- [x] 2.1 Add `*LabelOverrideTest` to `academic_partners`, `academic_projects`,
  `academic_programs`, `academic_persons`, `academic_persons_edit`, `academic_jobs`,
  `academic_bite_jobs` and `academic_study_plan`: per kind of call, the label of the
  language file, the override of the extension, of the plugin, and the plugin winning.
  On v13 every override case failed before the change (15, 19, 15, 15, 28, 21, 9 and 3
  failures), the language file cases passed.
- [x] 2.2 Shape the fixtures around the v13 leak: hide the category filter, and render the
  sorting select from a partial that holds it alone. The first partner run passed two cases
  because of the leak; with the default name of the view helpers reverted, the three
  sorting option cases fail on v13.
- [x] 2.3 Add `TranslationExtensionNameTest` to `packages-dev/monorepo-shared`; it failed
  for the nine extensions with underscored names, for the six translations without a name
  (the editor's help texts and section heading, the persons subline, the two job property
  values), and for an underscored name handed to `translate()` in the editor's controller.
- [x] 2.4 Prove the PHP cases on v14: the sorting select options, the editor's controller
  and option service labels, the job form options and the job alert lost the override of
  the plugin without the request, and read it with it.
- [x] 2.5 Add the editor's help texts (template and PHP), the heading of a document section
  and a country option: on v14 all 15 override cases failed before the calls named the
  editor.
- [x] 2.6 Add a literal help text for a contract and a contact field (fixture extension
  `test_literal_helptext`): shown as it is; both cases failed with the calls without a name,
  whose short key the core refused.

## 3. Implementation

- [x] 3.1 Rewrite the `extensionName` of every template to UpperCamelCase, the
  `category_types` page module template included.
- [x] 3.2 Let the three sorting select view helpers render the core translate view helper;
  their `extensionName` default becomes UpperCamelCase.
- [x] 3.3 Translate through a helper that spreads the arguments and hands the request on
  v14 in `ProfileController`, `ProfileFieldOptionsService` and `JobController`.
- [x] 3.4 Let the select items trait turn an extension key into the extension name and hand
  the request on v14.
- [x] 3.5 Name the extension in every translation that named none: the editor's help texts,
  section headings and country options, the persons subline, the job property values. The
  editor's controller translates through one method that defaults to its name.
- [x] 3.6 Document that the settings taking a label reference accept an `LLL:EXT:`
  reference, not a translation domain reference of TYPO3 v14.

## 4. Documentation

- [x] 4.1 A `Labels` page per extension's configuration chapter: the paths, the path of
  each content element, where only the extension path applies, and every key with the
  templates that show it.
- [x] 4.2 `Important-LabelOverridesUseTheDocumentedPath.rst` in `Changelog/2.4/` of the eight
  extensions, `Important-SelectItemLabelsReadTheDocumentedPath.rst` of `academic_base`, the
  same on both branches: they describe what 2.4 changes. What only `main` has - the plugin
  override of labels translated in PHP on TYPO3 v14, the profile editor of this version -
  is in `Changelog/3.0/` of partners, projects, programs, jobs, the editor and
  `academic_base`. ACE-739's entries of partners, projects and programs say to move instead
  of copy. The sentence of academic_persons on overrides that reached some labels before is
  dropped: its cause, the unnamed subline of 3.0, was never released.
- [x] 4.3 Name `LANG.resourceOverrides` next to `SYS.locallangXMLOverride`, each with its
  core version, where a text named only the old key.
- [x] 4.4 `docs/architecture/label-overrides.md`, linked from the index;
  `list-filter-types.md`, the unit test, fixture extension, quality gate and monorepo
  layout pages and `AGENTS.md` updated.

## 5. Definition of done

- [x] 5.1 `lintPhp`, `cgl -n`, `phpstan`, `unit` and `functional -j auto` for TYPO3 v13 and
  v14, each after its own `composerUpdate`.
- [x] 5.2 `checkRstRenderingAll` and `lintMarkdown -n`.
- [x] 5.3 Commit message in the TYPO3 Core format with `ACE-740`; this change archived as
  the last commit of the pull request.
