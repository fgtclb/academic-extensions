## 1. Backport analysis

- [x] 1.1 Diff `Resources/Private/TypeScript/frontend/academic-study-plan.ts`
      between `main` and `2` and record every difference in `proposal.md`,
      with whether it touches the substitution this change is about.
- [x] 1.2 Confirm that the template renders the same three placeholders here,
      in the same two places each.

## 2. Implementation

- [x] 2.1 Add `colourOf()` and `substitutePlaceholders()` and rewrite the body
      of `buildCategoryFilter()` to clone the item and substitute into the
      clone's attribute values and text nodes.
- [x] 2.2 Run `buildJs`, commit the artifact, and check `checkJsBuildClean`,
      `lintTypescript -n` and `typecheckJs`.

## 3. Documentation

- [x] 3.1 `Documentation/Changelog/2.4/Important-StudyPlanCategoryTitleIsText.rst`.
- [x] 3.2 Say in the commit message and the pull request that the fix arrives
      without a test, and why.

## 4. Definition of done

- [x] 4.1 `lintPhp`, `cgl -n`, `phpstan` and `unit` green on TYPO3 v12 and
      v13, each after its own `composerUpdate`.
- [x] 4.2 `functional` green on TYPO3 v12 and v13. The change cannot affect
      it - nothing here executes JavaScript - but the artifact is committed
      and the suite renders the element.
- [x] 4.3 `checkJsBuildClean`, `lintTypescript -n`, `typecheckJs`,
      `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 4.4 Commit message in TYPO3 Core format referencing ACE-705; the change
      archived as the last commit of the same pull request.
