## 1. category_types

- [ ] 1.1 Add `CategoryCollection::getMostSpecificCategoriesByType()` with
      unit tests in `typo3-category-types/Tests/Unit/Collection/`: parent
      and child keep the child, an unrelated sibling stays, a three-level
      chain keeps the leaf, a parent of another type stays, a parent cycle
      terminates. Make the method return `getAllCategoriesByType()` and
      watch the parent/child test fail.

## 2. academic_programs

- [ ] 2.1 Add `plugin.tx_academicprograms.facts.mostSpecificOnly` to the
      settings definition and the constants (default false) and pass it to
      the plugins and the `program-data` processor; a site set test reads
      the default back.
- [ ] 2.2 Switch the facts builder to the reduced list when the setting is
      on; a functional page test and a details plugin test with "Bachelor"
      plus "Bachelor of Science" render only the latter with the setting on
      and both with it off. Drop the switch and watch the "on" case fail.
- [ ] 2.3 A functional list test asserts the card shows only the child with
      the setting on, and that filtering by the parent lists the same
      programs with the setting on and off.

## 3. Documentation

- [ ] 3.1 Document the setting and the two-level limit in
      `academic_programs` `Documentation/` and in `docs/`, linked from the
      section `Index.md`.
- [ ] 3.2 Add
      `Documentation/Changelog/3.0/Feature-MostSpecificCategoryFacts.rst`
      from `Build/Documentation/Templates/`; check the reST line lengths.

## 4. File the issue

- [ ] 4.1 After implementation, file the ACE issue in YouTrack (relates to
      ACE-620), rename the change to
      `ace-<NNN>-most-specific-categories-only`, and commit in TYPO3 Core
      format `[FEATURE] ACE-<NNN>: <subject>`.

## 5. Definition of done

- [ ] 5.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
      `functional` green for TYPO3 v13; the same after its own
      `composerUpdate` for v14.
- [ ] 5.2 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 5.3 `docs/` and the `Documentation/` changelog updated; `README.md`
      and `CONTRIBUTING.md` only link.
- [ ] 5.4 Archive the change as the last commit of the pull request.
