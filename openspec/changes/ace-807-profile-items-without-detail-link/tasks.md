## 1. Verify the premises

- [x] 1.1 On `main`, render a persons list without a detail page in a
  functional test on both core versions and confirm that the name links to
  the current page with the detail arguments, and that an empty `link`
  renders the name without an anchor. If either does not hold, stop and update
  this change.

## 2. The settings

- [x] 2.1 Add `plugin.tx_academicpersons.detailLink` (`type: string`, `enum`
  `link` and `none`, default `link`) to
  `Configuration/Sets/Full/settings.definitions.yaml`, the same default to
  `Configuration/TypoScript/Default/constants.typoscript`, and
  `settings.detailLink` to `setup.typoscript`. Verify that the existing test
  that compares set and constant defaults covers the new path, or extend it.
- [x] 2.2 Add `detailLink` to `ignoreFlexFormSettingsIfEmpty`.
- [x] 2.3 Add the select `settings.detailLink` ('', `link`, `none`) to
  `Core13/List.xml`, `Core14/List.xml`, `SelectedProfiles.xml` and
  `SelectedContracts.xml`, with backend labels in English and German. Try the
  `displayCond` on the CType for the list-and-detail element on both core
  versions; keep it if it hides the field there, otherwise drop it and say so
  in the field description.
- [x] 2.4 Make `Profile/Item/DetailLink.html` render nothing when
  `settings.detailLink` is `none`, no `detailPid` is passed and the element is
  not the list-and-detail element.

## 3. Tests

- [x] 3.1 Functional tests on both core versions:
  - site setting `none` with an empty choice: list, card, selected profiles
    and selected contracts without a link;
  - default settings: with the link;
  - element `none` under the site default: no link in that element only;
  - element `link` under site `none`: the link;
  - the list-and-detail element linking under `none`;
  - a passed detail page linking under `none`;
  - the same result through the site set and the static template.

  Show the "without a link" tests to fail by removing the condition from the
  partial, and the "empty choice uses the site setting" test to fail by
  removing `detailLink` from `ignoreFlexFormSettingsIfEmpty`.

## 4. Documentation

- [x] 4.1 Describe the site setting and the element choice in the
  configuration chapter of the persons manual and in the passage on the detail
  page of `Documentation/Templates/Partials/Index.rst`.
- [x] 4.2 `Feature-` changelog entry in `academic_persons`, with the condition
  an override of the detail link partial adds.
- [x] 4.3 Check whether a `docs/` page names the detail link partial or
  `ignoreFlexFormSettingsIfEmpty` and update it.

## 5. File the issue

- [x] 5.1 File the ACE issue (Story, Version 3.0.0, subtask of ACE-17), relate
  it to ACE-716, and rename the change to
  `ace-807-profile-items-without-detail-link`.

## 6. Definition of done

- [x] 6.1 After `composerUpdate` for TYPO3 v13: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` green.
- [x] 6.2 After `composerUpdate` for TYPO3 v14: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` green.
- [x] 6.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 6.4 `docs/` is updated, and `README.md` and `CONTRIBUTING.md` still only
  summarize.
- [x] 6.5 Commit as `[FEATURE] ACE-807: Show profile names without a
  link` in TYPO3 Core format (the planned subject exceeds 52 characters),
  and archive the change as the last commit of the pull request.
