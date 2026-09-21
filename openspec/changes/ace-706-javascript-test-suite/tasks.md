## 1. The harness

- [x] 1.1 Extract `Build/extensions.mjs` from `esbuild.mjs`, with the
      `specifier` the resolve hook needs, and prove `buildJs` still writes the
      same artifacts.
- [x] 1.2 Add `Build/tests/register.mjs`, `resolve-hook.mjs`, `dom.mjs` and
      `dom.d.mts`, re-derived for the four modules this branch ships.
- [x] 1.3 Add `Build/tsconfig.tests.json`, the `paths` entry the tests import
      through, and the second project in `typecheck.mjs`.
- [x] 1.4 Add `jsdom` and the `test` script to `Build/package.json`, and commit
      the regenerated lock file.

## 2. The suite

- [x] 2.1 Add the `testJs` suite to `Build/Scripts/runTests.sh`, its help entry
      and its example, and update the node suite list from seven to eight.
- [x] 2.2 Add the step to the `frontend-assets` job of `ci.yml`, between the
      type check and `checkJsBuildClean`.
- [x] 2.3 Update the suite lists of `AGENTS.md`.

## 3. The tests

- [x] 3.1 The harness's own tests below
      `packages/fgtclb/academic-study-plan/Tests/JavaScript/Harness/`: the
      installed DOM, what jsdom does not provide, and module resolution.
- [x] 3.2 `academic-study-plan.test.ts`: the filter by pointer and by keyboard,
      the module dialog, the accordion below the breakpoint, and the hostile
      category title of ACE-705.
- [x] 3.3 Make the module loadable by node — declare and assign the container
      instead of a parameter property, export the initialiser — and rebuild.
- [x] 3.4 Put the string substitution of ACE-705 back and watch 3.2 go red;
      restore.

## 4. Documentation

- [x] 4.1 `docs/testing/javascript-tests.md`, written for this branch, linked
      from `docs/testing/Index.md`.
- [x] 4.2 No changelog entry: nothing a user or an integrator can notice
      changes. `docs/workflow/changelog-and-documentation.md` names the harness
      as one of the cases that ship without one.

## 5. Definition of done

- [x] 5.1 `lintPhp`, `cgl -n`, `phpstan`, `unit` and `functional` green on
      TYPO3 v12 and v13, each after its own `composerUpdate`.
- [x] 5.2 `buildJs`, `checkJsBuildClean`, `lintTypescript -n`, `typecheckJs`,
      `testJs`, `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 5.3 Commit message in TYPO3 Core format referencing ACE-706; the change
      archived as the last commit of the same pull request.
