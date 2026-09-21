## 1. Test first

- [x] 1.1 A jsdom fixture with two plans of the same identifier: assert that
      both filters were rebuilt, that both semester headers were prepared, and
      that filtering in the second leaves the first alone.
- [x] 1.2 Run it against the unfixed module and watch it go red.

## 2. Implementation

- [x] 2.1 Key `instances` by the container element, drop the identifier lookup,
      and say in the code why the value is not the key.
- [x] 2.2 Correct the module docblock, which promised the old key.
- [x] 2.3 Run `buildJs` and commit the artifact.

## 3. Documentation

- [x] 3.1 `Documentation/Changelog/2.4/Important-*.rst`: a page that renders
      the element twice renders something usable afterwards and did not before.

## 4. Definition of done

- [x] 4.1 `lintPhp`, `cgl -n`, `phpstan`, `unit` and `functional` green on
      TYPO3 v12 and v13, each after its own `composerUpdate`.
- [x] 4.2 `buildJs`, `checkJsBuildClean`, `lintTypescript -n`, `typecheckJs`,
      `testJs`, `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 4.3 Commit message in TYPO3 Core format referencing ACE-707; the change
      archived as the last commit of the same pull request.
