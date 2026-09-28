## 1. The build

- [x] 1.1 Add `leaflet` 1.9.4 and `leaflet.markercluster` 1.5.3 as exact
  versions to `Build/package.json`, update the lock file.
- [x] 1.2 `Build/vendor.mjs` writes `leaflet.js` and `markerCluster.js` as
  classic scripts publishing `LeafletObject`, and `--list-outputs` names them.
- [x] 1.3 `map-libraries.test.ts` evaluates the built scripts. Shown red on the
  old copies.

## 2. The popup

- [x] 2.1 `map.test.ts`: the popup is an anchor around a bold title, and a title
  with markup characters is shown as written. Shown red with the string popup.
- [x] 2.2 `map.ts` builds the popup from elements.

## 3. Documentation

- [x] 3.1 `docs/development/frontend-assets.md`: the libraries built from their
  packages. `docs/testing/javascript-tests.md`: the test that reads built files.
- [x] 3.2 `Documentation/Changelog/2.4/Important-PartnerMapLibrariesFromNpm.rst`.

## 4. Commit

- [ ] 4.1 Commit as `[TASK] ACE-771: Build partner map libraries from npm` in
  TYPO3 Core format, with `Resolves: ACE-771`.

## 5. Definition of done

- [ ] 5.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan` and `unit` for
  TYPO3 v12 and v13. The change touches no PHP and no template, so
  `functional` is not required.
- [ ] 5.2 `testJs`, `lintTypescript`, `typecheckJs`, `checkJsBuildClean`,
  `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 5.3 Archive the change as the last commit of the pull request.
