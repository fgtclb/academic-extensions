## 1. Backport analysis

- [x] 1.1 Diff `map.ts` between `main` and `2` and record every difference;
      confirm the two fixes are the whole of it.
- [x] 1.2 Establish what of `main`'s ACE-562 is *not* being backported, and say
      so in `proposal.md` and in the changelog entry.

## 2. Test first

- [x] 2.1 Take over `main`'s behavioural test, adapting the one comment that
      refers to its repository filter: a fixture with a located partner, one
      without coordinates, one at 0/0 in another spelling, and one on the prime
      meridian, driven after parsing has finished, with the console warning
      captured.
- [x] 2.2 Run it against the unfixed module and watch it go red.

## 3. Implementation

- [x] 3.1 Call `initializeMap()` immediately unless the document is still
      parsing.
- [x] 3.2 Test absence on the raw attribute, use `Number.isFinite()`, refuse
      the pair 0/0 and keep a single zero.
- [x] 3.3 Mutation: put each of the two back in turn and watch 2.1 go red.
- [x] 3.4 Run `buildJs` and commit the artifact.

## 4. Documentation

- [x] 4.1 `Important-PartnerMapDrawsOnLateModuleLoad.rst` and
      `Important-PartnerMapSkipsPartnersWithoutCoordinates.rst` in
      `Documentation/Changelog/2.4/`, the second naming what stays behind.

## 5. Definition of done

- [x] 5.1 `lintPhp`, `cgl -n`, `phpstan`, `unit` and `functional` green on
      TYPO3 v12 and v13, each after its own `composerUpdate`.
- [x] 5.2 `buildJs`, `checkJsBuildClean`, `lintTypescript -n`, `typecheckJs`,
      `testJs`, `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 5.3 Commit message in TYPO3 Core format referencing ACE-708; the change
      archived as the last commit of the same pull request.
