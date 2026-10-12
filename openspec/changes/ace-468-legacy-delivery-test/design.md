## Context

See proposal.md for the motivation. Measured on `origin/2` `7ebded7b8`, with
the core read in the TYPO3 13.4.35 tree of `core-13/vendor`, the version the
test runs on. The cause is established by reading, not yet by a run: proving
it is the first task.

- `ExtensionUtility::configurePlugin()` writes every plugin definition with
  `ExtensionManagementUtility::addTypoScript(..., 'defaultContentRendering')`
  (`cms-extbase/Classes/Utility/ExtensionUtility.php:93`).
- `SysTemplateTreeBuilder` appends that bucket to a site set tree
  unconditionally (`createSiteTemplateInclude()`, line 199), and to a
  `sys_template` tree only right after a static template whose identifier is
  listed in `$GLOBALS['TYPO3_CONF_VARS']['FE']['contentRenderingTemplates']`
  (`addStaticMagicFromGlobals()`, lines 577 to 583).
  `cms-fluid-styled-content/ext_localconf.php:8` registers
  `fluidstyledcontent/Configuration/TypoScript/`, and EXT:bootstrap_package 15
  registers its own static template the same way.
- The legacy root record (`sys_template` on page 1001, the only `sys_template`
  row of the v13 seed) names `EXT:academics_dev_site/Configuration/TypoScript`,
  then `EXT:bootstrap_package/Configuration/TypoScript`, then the nine `Full`
  aggregates (`ScenarioLegacy.yaml:111`, written by
  `Build/Scripts/generateLegacyScenario.php`, `templateRecord()`).
- `AbstractSeedTestCase` loads `typo3/cms-fluid-styled-content` and not
  `bk2k/bootstrap-package`. The theme entry resolves to nothing, the legacy
  tree has no content rendering template, and every `tt_content` type falls
  back to the core's "has no rendering definition" message. That matches the
  1157 against 16539 bytes measured for ACE-468.
- `LegacyDeliveryTest::writeSiteConfigurations()` replaces
  `bootstrap-package/full` with `fgtclb/academics-dev-site-page-object`, whose
  only dependency is `typo3/fluid-styled-content`. The `/` side gets FSC in
  the theme's place, the legacy side gets nothing.
- `main` names `EXT:fluid_styled_content/Configuration/TypoScript` first in its
  legacy record since 09c84f789 (ACE-460), which is why its test runs.
- `Build/phpunit/FunctionalTestTimes-<dbms>.json` records the class at `0.0`
  seconds in all four files, because it was skipped when they were written. On
  `main` it is the heaviest functional class, some 2 minutes on SQLite.

Re-checked on `origin/2` `853045d2b`, with the harness on TYPO3 13.4.36 after
`-t 13 -s composerUpdate`. The core lines above are unchanged there (the
`addTypoScript()` call of `configurePlugin()` ends at line 93, the two
`SysTemplateTreeBuilder` lines are 199 and 581 to 583), and the archive of
EXT:bootstrap_package 15.0.4 registers `bootstrappackage/Configuration/TypoScript/`
in `ext_localconf.php:13`. Two facts the reading did not show came out of the
first run:

- The class could never have run on this branch. Since it was ported in
  0b3cf286f (ACE-460) it uses `self::BASE`, `self::LEGACY_SEGMENT`,
  `self::PAGE_OFFSET` and `self::PROFILE_DETAIL_PAGE` without declaring them.
  `main` declares them since 09c84f789. The skip in `setUp()` hid it, and
  phpstan does not analyse `packages-dev/`.
- The status test expects `200` for page 209, the profile detail of hidden
  profiles that ACE-834 added to the seed, and for page 233, the job detail,
  which answers `404` without a job since ACE-839. Both trees answer `404` for
  both pages, which is the behaviour those changes introduced. `main` expects
  `404` for the three detail pages 205, 209 and 233 since f3348abce.

## Goals / Non-Goals

**Goals:**

- Both trees of the harness get the same substitute for the theme: the page
  object of the seed package plus fluid_styled_content.
- The substitution fails the test when its premise breaks, instead of
  silently comparing two trees that both lack something.

**Non-Goals:**

- Masking any difference the run still reports after the substitution.

## Decisions

### 1. Substitute the theme entry in the test, after the import

After `importSeed()` the test reads the one `sys_template` row of page 1001,
replaces the entry `EXT:bootstrap_package/Configuration/TypoScript` of its
`include_static_file` with `EXT:fluid_styled_content/Configuration/TypoScript`
in the same position, and writes the row back. The read uses the query
builder, ordered by `uid`, and the write is keyed by the uid it read. It
asserts that there is exactly one such row and that the theme entry occurs in
it exactly once, with a message that says the substitution has to be revisited
when the seed changes. Both test methods call it, before
`writeSiteConfigurations()` and before the first frontend request, so no
TypoScript tree is cached before the row is final.

Rejected:

- **FSC in the seed record**, as `main` has it. In the `core-13` instance FSC
  and the theme would both be content rendering templates, the content
  rendering bucket would be included twice, and the instance markup would
  change. It also means regenerating both SQLite templates and both seed
  manifests, for a defect of the harness.
- **Installing `bk2k/bootstrap-package` in the functional suite.** A theme
  dependency of the repository root for one test, and the comparison would
  compare two themes, which the `/` side deliberately avoids.
- **Listing the seed package's static template in
  `contentRenderingTemplates` through the test configuration.** It would bring
  the plugin definitions, but not the core content element definitions FSC
  provides, so the two sides would still differ.
- **Substituting inside `AbstractSeedTestCase::importSeed()`.** The manifest
  tests measure what the import writes, and must keep seeing the record as the
  seed declares it.

### 2. The same position, not first

FSC takes the theme's position: after the seed page object, before the `Full`
aggregates. The generator calls that position load-bearing for the theme, and
the substitute stands in for the theme. On the `/` side the set order is FSC,
then the page object. By reading, the order between those two cannot matter:
the page object sets only `page` and `config`, FSC sets only `tt_content`,
`lib` and `styles` and does not redefine `styles.content.get`, which the page
object copies from the core default. Should the run show an order effect,
moving FSC first, as on `main`, is the fallback, and design.md is updated with
the evidence.

### 3. Refresh the recorded durations in the same pull request

With `0.0` recorded, the scheduler of `-j` puts a class of some 2 minutes
wherever it fits as weightless, and one CI chunk carries it on top of a full
share. The four files are refreshed with `recordFunctionalTestTimes` from the
JUnit artifacts of the pull request's first green CI run, TYPO3 v13, one PHP
version, as `docs/development/environment.md` describes. Rejected: editing the
four values by hand, which mixes measurements of different runs in one file.

### 4. Repair what rotted while the class was skipped

The two facts found by the first run are fixed in the same commit, because
without them the class cannot run at all. `BASE`, `LEGACY_SEGMENT` and
`PAGE_OFFSET` are declared as `main` declares them, and `PROFILE_DETAIL_PAGE`
gives way to `DETAIL_PAGES`, the three detail pages that answer `404` without
a record, as on `main`. Both
are repairs of the test, not of the seed: every one of those pages answers the
same in both trees.

Rejected: a commit or an issue of its own. The repairs make the unskip
possible, they have no meaning apart from it.

## Risks / Trade-offs

- [The substitution is green but other differences remain, for example the
  content rendering bucket sitting before FSC on the `/` side and after it on
  the legacy side] → one iteration is budgeted. Each remaining difference is
  explained by a run, fixed in the test's substitution if it is a harness
  artefact, and reported as a seed defect otherwise. Nothing is added to
  `normalise()` to make it disappear.
- [A future seed names FSC itself on this branch] → the guard of decision 1
  fails and says why, instead of the test quietly running without a
  substitution.
- [The test adds some 2 minutes to the v13 functional jobs] → that is the cost
  of the check `main` already pays, and decision 3 keeps the chunks balanced.
