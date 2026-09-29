## 1. category_types

- [x] 1.1 Add `CategoryCollection::getMostSpecificCategoriesByType()` with
      unit tests in `typo3-category-types/Tests/Unit/Collection/`: parent
      and child keep the child, an unrelated sibling stays, a three-level
      chain keeps the leaf, a parent of another type stays, a parent that is
      not attached is not bridged, a parent cycle terminates and hides
      nothing of its own. Make the method return `getAllCategoriesByType()` and
      watch the parent/child test fail.

## 2. academic_programs

- [x] 2.1 Add `plugin.tx_academicprograms.facts.mostSpecificOnly` to the
      settings definition and the constants (default false) and pass it to
      the plugins and the `program-data` processor; a site set test reads
      the default back.
- [x] 2.2 Switch the facts builder to the reduced list when the setting is
      on; a functional page test and a details plugin test with "Bachelor"
      plus "Bachelor of Science" render only the latter with the setting on
      and both with it off. Drop the switch and watch the "on" case fail.
- [x] 2.3 A functional list test asserts the card shows only the child with
      the setting on, and that filtering by the parent lists the same
      programs with the setting on and off.

## 3. Documentation

- [x] 3.1 Document the setting and the two-level limit in
      `academic_programs` `Documentation/` and in `docs/`, linked from the
      section `Index.md`.
- [x] 3.2 Add
      `Documentation/Changelog/3.0/Feature-MostSpecificCategoryFacts.rst`
      from `Build/Documentation/Templates/`, and
      `Feature-MostSpecificCategoriesByType.rst` in `category_types` for its
      new collection method, and check the reST line lengths.

## 4. File the issue

- [x] 4.1 File the ACE issue in YouTrack, relating it to ACE-620: ACE-774.
- [x] 4.2 Rename the change to `ace-774-most-specific-categories-only`.
- [x] 4.3 Commit as `[FEATURE] ACE-774: Show the most specific category` in
  TYPO3 Core format.

## 5. Definition of done

- [x] 5.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
      `functional` green for TYPO3 v13; the same after its own
      `composerUpdate` for v14.
- [x] 5.2 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 5.3 `docs/` and the `Documentation/` changelog updated; `README.md`
      and `CONTRIBUTING.md` only link.
- [ ] 5.4 Archive the change as the last commit of the pull request.
