# Functional tests

A functional test boots a real TYPO3 instance against a real database, loads a
declared set of extensions, imports records and then exercises the subject
through the same code paths production uses. It is the only suite here that
sees the database, the TCA that TYPO3 actually compiled, dependency injection,
and — for the plugin tests — a rendered frontend page.

It is also by far the larger suite: 93 functional test classes against 31 unit
test classes, and 165 CSV fixtures.

| Extension                | Functional | Unit |
|--------------------------|------------|------|
| `academic-base`          | 10         | 3    |
| `academic-bite-jobs`     | 2          | 1    |
| `academic-contact4pages` | 5          | 2    |
| `academic-jobs`          | 8          | 1    |
| `academic-partners`      | 6          | 1    |
| `academic-persons`       | 22         | 6    |
| `academic-persons-edit`  | 10         | 3    |
| `academic-persons-sync`  | 2          | 1    |
| `academic-programs`      | 6          | 1    |
| `academic-projects`      | 8          | 1    |
| `academic-study-plan`    | 3          | 1    |
| `typo3-category-types`   | 11         | 10   |

## Running them

```bash
# Prepare the vendor tree for the core version first — always.
Build/Scripts/runTests.sh -t 12 -p 8.1 -s composerUpdate

# The whole suite on the default DBMS (SQLite).
Build/Scripts/runTests.sh -t 12 -p 8.1 -s functional

# Restricted to one extension's functional tests.
Build/Scripts/runTests.sh -t 12 -s functional packages/fgtclb/academic-persons/Tests/Functional

# Restricted to one file, on PostgreSQL, with xdebug attached.
Build/Scripts/runTests.sh -x -t 13 -p 8.2 -s functional -d postgres \
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

Defaults and accepted versions are validated in `handleDbmsOptions()`,
[`Build/Scripts/runTests.sh:117`](../../Build/Scripts/runTests.sh#L117) onwards:
MariaDB `10.4`, MySQL `8.0`, PostgreSQL `10`. An invalid combination is refused
before a container starts, with the offending pair echoed.

SQLite needs no container: the script creates
`.Build/Web/typo3temp/var/tests/functional-sqlite-dbs/` and mounts it as tmpfs
([`runTests.sh:699-711`](../../Build/Scripts/runTests.sh#L699-L711)). The other
three start a database container, wait for its port, and pass the connection
through `typo3Database*` environment variables. That is the whole reason SQLite
is the default — it is the fastest loop, and it needs the least of the host.

## Why the default is not enough

SQLite is the fastest DBMS to run and the most forgiving one to be wrong on.
Four defects from this repository's own history show the failure modes, in all
directions — this is not "SQLite is lax", it is "the four disagree":

**A statement SQLite accepts and the other three reject.** ACE-349
(`8a53f6659`): `CategoryRepository::findByGroupAndUidList()` handed a plain
array to `ExpressionBuilder::in()`. TYPO3 v13 raises an
`\InvalidArgumentException` (1701857902) for an empty one, but TYPO3 v12 has no
such validation and lets `uid IN ()` reach the database —

> which MariaDB, MySQL and PostgreSQL reject with a syntax error while SQLite
> accepts it.

The fix is the rule now recorded in [`AGENTS.md`](../../AGENTS.md#database-queries):
quote the list with `quoteArrayBasedValueListToIntegerList()`, which renders
`NULL` for an empty array.

**An update that three DBMS report as successful and one aborts on.** ACE-356
(`7127f6adb`): `FlexFormUpgradeWizard::executeUpdate()` built its `WHERE` on the
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
> locally but failed in CI on MySQL 8.0 for the TYPO3 v12 matrix.

ACE-358 (`1dd253043`) later removed the defaults from five such `TEXT` columns
outright. That one is v12 specific in the other direction: TYPO3 v13 expresses
the default in the `DEFAULT ('')` syntax MySQL 8.0.13 introduced, while v12 uses
the Doctrine platform unmodified and drops the default — so only the two
`functional mysql 8.0 (v12, …)` jobs could see it.

**A defect that only SQLite has.** ACE-314 (`2bf842956`):
`CategoryRepository::getByDatabaseFields()` returned early when
`Result::rowCount()` reported no rows, and for a `SELECT` that value is driver
dependent — SQLite reports `0` even for a result carrying rows, so every
category filter silently returned nothing *there and only there*. The commit
records that the same code works on MariaDB and PostgreSQL, which is what makes
it driver specific rather than generally broken, and notes this was the only
`rowCount()` call in the repository.

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

`runTests.sh` always excludes **two** groups from the functional PHPUnit call,
as one comma separated `--exclude-group` argument
([`runTests.sh:665`](../../Build/Scripts/runTests.sh#L665)):

```bash
COMMAND=(.Build/bin/phpunit -c ${PHPUNIT_CONFIG_FILE} --exclude-group not-${DBMS},not-core-${CORE_VERSION} "$@")
```

The `unit` and `unitRandom` suites pass only the `not-core-*` half.

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
drop the inherited entries, so tests merge instead. There is no shared helper
trait for this on this branch; the plugin tests spell the merge out in
`setUp()`:

```php
$this->testExtensionsToLoad = array_unique([
    ...array_values($this->testExtensionsToLoad),
    ...array_values([
        'georgringer/numbered-pagination',
        'tests/plugin-templates',
    ]),
]);
```

— [`AcademicPersonsListPluginTest.php:49-57`](../../packages/fgtclb/academic-persons/Tests/Functional/Plugins/AcademicPersonsListPluginTest.php#L49-L57)

`academic-persons` also offers `addTestExtension()`/`addCoreExtension()` on its
own abstract case
([`AbstractAcademicPersonsTestCase.php:31-47`](../../packages/fgtclb/academic-persons/Tests/Functional/AbstractAcademicPersonsTestCase.php#L31-L47)),
which do the same thing more readably and skip an entry that is already present.
Prefer them where the abstract case provides them.

Everything appended must be done **before** `parent::setUp()`, because that call
is what builds the instance.

## Worked examples

| Test                                                                                                                                                                               | What it demonstrates                                                                                     |
|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|----------------------------------------------------------------------------------------------------------|
| [`academic-base/Tests/Functional/ExtensionLoadedTest.php`](../../packages/fgtclb/academic-base/Tests/Functional/ExtensionLoadedTest.php)                                           | The smallest possible functional test: a trait plus a list of identifiers.                               |
| [`academic-persons/Tests/Functional/Plugins/AcademicPersonsListPluginTest.php`](../../packages/fgtclb/academic-persons/Tests/Functional/Plugins/AcademicPersonsListPluginTest.php) | Full frontend rendering: site configuration, three languages, TypoScript, a real request.                |
| [`academic-base/Tests/Functional/Core12/Environment/StateManagerTest.php`](../../packages/fgtclb/academic-base/Tests/Functional/Core12/Environment/StateManagerTest.php)           | A whole test class that only applies to one core version, in a `Core12/` folder.                         |
| [`academic-jobs/Tests/Functional/Upgrades/ContactTcaUpgradeWizardTest.php`](../../packages/fgtclb/academic-jobs/Tests/Functional/Upgrades/ContactTcaUpgradeWizardTest.php)         | An upgrade wizard against a table renamed for the test, using a fixture extension for the legacy schema. |
| [`academic-base/Tests/Functional/Tca/TableConfigurationTest.php`](../../packages/fgtclb/academic-base/Tests/Functional/Tca/TableConfigurationTest.php)                             | Mutating `$GLOBALS['TCA']` safely, via `TcaHelperMethodsTrait`.                                          |

Records come from CSV fixtures next to the test, imported with
`importCSVDataSet()` (used in 41 files) and, where the test writes, asserted back
with `assertCSVDataSet()` (14 call sites). Fixtures of a plugin test live in a
`Fixtures/<TestClassName>/` folder beside it, one file per scenario, which keeps
a fixture from being quietly reused by a test it was not written for.

## Version-gated tests

Three mechanisms coexist, and they are not interchangeable:

| Mechanism                                 | Granularity     | Use when                                                               |
|-------------------------------------------|-----------------|------------------------------------------------------------------------|
| `Core12/` / `Core13/` subfolder           | whole class     | The class only makes sense on one core version.                        |
| `#[Group('not-core-12')]` / `not-core-13` | class or method | The test exists on both, but the expectation differs by major version. |
| `markTestSkipped()` on `Typo3Version`     | method          | A behaviour difference that is not yet understood well enough to pin.  |

Remember the inversion described in
[Unit tests](unit-tests.md#core-version-aware-unit-tests): `not-core-13` marks a
test that runs on TYPO3 v12 only.

Both of the first two mechanisms are in use in `academic-base`, and in the same
area. The whole-class form is
[`Tests/Functional/Core12/Environment/StateManagerTest.php`](../../packages/fgtclb/academic-base/Tests/Functional/Core12/Environment/StateManagerTest.php)
and its `Core13/` sibling; the method form is
[`EnvironmentBuilderFactoryTest.php:36`](../../packages/fgtclb/academic-base/Tests/Functional/Environment/EnvironmentBuilderFactoryTest.php#L36)
and [`:49`](../../packages/fgtclb/academic-base/Tests/Functional/Environment/EnvironmentBuilderFactoryTest.php#L49),
one method per core version on the same class. Those eight attributes are the
only `not-core-*` groups in the repository outside
`ExtensionCoreVersionCompatTestsTrait`.

The third form is used twice, and it is not an endorsement. Two selected-profile
list assertions differ from TYPO3 v12 on and skip themselves rather than assert
either behaviour:

```php
if ((new Typo3Version())->getMajorVersion() >= 12) {
    $this->markTestSkipped('Different behaviour since TYPO3 v12 - needs investigation in core first if this was intended.');
}
```

— [`AcademicPersonsListPluginTest.php:397`](../../packages/fgtclb/academic-persons/Tests/Functional/Plugins/AcademicPersonsListPluginTest.php#L397)
and [`AcademicPersonsListAndDetailPluginTest.php:521`](../../packages/fgtclb/academic-persons/Tests/Functional/Plugins/AcademicPersonsListAndDetailPluginTest.php#L521)

Both carry a `@todo` to investigate the core-side change and then either fix the
implementation or restate the expectation. Since every core version this branch
supports is v12 or newer, the condition is true on both legs and neither test
ever runs — which is why `failOnSkipped` staying off matters, and why this form
is a placeholder rather than a pattern to copy. A behaviour that genuinely
differs is better documented by stating both sides with the groups above.

## See also

- [PHPUnit configuration](phpunit-configuration.md)
- [Unit tests](unit-tests.md)
- [Fixture extensions](fixture-extensions.md)
- [Testing helper](testing-helper.md)
