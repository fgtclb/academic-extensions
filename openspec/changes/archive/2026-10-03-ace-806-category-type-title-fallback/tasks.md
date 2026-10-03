## 1. Verify the premises

- [x] 1.1 Register a type `teaching_form` for the group `programs` in a test
  fixture extension, add it to `facts.fields` and `filter.categoryTypes`, and
  confirm on `main` that the fact row and the filter select render without a
  label. If they do not, stop and update this change. (Confirmed by the
  functional tests of 3.4 run against the templates of `main`: every title
  case failed.)

## 2. The title ViewHelper

- [x] 2.1 Add the `categoryTypeTitle` ViewHelper to `category_types`: the
  registered title of a type of a group, resolved for the site language of the
  request, empty for an unknown type. Test for a literal title, an `LLL:`
  title and an unknown type, each shown to fail with the ViewHelper returning
  an empty string. (A functional test rather than a unit test, because the
  title is resolved through the language files of the installation.)

## 3. Adopt it in the three extensions

- [x] 3.1 programs: hand the label to the ViewHelper in the facts item, the
  list filter and the finder. A built-in fact has no registered type, so its title
  is empty and its label always exists.
- [x] 3.2 partners: pass it in the partner page categories, the partner card,
  the partnership list and teaser items and the list filter.
- [x] 3.3 projects: pass it in the project page categories, the project card
  and the list filter.
- [x] 3.4 Functional tests per extension, on both core versions: a fixture
  type without a label renders its registered title in the facts or
  categories and in the filter, a German page renders the German title, and a
  `_LOCAL_LANG` label wins. Show each to fail by removing the ViewHelper.
  (The first form, the title as the `default` of `f:translate`, failed the
  label override tests of partners and projects on v13, see the design.)

## 4. Documentation

- [x] 4.1 Replace the "needs that label added" sentence of the programs
  manual (facts chapter, the filter chapter had none) and add the section
  "Category types of a project" to the labels page of the programs, partners
  and projects manuals, which had no passage of their own.
- [x] 4.2 Document the ViewHelper in the `category_types` manual and on the
  extension points page of `academic_base`.
- [x] 4.3 `Feature-` changelog entries in programs, partners, projects and
  category types, showing the call an override adds.
- [x] 4.4 `docs/architecture/label-overrides.md`: the order plugin label,
  extension label, registered title.

## 5. File the issue

- [x] 5.1 File the ACE issue (Story, Version 3.0.0, subtask of ACE-10), relate
  it to ACE-733, ACE-736 and ACE-739, and rename the change to
  `ace-<NNN>-category-type-title-fallback`. (ACE-806)

## 6. Definition of done

- [x] 6.1 After `composerUpdate` for TYPO3 v13: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` green.
- [x] 6.2 After `composerUpdate` for TYPO3 v14: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` green.
- [x] 6.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 6.4 `docs/` is updated, and `README.md` and `CONTRIBUTING.md` still only
  summarize.
- [x] 6.5 Commit as `[FEATURE] ACE-806: Label own category types by title`
  in TYPO3 Core format, and archive the change as the last commit of the pull
  request.
