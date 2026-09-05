# Functional tests

A functional test boots a real TYPO3 instance against a real database, loads a
declared set of extensions, imports records and then exercises the subject
through the same code paths production uses. It is the only suite here that
sees the database, the TCA that TYPO3 actually compiled, dependency injection,
and — for the plugin tests — a rendered frontend page.

It is also by far the larger suite: 228 functional test classes against 84 unit
test classes, and 367 CSV fixtures. Measured with

```bash
find packages/fgtclb/*/Tests/Functional -name '*Test.php' | wc -l
find packages/fgtclb/*/Tests/Unit -name '*Test.php' | wc -l
find packages -path '*Tests*' -name '*.csv' | wc -l
```

| Extension                | Functional | Unit |
|--------------------------|------------|------|
| `academic-base`          | 11         | 11   |
| `academic-bite-jobs`     | 8          | 1    |
| `academic-contact4pages` | 16         | 2    |
| `academic-jobs`          | 19         | 2    |
| `academic-partners`      | 19         | 5    |
| `academic-persons`       | 58         | 19   |
| `academic-persons-edit`  | 37         | 23   |
| `academic-persons-sync`  | 2          | 1    |
| `academic-programs`      | 18         | 3    |
| `academic-projects`      | 19         | 4    |
| `academic-study-plan`    | 9          | 1    |
| `typo3-category-types`   | 12         | 12   |

`packages-dev/dev-site` adds four functional and two unit classes on top; both
suites collect it, see [Unit tests](unit-tests.md#discovery).

## Running them

```bash
# Prepare the vendor tree for the core version first — always.
Build/Scripts/runTests.sh -t 13 -p 8.3 -s composerUpdate

# The whole suite on the default DBMS (SQLite).
Build/Scripts/runTests.sh -t 13 -p 8.3 -s functional

# Restricted to one extension's functional tests.
Build/Scripts/runTests.sh -t 13 -s functional packages/fgtclb/academic-persons/Tests/Functional

# Restricted to one file, on PostgreSQL, with xdebug attached.
Build/Scripts/runTests.sh -x -p 8.3 -s functional -d postgres \
  packages/fgtclb/academic-persons/Tests/Functional/Domain
```

Point the trailing path at `Tests/Functional`, not at the extension root. The
extension root also contains `Tests/Unit`, which the functional configuration
neither expects nor can bootstrap.

As with the unit suite, the trailing path is the only way to narrow a run;
`runTests.sh` has no argument passthrough to PHPUnit.

### DBMS selection

| Option | Values                                   | Default   | Notes                                                                     |
|--------|------------------------------------------|-----------|---------------------------------------------------------------------------|
| `-d`   | `sqlite`, `mariadb`, `mysql`, `postgres` | `sqlite`  | Chooses the DBMS. Anything else aborts the script.                        |
| `-i`   | a version of the selected DBMS           | see below | Rejected for `-d sqlite`.                                                 |
| `-a`   | `mysqli`, `pdo_mysql`                    | `mysqli`  | Only for `-d mysql` and `-d mariadb`; rejected for SQLite and PostgreSQL. |

Defaults and accepted versions are validated in `handleDbmsOptions()` of
[`Build/Scripts/runTests.sh`](../../Build/Scripts/runTests.sh):
MariaDB `10.4`, MySQL `8.0`, PostgreSQL `10`. An invalid combination is refused
before a container starts, with the offending pair echoed.

SQLite needs no container: the script creates
`.Build/Web/typo3temp/var/tests/functional-sqlite-dbs/` and mounts it as tmpfs,
in the `sqlite)` branch of the `functional` arm. The other
three start a database container, wait for its port, and pass the connection
through `typo3Database*` environment variables. That is the whole reason SQLite
is the default — it is the fastest loop, and it needs the least of the host.

## Why the default is not enough

SQLite is the fastest DBMS to run and the most forgiving one to be wrong on.
Four defects from this repository's own history show the failure modes, in all
directions — this is not "SQLite is lax", it is "the four disagree":

**A statement SQLite accepts and the other three reject.** ACE-349
(`90361d630`): `CategoryRepository::findByGroupAndUidList()` handed a plain
array to `ExpressionBuilder::in()`. On TYPO3 v12 that reached the database as
`uid IN ()` —

> which MariaDB, MySQL and PostgreSQL reject with a syntax error while SQLite
> accepts it.

The fix is the rule now recorded in [`AGENTS.md`](../../AGENTS.md#database-queries):
quote the list with `quoteArrayBasedValueListToIntegerList()`, which renders
`NULL` for an empty array.

**An update that three DBMS report as successful and one aborts on.** ACE-356
(`fe79b46bf`): `FlexFormUpgradeWizard::executeUpdate()` built its `WHERE` on the
query builder of the enclosing `SELECT`, so the `UPDATE` referenced a
placeholder that was never bound to it.

> MySQL, MariaDB and SQLite reported success and changed nothing; PostgreSQL
> aborted with `invalid input syntax for integer`.

A test asserting "the wizard ran without error" would have passed on three of
four. The wizard was the only one in the repository without a functional test at
the time; it has one now.

**A schema declaration only MySQL refuses.** ACE-250 (`af10dba34`): a fixture
inserted `pages` rows without the `link` column, which `EXT:academic_partners`
declares as `text NOT NULL`. MySQL cannot apply an effective default to a `TEXT`
column, so the insert raised *"Field 'link' doesn't have a default value"* in
strict mode —

> SQLite tolerated the omission, which is why the functional suite passed
> locally but failed in CI on MySQL 8.0.

ACE-358 (`8b5557037`) later removed the defaults from five such `TEXT` columns
outright.

**A defect that only SQLite has.** ACE-314 (`93b41a701`):
`CategoryRepository::getByDatabaseFields()` returned early when
`Result::rowCount()` reported no rows, and for a `SELECT` that value is driver
dependent — SQLite reports `0` even for a result carrying rows, so every
category filter silently returned nothing *there and only there*. The commit
records the verification from both ends: the rendering test fails on SQLite
without the fix and passes unpatched on MariaDB 10.4 and PostgreSQL 10.

**Practical consequence.** Run the DBMS matrix locally for anything that writes,
changes a schema declaration, or builds a query by hand. A green SQLite run is
evidence that the logic is right, not that the SQL is portable.

CI encodes the same judgement as staging
([`.github/workflows/ci.yml`](../../.github/workflows/ci.yml)): the 16-job DBMS
matrix — MySQL 8.0, MariaDB 10.4, MariaDB 10.6, PostgreSQL 10, each on two core
versions and two PHP versions — only starts once the identical functional suite
passed on SQLite for both core versions and both PHP edges. A defect that is not
DBMS specific is therefore reported by 4 jobs instead of 20.

## The `not-<dbms>` group

`runTests.sh` always appends **two** exclusions to the functional PHPUnit call:

```bash
COMMAND=(.Build/bin/phpunit -c ${PHPUNIT_CONFIG_FILE} --exclude-group not-${DBMS} --exclude-group not-core-${CORE_VERSION} "$@")
```

So `#[Group('not-postgres')]` on a test class or method would exclude it from a
`-d postgres` run, and the same for `not-sqlite`, `not-mysql` and `not-mariadb`.

**No test in this repository uses it.** The mechanism is available and
inherited from the TYPO3 Core harness; nothing here has yet had a reason to
exclude a test from one DBMS. Prefer keeping it that way: a test that cannot run
on one DBMS usually means production code that does not work there either — all
four of the defects above were fixed rather than skipped.

## Declaring what a test loads

Functional tests here extend
`SBUERK\TYPO3\Testing\TestCase\FunctionalTestCase` (from
`sbuerk/typo3-site-based-test-trait`, a thin subclass of the testing framework's
own `FunctionalTestCase` that adds a `constants`/`setup` aware
`setUpFrontendRootPage()`), by way of one abstract test case per extension.

Each extension's abstract test case names the extensions the whole extension's
suite needs:

```php
abstract class AbstractAcademicBiteJobsTestCase extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'typo3/cms-install',
    ];

    protected array $testExtensionsToLoad = [
        'fgtclb/environment-state-manager',
        'fgtclb/academic-base',
        'fgtclb/academic-bite-jobs',
    ];
}
```

— [`AbstractAcademicBiteJobsTestCase.php`](../../packages/fgtclb/academic-bite-jobs/Tests/Functional/AbstractAcademicBiteJobsTestCase.php)

Points worth knowing:

- **Both lists take composer package names**, not extension keys. That works
  because the testing framework resolves them through its
  `ComposerPackageManager`. Extension keys are accepted too — the
  `ExtensionsLoadedTestsTrait` asserts both spellings resolve, see
  [Testing helper](testing-helper.md#extensionsloadedteststrait).
- **`$testExtensionsToLoad` covers this repository's own extensions as well as
  third-party ones.** `fgtclb/academic-base` is a sibling package, not a "test
  extension" in any narrower sense.
- **Dependencies are not resolved for you.** An extension that reads TCA of
  another one has to list it, which is why every list starts with
  `fgtclb/academic-base`.
- **`typo3/cms-install` appears in most lists** because upgrade wizards live in
  `EXT:install`.

A single test class adds what only it needs. Assigning the array again would
drop the inherited entries, so tests append:

```php
$this->addTestExtensionsToLoad('georgringer/numbered-pagination', 'tests/plugin-templates');
$this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
```

— [`AcademicPersonsListPluginTest.php:35-36`](../../packages/fgtclb/academic-persons/Tests/Functional/Plugins/AcademicPersonsListPluginTest.php#L35-L36)

Those two helpers come from `FrontendPluginRenderingTrait`; `academic-persons`
additionally has `addTestExtension()`/`addCoreExtension()` on its own abstract
case
([`AbstractAcademicPersonsTestCase.php:31-47`](../../packages/fgtclb/academic-persons/Tests/Functional/AbstractAcademicPersonsTestCase.php#L31-L47)).
Both do the same thing — merge instead of replace.

Everything appended must be done **before** `parent::setUp()`, because that call
is what builds the instance.

## Worked examples

| Test                                                                                                                                                                                                                                   | What it demonstrates                                                                                     |
|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|----------------------------------------------------------------------------------------------------------|
| [`academic-base/Tests/Functional/ExtensionLoadedTest.php`](../../packages/fgtclb/academic-base/Tests/Functional/ExtensionLoadedTest.php)                                                                                               | The smallest possible functional test: a trait plus a list of identifiers.                               |
| [`academic-persons/Tests/Functional/Tca/PluginFlexFormTest.php`](../../packages/fgtclb/academic-persons/Tests/Functional/Tca/PluginFlexFormTest.php)                                                                                   | Compiling a piece of FormEngine data without a backend user or a page tree.                              |
| [`academic-persons/Tests/Functional/Plugins/AcademicPersonsListPluginTest.php`](../../packages/fgtclb/academic-persons/Tests/Functional/Plugins/AcademicPersonsListPluginTest.php)                                                     | Full frontend rendering: site configuration, three languages, TypoScript, a real request.                |
| [`academic-jobs/Tests/Functional/Upgrades/ContactTcaUpgradeWizardTest.php`](../../packages/fgtclb/academic-jobs/Tests/Functional/Upgrades/ContactTcaUpgradeWizardTest.php)                                                             | An upgrade wizard against a table renamed for the test, using a fixture extension for the legacy schema. |
| [`academic-base/Tests/Functional/Tca/TableConfigurationTest.php`](../../packages/fgtclb/academic-base/Tests/Functional/Tca/TableConfigurationTest.php)                                                                                 | Mutating `$GLOBALS['TCA']` safely, via `TcaHelperMethodsTrait`.                                          |
| [`academic-persons-edit/Tests/Functional/Plugins/AcademicPersonsEditProfileEditingAuthorizationTest.php`](../../packages/fgtclb/academic-persons-edit/Tests/Functional/Plugins/AcademicPersonsEditProfileEditingAuthorizationTest.php) | Driving a JSON API through the plugin, and asserting every way a request is refused.                     |

Records come from CSV fixtures next to the test, imported with
`importCSVDataSet()` (used in 132 files) and, where the test writes, asserted
back with `assertCSVDataSet()` (61 call sites). Fixtures of a plugin test live in a
`Fixtures/<TestClassName>/` folder beside it, one file per scenario, which keeps
a fixture from being quietly reused by a test it was not written for.

## Testing a JSON endpoint

`academic-persons-edit` answers fourteen actions as JSON — the thirteen of
`ProfileController::JSON_ACTIONS` plus the multipart `uploadImage` — and they
are tested
through the real plugin rather than by calling the controller: the gate that
refuses a request lives partly in `initializeAction()` and partly in a service,
and only a real request exercises both.

The pattern is a helper on the test class. The one below is composed from
`AcademicPersonsEditProfileEditingTest` and shortened for the page; read the
class for the shipped form:

```php
private function postJson(string $url, array $payload): ResponseInterface
{
    $body = new Stream('php://temp', 'rw');
    $body->write(json_encode($payload, JSON_THROW_ON_ERROR));
    $body->rewind();
    return $this->requestAsFrontendUser(
        (new InternalRequest($url))
            ->withMethod('POST')
            ->withAddedHeader('Content-Type', 'application/json')
            ->withAddedHeader('X-Requested-With', 'XMLHttpRequest')
            ->withBody($body),
    );
}
```

Three things about it are not obvious:

- **The URL comes from the rendered page**, read out of the `data-*-url`
  attribute the template wrote, never assembled by hand. The URL carries the
  page type and the request's own `cHash`, and an assembled one is a different
  request than the browser makes.
- **The `X-Requested-With` header is required.** Every writing endpoint refuses a
  request without it with `400 invalid_request`; a test that omits it is testing
  the refusal, not the endpoint.
- **A refusal is asserted on the status *and* the error code, and on the
  database.** `assertSame(['status' => 403, 'error' => 'profile_not_editable'], …)`
  plus a query showing the record is unchanged. A status alone passes for the
  wrong reason more often than it fails.

The anonymous case is the one that needs care: `requestAsFrontendUser()` adds
the session cookie, so an unauthenticated request is sent through
`requestFrontendPage()` with the same request object instead.

[`AcademicPersonsEditProfileEditingAuthorizationTest`](../../packages/fgtclb/academic-persons-edit/Tests/Functional/Plugins/AcademicPersonsEditProfileEditingAuthorizationTest.php)
is the worked example: one data provider naming an endpoint per family, and one
test per way a request is refused, so a new endpoint family is one line.

## Version-gated tests

Three mechanisms coexist, and they are not interchangeable:

| Mechanism                                 | Granularity     | Use when                                                                |
|-------------------------------------------|-----------------|-------------------------------------------------------------------------|
| `Core13/` / `Core14/` subfolder           | whole class     | The class only makes sense on one core version.                         |
| `#[Group('not-core-13')]` / `not-core-14` | class or method | The test exists on both, but the expectation differs by major version.  |
| `markTestSkipped()` on `Typo3Version`     | method          | The behaviour changed within a major version, at a known patch release. |

The group attributes are the common case;
[`AcademicJobsNewJobFormUploadTest.php:32`](../../packages/fgtclb/academic-jobs/Tests/Functional/Plugins/AcademicJobsNewJobFormUploadTest.php#L32)
carries one at class level,
[`DeprecatedCoreLabelsTest.php:19`](../../packages/fgtclb/academic-persons/Tests/Functional/Tca/DeprecatedCoreLabelsTest.php#L19)
at method level.

The third form covers a case the groups cannot: TYPO3 v14.3.6 shipped the fix
for forge #88886, so Extbase honours a site language `fallbackType: strict` for
untranslated selected records from that patch release on, and v13.4 never gets
the fix. Five tests were therefore split into pairs — the plain name asserting
the corrected behaviour and skipping below 14.3.6, a `…_beforeCoreFix` sibling
asserting the previous behaviour and skipping from 14.3.6 on (ACE-378,
`7fa1ca167`). Both halves state their reason in the skip message and link the
Gerrit changes:

```php
if (version_compare((new Typo3Version())->getVersion(), '14.3.6', '<')) {
    $this->markTestSkipped(
        'Extbase honours "fallbackType: strict" for untranslated selected profiles only since '
        . 'TYPO3 v14.3.6 (core fix …, forge #88886; not backported to v13.4).'
    );
}
```

— [`AcademicPersonsSelectedProfilesPluginTest.php:145-152`](../../packages/fgtclb/academic-persons/Tests/Functional/Plugins/AcademicPersonsSelectedProfilesPluginTest.php#L145-L152)

Never delete the older half while the older core version is supported. Behaviour
that differs is documented by stating both sides, not by asserting the newer one
and skipping the rest.

## See also

- [PHPUnit configuration](phpunit-configuration.md)
- [Unit tests](unit-tests.md)
- [Fixture extensions](fixture-extensions.md)
- [Testing helper](testing-helper.md)
- [Frontend verification for `academic-persons-edit`](academic-persons-edit-frontend-tests.md)
