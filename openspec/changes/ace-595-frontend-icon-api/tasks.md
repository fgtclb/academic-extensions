## 1. Verify the premises

- [x] 1.1 After `composerUpdate` for each core version, confirm that the
  JavaScript icon API of the backend fetches from the backend AJAX route
  `/ajax/icons` and renders through the icon factory of TYPO3, that
  `AbstractSvgIconProvider`, `SvgSpriteIconProvider`, `IconSize` and
  `PackageDependentCacheIdentifier` exist with the API `design.md` uses, and
  that `RenderingContextInterface::getAttribute()` exists on both cores.
- [x] 1.2 Order every `RequestMiddlewares.php` of the installed vendor tree with
  core's `DependencyOrderingService`, once per core version, and confirm that
  the endpoint sorts after `typo3/cms-frontend/site` and `maintenance-mode` and
  before both authenticators and `page-resolver`, and that "after
  `base-redirect-resolver`, before `authentication`" is a cycle on both.

## 2. Fixture extension

- [x] 2.1 Add `academic-base/Tests/Functional/Fixtures/Extensions/test_frontend_icon_api`
  (`tests/frontend-icon-api`) as `docs/testing/fixture-extensions.md` describes:
  a `Configuration/FrontendIcons.php` that replaces a shared icon, registers
  the identifier shapes of a category type and a category group, and one icon
  per provider to serve or refuse (`currentColor`, the core SVG provider, a
  bitmap, a sprite). Verify with `composerUpdate` that it is found on both core
  versions, and with `theFixtureLoadsAfterAcademicBase()` that it loads after
  `academic_base`.

## 3. The JSON icon map

- [x] 3.1 Unit `academic-base/Tests/Unit/Imaging/FrontendIconRendererTest.php`
  for the identifier pattern and the sizes. Shown to fail on TYPO3 v13 by
  anchoring the pattern with `^` and `$` instead of `\A` and `\z`: the two
  cases with a trailing line feed go red.
- [x] 3.2 Functional `academic-base/Tests/Functional/Imaging/FrontendIconRendererTest.php`:
  the inline markup of each identifier in the order asked for and once, the
  replacement of a site package, the category type shapes, the core SVG
  provider inlined, every refusal on its own and no icon of the backend
  registry. Shown to fail on TYPO3 v13 by serving `default-not-found`, by
  dropping the sprite exclusion, by dropping the provider check (each red in
  its refusal case) and by giving an unknown provider the SVG provider of
  TYPO3 (red for the core icon `actions-add`, an unregistered identifier and
  `neverServesAnIconOfTheBackendRegistry()`).
- [x] 3.3 Functional `academic-base/Tests/Functional/ViewHelpers/FrontendIconMapViewHelperTest.php`:
  the markup equals what the frontend icon factory renders inline, what is not
  served is left out, an empty object, the size, the escaping and the refused
  size. Shown to fail on TYPO3 v13 by dropping `JSON_HEX_TAG` (red in the
  escaping test and in the markup comparison).
- [x] 3.4 Implement the renderer and the ViewHelper as `design.md` describes,
  both classes `@internal`, and verify with tasks 3.1 to 3.3.
- [x] 3.5 Add `Feature-FrontendIconMap.rst` to the 3.0 changelog of
  `academic_base`, a section "Icons in frontend JavaScript" to its icon
  chapter and the ViewHelper to its extension points page, describing the
  JSON icon map as public API. Verify with `checkRstRenderingAll` and the
  extension point check of `packages-dev/monorepo-shared`.

## 4. The icon endpoint

- [x] 4.1 Functional `academic-base/Tests/Functional/Middleware/FrontendIconEndpointTest.php`:
  the answer, the default size, the replacement of a site package, every base,
  32 identifiers, every bad request with `400`, every other method with `405`,
  `HEAD`, the year for the current token, five minutes otherwise, the `ETag`
  and `304`, no cookie, and every other path passed on. Shown to fail on
  TYPO3 v13 by accepting 33 identifiers (red for "more than 32 identifiers"),
  by accepting `POST` (red for `POST`), by never answering as immutable (red
  in both tests of the current token), by never matching the `ETag` (red in
  the three `304` cases) and by placing the middleware after the frontend user
  authentication (red in `neverStartsASessionOrSendsACookie()`).
- [x] 4.2 Functional `academic-base/Tests/Functional/Middleware/FrontendIconEndpointMaintenanceTest.php`:
  a site in maintenance answers `503`. Shown to fail on TYPO3 v13 by placing
  the middleware before `maintenance-mode`.
- [x] 4.3 The ViewHelper names the endpoint below the base of the current site
  language and the version token with `endpoint="1"`, and nothing outside a
  site. Shown to fail on TYPO3 v13 by using the base of the site instead of the
  site language (red in `namesTheEndpointBelowTheBaseOfTheSiteLanguage()`).
- [x] 4.4 The version token in the unit test of the renderer: stable, changed by
  a replaced registration, an added icon, a source file, a provider class file
  or a parent of it and the package dependent cache identifier, and blind to
  icons that are not served and to the order of the registry. Shown to fail on
  TYPO3 v13 by reading the provider class only, without its parents, by
  dropping the `filemtime()` of the source and by dropping the sort, each red
  in its own test.
- [x] 4.5 Implement the middleware, its `Configuration/RequestMiddlewares.php`
  and the token as `design.md` describes, and verify with tasks 4.1 to 4.4.
  Document the endpoint, the token and the web server requirement in the icon
  chapter, in `Feature-FrontendIconEndpoint.rst` and on the extension points
  page, and in the icon chapter that web nodes that deploy separately compute
  different tokens.

## 5. The JavaScript icon factory

- [x] 5.1 `academic-base/Tests/JavaScript/icons.test.ts` with jsdom and the
  fetch double: reading maps, a map added later, sizes kept apart, batching
  sorted by identifier, one request per size, the split at 32, once per page,
  the version, rejection of a left-out icon, a left-out icon answered by a map
  that arrives after its request or while it is under way, retry after a failed
  request, the malformed identifier, the ten second timeout, `prefetch()`,
  `getIconElement()`, `endpointFrom()` and the two limits read out of the PHP
  sources. Shown to fail by sending credentials (`same-origin`), by a batch size
  of 33 (red in the split and in the limit read from the PHP source), by
  allowing upper case in the pattern (red in the pattern read from the PHP
  source), by a timeout of twenty seconds, by sending a malformed identifier, by
  sending a batch in the order asked for (red in the batch and the split), by
  not reading the maps again for a refused icon and by not letting a map replace
  a refusal (both red for the map that arrives after the request), and by not
  reading the maps when the answer leaves an icon out (red for the map that
  arrives while the request is under way), each red in its test.
- [x] 5.2 Implement `Resources/Private/TypeScript/frontend/icons.ts`, the
  `Configuration/JavaScriptModules.php` for the `frontend/` prefix and the
  `Build/tsconfig.json` paths entry, build it with `buildJs`, and verify with
  task 5.1, `typecheckJs`, `lintTypescript` and `checkJsBuildClean`. Tag the
  exports `@api` and document the module in the icon chapter, in
  `Feature-FrontendIconFactory.rst` and on the extension points page.

## 6. The demonstration on the development instances

- [x] 6.1 Functional `packages-dev/dev-site/Tests/Functional/IconOverviewFrontendApiTest.php`:
  the first list is answered from the JSON map next to it, the second map names
  the endpoint and the token and the endpoint answers it without `actions-add`,
  and the import map carries the demo module and the prefix of
  `academic_base`. Shown to fail on TYPO3 v13 by removing `endpoint="1"` from
  the second map and by dropping `academic_base` from the dependencies of the
  import map of the package, each red in its test.
- [x] 6.2 Add the section, `icon-demo.ts` and the
  `Configuration/JavaScriptModules.php` of `packages-dev/dev-site`, without a
  seed change, and verify with task 6.1 and in a browser on both instances.

## 7. Documentation

- [x] 7.1 `docs/architecture/icons.md` gets "Icons for frontend JavaScript".
  `docs/development/frontend-assets.md` and `docs/development/instances.md`
  name the module, the demo and the build. Verify with `lintMarkdown -n`.
- [x] 7.2 Re-measure with the commands the pages document and update the
  counts this change moves: the test classes of
  `docs/testing/functional-tests.md` and `docs/testing/unit-tests.md`, the
  fixture count and row of `docs/testing/fixture-extensions.md`, the
  `#[Autoconfigure]` sites of `docs/architecture/dependency-injection.md` and
  the files, classes, `final` share, `readonly` modifiers and
  `final readonly class` count of `docs/architecture/class-design.md`.

## 8. Definition of done

- [x] 8.1 After `composerUpdate` for TYPO3 v13: `lintPhp`, `cgl -n`, `phpstan`
  and `unit` green, and `functional` of `academic_base` and
  `packages-dev/dev-site`, where everything this change adds is tested.
- [x] 8.2 After `composerUpdate` for TYPO3 v14: the same gates green.
- [x] 8.3 `testJs`, `lintMarkdown -n`, `checkRstRenderingAll` and
  `openspec validate --all --strict` green.
- [x] 8.4 `docs/` is updated as in section 7, and `README.md` and
  `CONTRIBUTING.md` still only summarize.
- [x] 8.5 The three Feature changelog entries of `academic_base` exist, and
  its extension points page lists the JSON icon map, the endpoint and the
  module as public API.
- [x] 8.6 Commit in TYPO3 Core format, one commit per section 3 to 6, with the
  verified key ACE-595 and without attribution of any tool, and archive the
  change as the last commit of the pull request.
