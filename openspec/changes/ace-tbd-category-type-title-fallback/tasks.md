## 1. Verify the premises

- [ ] 1.1 Register a type `teaching_form` for the group `programs` in a test
  fixture extension, add it to `facts.fields` and `filter.categoryTypes`, and
  confirm on `main` that the fact row and the filter select render without a
  label. If they do not, stop and update this change.

## 2. The title ViewHelper

- [ ] 2.1 Add the `categoryTypeTitle` ViewHelper to `category_types`: the
  registered title of a type of a group, resolved for the site language of the
  request, empty for an unknown type. Unit test for a literal title, an `LLL:`
  title and an unknown type, each shown to fail with the ViewHelper returning
  an empty string.

## 3. Adopt it in the three extensions

- [ ] 3.1 programs: pass the title as `default` in the facts item (category
  type facts only), the list filter and the finder.
- [ ] 3.2 partners: pass it in the partner page categories, the partner card,
  the partnership list and teaser items and the list filter.
- [ ] 3.3 projects: pass it in the project page categories, the project card
  and the list filter.
- [ ] 3.4 Functional tests per extension, on both core versions: a fixture
  type without a label renders its registered title in the facts or
  categories and in the filter, a German page renders the German title, and a
  `_LOCAL_LANG` label wins. Show each to fail by removing the `default`
  argument.

## 4. Documentation

- [ ] 4.1 Replace the "needs that label added" sentences of the programs
  manual (facts and filter chapters) and the corresponding passages of the
  partners and projects manuals.
- [ ] 4.2 Document the ViewHelper in the `category_types` manual and on the
  extension points page of `academic_base`.
- [ ] 4.3 `Feature-` changelog entries in programs, partners, projects and
  category types, naming the `default` argument an override adds.
- [ ] 4.4 `docs/architecture/label-overrides.md`: the order plugin label,
  extension label, registered title.

## 5. File the issue

- [ ] 5.1 File the ACE issue (Story, Version 3.0.0, subtask of ACE-10), relate
  it to ACE-733, ACE-736 and ACE-739, and rename the change to
  `ace-<NNN>-category-type-title-fallback`.

## 6. Definition of done

- [ ] 6.1 After `composerUpdate` for TYPO3 v13: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` green.
- [ ] 6.2 After `composerUpdate` for TYPO3 v14: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` green.
- [ ] 6.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 6.4 `docs/` is updated; `README.md` and `CONTRIBUTING.md` still only
  summarize.
- [ ] 6.5 Commit as `[FEATURE] ACE-<NNN>: Label own category types by their
  title` in TYPO3 Core format, and archive the change as the last commit of
  the pull request.
