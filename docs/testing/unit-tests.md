# Unit tests

Unit tests run against a bootstrapped TYPO3 class loader and nothing else — no
database, no site, no request. Everything the subject needs is passed to it or
stubbed. That makes the suite fast enough to run on every save, and it makes a
failure point at one class instead of at a stack.

There are 113 unit test classes: 95 across the twelve extensions, four in
`packages-dev/dev-site`, eight in `packages-dev/monorepo-shared` and six in
`packages-dev/testing-helper`. Twelve of them are the one-line version
compatibility test every extension carries (see
[below](#the-version-compatibility-test)). Measured with
`find packages/fgtclb/*/Tests/Unit packages-dev/*/Tests/Unit -name '*Test.php' | wc -l`.

## Running them

Dependencies come first, always. `-t` selects configuration, it does **not**
install anything, so a run for a core version whose dependencies are not
installed silently uses the wrong vendor tree:

```bash
Build/Scripts/runTests.sh -t 12 -p 8.1 -s composerUpdate
Build/Scripts/runTests.sh -t 12 -p 8.1 -s unit
```

| Command                                                                                      | What it does                                                            |
|----------------------------------------------------------------------------------------------|-------------------------------------------------------------------------|
| `runTests.sh -s unit`                                                                        | The whole suite, TYPO3 v12 and PHP 8.2 by default.                      |
| `runTests.sh -t 13 -p 8.5 -s unit`                                                           | The same suite on the other end of the support matrix.                  |
| `runTests.sh -s unitRandom`                                                                  | The suite with `--order-by=random` and a fresh seed.                    |
| `runTests.sh -s unitRandom -o 1234`                                                          | The same, with a fixed seed — how a random-order failure is reproduced. |
| `runTests.sh -s unit packages/fgtclb/academic-persons/Tests/Unit`                            | Restricted to one extension.                                            |
| `runTests.sh -s unit packages/fgtclb/academic-persons/Tests/Unit/Domain/Model/EmailTest.php` | Restricted to one file.                                                 |

The trailing path is the usual way to narrow a run, and there is no `-e`
option. Anything else — a `--filter`, a `--group` — goes after a `--`
separator, which every phpunit suite appends to its command:

```bash
Build/Scripts/runTests.sh -s unit -- --filter EmailTest
```

`-o` is parsed in its own `getopts` arm and turns into
`--random-order-seed=<seed>`. It only has an effect together with
`-s unitRandom`; the `unit` suite does not pass `PHPUNIT_RANDOM` on. Prefer it
over `-- --random-order-seed=<number>`, which reaches phpunit as well but says
the same thing twice as long.

`unitRandom` is not decoration. CI runs it right after `unit` in the same job —
the *Execute unit tests in random order* step — because ordering dependencies
between tests are exactly the kind of defect a fixed order hides.

## Discovery

There is no per-extension PHPUnit configuration. Two globs in
[`Build/phpunit/UnitTests.xml:36-50`](../../Build/phpunit/UnitTests.xml#L36-L50)
collect every extension's unit tests, and those of the development packages,
into a single suite:

```xml
<testsuites>
    <testsuite name="Unit tests">
        <!--
            This path either needs an adaption in extensions, or an extension's
            test location path needs to be given to phpunit.
        -->
        <directory>../../packages/*/*/Tests/Unit/</directory>
        <directory>../../packages-dev/*/Tests/Unit/</directory>
    </testsuite>
</testsuites>
```

Three things follow from that.

**A new extension needs no configuration change.** Drop it under
`packages/<vendor>/<dir>/` with a `Tests/Unit/` folder and it is in the suite.

**`packages-dev/` is covered too**, and the second glob is why: the seed
definition of [`packages-dev/dev-site/`](../../packages-dev/dev-site) carries
tests of its own, and a suite that does not collect them reports the seed as
green because it never looked at it.
[`packages-dev/testing-helper/`](../../packages-dev/testing-helper) carries the
tests of the three scripts behind `runTests.sh -j`, which split the functional
suite, check that the chunks ran every listed test, and record the durations
(ACE-692), of `Build/Scripts/composerBranchVersion.sh`, which names the
branch alias key (ACE-784), and of `Build/Scripts/readmeManualLink.php`, which
writes the manual link of the package READMEs (ACE-939). Its traits have no
tests of their own — they are exercised only through the extensions that use
them.
[`packages-dev/monorepo-shared/`](../../packages-dev/monorepo-shared) carries
the check that every `ext_emconf.php` of the repository names its dependencies
by extension key, see [below](#the-ext_emconfphp-dependency-keys), the one
that every upgrade wizard has a title and a description, see
[below](#the-description-of-upgrade-wizards), the one that every translation
names its extension without an underscore, see
[below](#the-extension-name-of-translations), the checks of the manual
links and the branch alias key, see [below](#the-links-of-the-manuals), the
check of the link table of every package README, see
[below](#the-link-table-of-the-readmes), the check of the form
`bin/set-version` writes, see
[below](#the-form-binset-version-writes), and the check that every inline event
handler of a template is allowed by the Content Security Policy of its
extension, see [below](#inline-event-handlers).

**Test classes are autoloaded, not included.** Each extension registers its own
`Tests/` namespace as `autoload-dev`, for example
`FGTCLB\AcademicPersons\Tests\` → `Tests/` in
[`academic-persons/composer.json`](../../packages/fgtclb/academic-persons/composer.json).
A test class in the wrong namespace for its path is not found, and PHPUnit
reports nothing rather than an error.

## Conventions

These are read off the existing classes, not prescribed from outside. All of
them follow the shape below.

**Every test class is `final`, declares `strict_types`, and extends
`TYPO3\TestingFramework\Core\Unit\UnitTestCase`.** There is no local abstract
unit test case, and no unit test extends another test class. The eighteen below
`packages-dev/` and `ClassAliasMapTest` of `academic_base` are the exception to
the base class: they need nothing of the TYPO3 testing framework and extend
PHPUnit's `TestCase` directly.

**The namespace mirrors the path, which mirrors `Classes/`.**
`FGTCLB\AcademicPersons\Tests\Unit\Domain\Model\EmailTest` tests
`FGTCLB\AcademicPersons\Domain\Model\Email`. Finding the test of a class is a
path transformation, never a search.

**Test methods carry `#[Test]`, never a `test` prefix.** There is not a single
`public function test…()` in the repository. Method names are readable
statements about the subject, which is what turns the PHPUnit output into a
specification:

```php
#[Test]
public function getSortingReturnsIntegerZeroForNewModel(): void
{
    $this->assertSame(0, (new Email())->getSorting());
}
```

— [`EmailTest.php:37-41`](../../packages/fgtclb/academic-persons/Tests/Unit/Domain/Model/EmailTest.php#L37-L41)

**Data providers are `public static` and return a `\Generator` with named data
sets.** The name is what a failure report shows, so it describes the case rather
than numbering it:

```php
public static function returnsExpectedTcaArrayDataSet(): \Generator
{
    yield 'custom tab is added after the general tab not moving palettes to wrong tab for all types' => [
        // …
    ];
}
```

— [`TcaManipulatorTest.php:14-16`](../../packages/fgtclb/academic-base/Tests/Unit/TcaManipulatorTest.php#L14-L16)

**Non-obvious tests carry a docblock that says why, not what.** The assertion
already says what. The docblock records the defect the test descends from, so
that a later reader does not "simplify" it away:

```php
/**
 * A registry nobody attached anything to is a real state: only three extensions of
 * this repository ship a `Configuration/CategoryTypes.yaml`, so an installation
 * using `EXT:category_types` alone never fills it. `attach()` also returns early for
 * an empty argument list, which is what the loader passes in that case.
 */
#[Test]
public function freshRegistryReportsNoTypes(): void
```

— [`CategoryTypeRegistryTest.php:31-38`](../../packages/fgtclb/typo3-category-types/Tests/Unit/Registry/CategoryTypeRegistryTest.php#L31-L38)

**Fixtures are built by a private helper on the test class, not by a data
provider full of literals.** `CategoryTypeRegistryTest` has a `categoryType()`
factory with named defaults
([lines 15-29](../../packages/fgtclb/typo3-category-types/Tests/Unit/Registry/CategoryTypeRegistryTest.php#L15-L29)),
so each test names only the property it is about.

**Expected exceptions are pinned by code, not only by class.**
`$this->expectExceptionCode(1683633304209)` next to
`$this->expectException(\InvalidArgumentException::class)` keeps the test from
passing on a different `\InvalidArgumentException` thrown three frames earlier.

**No assertion-free test without a reason.** `beStrictAboutTestsThatDoNotTestAnything`
is relaxed (see [PHPUnit configuration](phpunit-configuration.md)), so PHPUnit
will not catch one for you.

## Core-version-aware unit tests

`runTests.sh` always appends `--exclude-group not-core-${CORE_VERSION}` to the
PHPUnit call, in both the `unit` and the `unitRandom` arm. The group names read
as what they exclude, **not** as the version they run on:

| Group         | Runs on | Meaning                      |
|---------------|---------|------------------------------|
| `not-core-12` | v13     | Excluded from a `-t 12` run. |
| `not-core-13` | v12     | Excluded from a `-t 13` run. |
| *(none)*      | both    | The normal case.             |

That inversion is the thing to get right: `not-core-13` marks a test that runs
on TYPO3 v12 only. On the `main` branch the same two-group scheme is spelled
`not-core-13` / `not-core-14`, so a test moved between branches needs its group
renamed, not copied.

Use them for a single method or for a class that differs only in expectations. A
whole class that only exists for one version belongs in a `Core12/` or `Core13/`
subfolder instead.

**No unit test in this repository currently uses either group.** The only
core-version-gated unit tests are the two inside
`ExtensionCoreVersionCompatTestsTrait`, which build their group names from the
supported-version constants — see
[Testing helper](testing-helper.md#extensioncoreversioncompatteststrait). The
worked examples of both mechanisms are all in the functional suite; see
[Functional tests](functional-tests.md#version-gated-tests).

When a gated unit test does become necessary, note that `backupGlobals="true"`
restores `$GLOBALS` after each test, so a test may `unset($GLOBALS['TCA'][…])`
in its body without cleaning up after itself.

## The version compatibility test

Every extension ships a `Tests/Unit/VersionCompatTest.php` that is nothing but a
`use` statement:

```php
final class VersionCompatTest extends UnitTestCase
{
    use ExtensionCoreVersionCompatTestsTrait;
}
```

— [`academic-persons/Tests/Unit/VersionCompatTest.php`](../../packages/fgtclb/academic-persons/Tests/Unit/VersionCompatTest.php)

The trait asserts that the running TYPO3 major version is one of the two the
branch supports, and — via `not-core-*` groups — that the v12 leg really runs on
12 and the v13 leg really runs on 13. It is the tripwire for "the harness
installed a different core than the run asked for", which is otherwise a
confusing pile of unrelated failures. See
[Testing helper](testing-helper.md#extensioncoreversioncompatteststrait).

`EXT:category_types` names its copy `VersionCompareTest.php`
([`typo3-category-types/Tests/Unit/VersionCompareTest.php`](../../packages/fgtclb/typo3-category-types/Tests/Unit/VersionCompareTest.php));
the content is identical. Every extension also carries the same test in its
functional suite.

## The `ext_emconf.php` dependency keys

[`ExtEmConfDependencyKeysTest`](../../packages-dev/monorepo-shared/Tests/Unit/ExtEmConfDependencyKeysTest.php)
reads every `ext_emconf.php` below `packages/` — the twelve extensions and
their fixture extensions — and asserts that each key of `depends`, `suggests`
and `conflicts` names an extension that exists. It lives in
`packages-dev/monorepo-shared` rather than in an extension because it checks
the repository as a whole, and a split-out extension has nothing to compare
against.

A classic, non-Composer installation resolves an extension's dependencies from
those keys. A key that is no extension key is a dependency on nothing, and
nothing else reports it: Composer installations read `composer.json` instead,
and the testing framework skips such a name silently. `academic_jobs` named
`rte-ckeditor` and `fluid-styled-content` from 2.1.0 on, and nothing noticed
until ACE-731.

What counts as valid is read, not listed:

| Key                                       | Accepted for                                                                            |
|-------------------------------------------|-----------------------------------------------------------------------------------------|
| `typo3`, `php`                            | everything — they are constraints of their own, not extension keys                      |
| the key of every installed TYPO3 package  | everything — core extensions and the packages of this repository alike                  |
| the key of every fixture extension        | fixture extensions only, which may depend on each other                                 |
| an entry of `NOT_INSTALLED` in the test   | `suggests` and `conflicts`, when the extension's `composer.json` names that package too |

The installed keys come from Composer's `InstalledVersions` and each package's
`extra.typo3/cms.extension-key`, so a new core version or a new extension needs
no change. `NOT_INSTALLED` holds the one suggested extension the development
setup does not install — `fgtclb/page-backend-layout`, which nothing here
requires — and a second test fails once it is installed after all.

The keys are deliberately **not** compared with the extension's
`composer.json`. Three extensions name a core extension in one file and not in
the other — `academic_base` depends on `backend` only in `ext_emconf.php`,
`academic_study_plan` on `fluid`, `frontend` and `install` only in
`composer.json`, and `academic_partners` on `install` and its suggestion of
`scheduler` only there — and an equality check would fail today for no
defect. `academic_jobs` carries that stricter
check for itself, `academic-jobs/Tests/Unit/ExtEmConfDependenciesTest.php`.

## The description of upgrade wizards

[`UpgradeWizardDescriptionTest`](../../packages-dev/monorepo-shared/Tests/Unit/UpgradeWizardDescriptionTest.php)
creates every class below `packages/fgtclb/*/Classes/Upgrades/` that carries
the `#[UpgradeWizard]` attribute, without its constructor, and fails on an
empty title or an empty description. The upgrade module shows both, and the
description is what an administrator reads before a wizard writes to the
database. Eight of the fourteen wizards had an empty one until ACE-847.

Whether a wizard reports itself as necessary only when there is something to
migrate is not a static property, and this check cannot see it. A new wizard is
expected to cover it with a functional test of its own, next to the one for
what it writes. Not every existing wizard has one yet.

## The extension name of translations

[`TranslationExtensionNameTest`](../../packages-dev/monorepo-shared/Tests/Unit/TranslationExtensionNameTest.php)
reads every Fluid template below `packages/fgtclb/*/Resources/Private/` and
fails on an `f:translate` that names no extension, and on an `extensionName`
with an underscore, in either notation and inside the argument of another view
helper as well. It also reads every class below `packages/fgtclb/*/Classes/`
and fails on a string literal with an underscore and neither dot nor colon -
an extension key, not a label key - handed to a `translate()` call. A name in
an argument list that is built first and then handed over is not seen. TYPO3 v12
and v13 read `_LOCAL_LANG` overrides for an underscored name from a path no
site uses (ACE-740), see [Label overrides](../architecture/label-overrides.md).

The functional label tests of each extension prove every kind of call against
both core versions, but only as the first translation of its file in a
request: the core keeps the overrides one translation has read for the ones
after it. This check holds the templates, and the names handed to
`translate()` directly in PHP.

## The links of the manuals

[`DocumentationGuidesTest`](../../packages-dev/monorepo-shared/Tests/Unit/DocumentationGuidesTest.php)
reads the `Documentation/guides.xml` of every package below `packages/fgtclb/`
and asserts that the links docs.typo3.org builds from it lead to the package
itself, and that the manual declares the shortcode other manuals link to it by:

| Attribute                  | Has to be                                                               |
|----------------------------|-------------------------------------------------------------------------|
| `edit-on-github`           | `fgtclb/academic-extensions`, the split repositories are read-only      |
| `edit-on-github-branch`    | the branch of the only `extra.branch-alias` of the root `composer.json` |
| `edit-on-github-directory` | `packages/fgtclb/<package directory>/Documentation`                     |
| `project-repository`       | `https://github.com/fgtclb/<package directory>`, the split repository   |
| `project-home`             | `https://extensions.typo3.org/extension/<extension key>/`               |
| `interlink-shortcode`      | the composer `name` of the package, for permalinks and interlinks       |

A third test fails on a `guides.xml` outside a `Documentation/` directory. The
renderer reads `Documentation/guides.xml` only (`--config=Documentation`), and
`bin/set-version` does not update another one.

The files were copied from one another, and nothing else notices a copy that
kept the wrong value: the render succeeds, and a link only fails when somebody
follows it. The manuals of `category_types` and `academic_study_plan` sent
every "Edit on GitHub" link into the manual of `academic_base` until ACE-773.

The interlink shortcode is checked for a different reason. The renderer logs a
warning for a manual without one since its release of October 2026, and the
documentation job renders with `--fail-on-log`, so a manual without it fails CI
on every branch at once. The shortcode is the composer name, which is what the
renderer suggests and what other manuals use in `:ref:` links to it. For
`category_types` that is `fgtclb/category-types`, not the directory name.

The edit branch is read from the branch alias because the branch name is not
in the files of a checkout, and in a git worktree the git directory is outside
the directory the test container mounts. The key of the alias is the version
name composer gives the branch, `2.x-dev` here and `dev-main` on `main`, and the
test maps it back to the bare branch name, `2` on this branch. A newly cut
version branch changes both, see [Releasing](../workflow/releasing.md).

## The link table of the READMEs

[`ReadmeLinkTableTest`](../../packages-dev/monorepo-shared/Tests/Unit/ReadmeLinkTableTest.php)
reads the `README.md` of every package below `packages/fgtclb/` and asserts the
link table below its title in the form ACE-934 gave it: one blank line after
the title, an empty first header cell and `URL` as the second, and these rows
in this order:

| Row                | Has to be                                                             |
|--------------------|-----------------------------------------------------------------------|
| `**Repository:**`  | `https://github.com/fgtclb/<package directory>`, the split repository |
| `**Read online:**` | `https://docs.typo3.org/p/<composer name>/<manual version>/en-us/`    |
| `**TER:**`         | `https://extensions.typo3.org/extension/<extension key>/`             |

A package without the table fails, and so does a table with another header,
another row or another order. The padding is left to `lintMarkdown`.

The manual version is the major and minor version of the package's `VERSION`
on a version branch, `2.4` here, and `main` on the branch `main`, because
docs.typo3.org renders the manual of a version line per release tag. The
branch is the one `DocumentationGuidesTest` reads from the root branch alias.
The README a release ships to the TER therefore links the manual of that
release. The link answers 404 until the release is tagged, `2.4` until 2.4.0.
`bin/set-version` writes the link with `Build/Scripts/readmeManualLink.php`
from the same branch and version, so a minor version bump on this branch and
a branch cut by `bin/cut-branch` keep the test green, see
[Releasing](../workflow/releasing.md). A link changed by hand fails it. A data
set per branch form covers the mapping, so the form of `main` is tested here
too.

`-` stands in the "Read online" row only for a package of the list
`MANUALS_NOT_RENDERED` in the test, which names the issue that makes its manual
render. A listed package that links a manual fails, so the change that links it
also removes its entry, and an entry that names no package directory fails as
well. The list is empty since ACE-937, which got the manual of `academic-base`
rendered. A new package goes on it until docs.typo3.org has rendered its
manual for the first time, which needs the docs.typo3.org webhook in its split
repository and an approval by the documentation team, see
[the split repositories](../development/monorepo-layout.md#the-extensions-and-their-split-repositories).

The tables were written by hand and copied from one README to the next. Until
ACE-934 five READMEs linked a manual path that answered 404 and four had no
manual link at all, and nothing reported it. All of them linked the manual of
`main` until ACE-939.

## The branch alias key

[`BranchAliasKeyTest`](../../packages-dev/monorepo-shared/Tests/Unit/BranchAliasKeyTest.php)
asserts that the root `composer.json` and every package below
`packages/fgtclb/` carry the same `extra.branch-alias` with exactly one entry,
that its key is what `Build/Scripts/composerBranchVersion.sh` prints for the
branch `DocumentationGuidesTest` reads from the root, and that a numeric key
aliases a minor version inside it (`2.x-dev` to `2.4.x-dev`).

Composer applies an alias only when its key is the version name it gives the
branch, and skips any other key without a word. `composer validate --strict`
accepts one as well. This branch carried `dev-2` from its cut until ACE-784, so
`2.4.x-dev` did not exist and no split package requiring a sibling with
`~2.4.0@dev` installed from it. `bin/set-version` writes the key through the
same script, see [Releasing](../workflow/releasing.md).

## The form bin/set-version writes

[`SetVersionNormalFormTest`](../../packages-dev/monorepo-shared/Tests/Unit/SetVersionNormalFormTest.php)
asserts that the files `bin/set-version` writes are stored in the form its
tools write them, so that a run with the version the branch already carries
changes nothing:

| Check                                                                   | Files                                                               |
|-------------------------------------------------------------------------|---------------------------------------------------------------------|
| no comment                                                              | every `ext_emconf.php` below `packages/fgtclb/`, fixtures included  |
| the academic keys last in `depends` and `suggests`, in package order    | the same                                                            |
| no `"providesPackages": {}`                                             | `packages/fgtclb/*/composer.json`, `packages-dev/*/composer.json`   |
| `require` and `require-dev` in composer's order                         | every manifest of the repository with `config.sort-packages`        |
| the "Read online" link `readmeManualLink.php` writes                    | `packages/fgtclb/*/README.md`                                       |

packwright writes `ext_emconf.php` from its data, so a comment is lost and a
constraint it sets moves to the end of its list, and `composer require` sorts
`require` where `sort-packages` asks for it. Until ACE-787 a run with an
unchanged version rewrote five files here, the two instance manifests among
them. The `providesPackages` check holds nothing on this branch today, no
package manifest declares the key, and is the same test as on `main`. The test
cannot run the tools themselves, `pkw` and `tailor` are not part of the
container, so the pre-release checklist asks for that run, see
[Releasing](../workflow/releasing.md#pre-release-checklist). The README check
runs `Build/Scripts/readmeManualLink.php --check` with the branch of the root
branch alias and the package's `VERSION`, it needs nothing but PHP.

## Inline event handlers

[`InlineEventHandlerTest`](../../packages-dev/monorepo-shared/Tests/Unit/InlineEventHandlerTest.php)
reads every Fluid template below `packages/fgtclb/*/Resources/Private/` and
collects its inline event handlers: an `on…` attribute of a tag or of a
ViewHelper, quoted or not, and an `on…` key of a Fluid array, as
`additionalAttributes` takes it, also as the first argument of an inline call
(ACE-924). It fails for every handler whose text the
`Configuration/ContentSecurityPolicies.php` of the same extension does not
allow, that is an `InlineEventHandlerMutation` of `academic_base` for the
frontend scope with exactly that text. A plain `Mutation` with the same hash
does not count: the listener that takes the directive out on a site with
`'unsafe-inline'` recognises the mutation by its class. A second test fails
for a hash the file allows that no handler of the extension needs any more,
because it widens the policy of every site for nothing.

A nonce does not cover an event handler attribute, so the frontend policy of
TYPO3 blocks a handler that is not allowed once a site enforces the policy.
Nothing fails in a rendering: the select of a filter form stops submitting,
and only the console of the browser reports the violation. See
[Content Security Policy](../architecture/content-security-policy.md).

The text is taken as the browser hashes it: a character reference in an
attribute of a plain HTML tag is decoded, one in an attribute of a ViewHelper
is not, because Fluid encodes the value again when it renders the tag, and the
escaping backslashes of a Fluid string are dropped. A handler built from a
variable cannot be allowed by a hash and is reported as such. The name is
matched in any letter case, as HTML does, so a ViewHelper argument whose name
starts with `on` is reported too. The content of `<f:comment>` is skipped. A
third test runs the collector over template snippets, a tag, a ViewHelper, a
character reference in either, an unquoted attribute, a variable, both array
forms and an inline call, a `data-` attribute and a comment, so a change of the
patterns cannot turn the check into one of nothing.

`academic_persons_edit` is the one exception, listed with the number of its
handlers and its reason in the constant `NOT_ALLOWED` of the test. Its profile
editor on this branch has eight handlers, six of them built from a variable,
and loads CKEditor 4 from a CDN that the default policy blocks as well. The
test skips its handlers as long as there are exactly eight, so a new handler
there fails as one in any other package does, and the entry cannot outlive its
reason. The test
is the same as on `main` apart from that exception.

## See also

- [PHPUnit configuration](phpunit-configuration.md)
- [Functional tests](functional-tests.md)
- [Testing helper](testing-helper.md)
