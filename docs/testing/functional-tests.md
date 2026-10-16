# Functional tests

A functional test boots a real TYPO3 instance against a real database, loads a
declared set of extensions, imports records and then exercises the subject
through the same code paths production uses. It is the only suite here that
sees the database, the TCA that TYPO3 actually compiled, dependency injection,
and — for the plugin tests — a rendered frontend page.

It is also by far the larger suite. There are about three and a half
functional test classes to every unit test class, and more CSV fixtures than
functional test classes. On 2026-10-16 that was 325 functional and 92 unit test
classes in `packages/fgtclb/`, and 413 CSV fixtures. The numbers move with
every change that adds a test, so measure them rather than trusting the ones
above:

```bash
find packages/fgtclb/*/Tests/Functional -name '*Test.php' | wc -l
find packages/fgtclb/*/Tests/Unit -name '*Test.php' | wc -l
find packages -path '*Tests*' -name '*.csv' | wc -l
```

Per package, functional and unit test classes, the packages of
`packages-dev/` included:

```bash
for d in packages/fgtclb/*/ packages-dev/*/; do
    printf '%-24s %4d %4d\n' "$(basename "$d")" \
        "$(find "$d" -path '*/Tests/Functional/*' -name '*Test.php' | wc -l)" \
        "$(find "$d" -path '*/Tests/Unit/*' -name '*Test.php' | wc -l)"
done
```

`academic-persons` carries the most of both. The packages below
`packages-dev/` have tests of their own as well, and both suites collect them,
see [Unit tests](unit-tests.md#discovery).

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

CI runs the same suite on SQLite and on the 16-job DBMS matrix — MySQL 8.0,
MariaDB 10.4, MariaDB 10.6, PostgreSQL 10, each on two core versions and two PHP
versions — side by side
([`.github/workflows/ci.yml`](../../.github/workflows/ci.yml)). They used to be
staged, the DBMS jobs waiting for SQLite; that was dropped because it put the
SQLite stage on the critical path of every green run (ACE-694).

## The `not-<dbms>` group

`runTests.sh` always excludes **two** groups from the functional PHPUnit call,
as one comma separated `--exclude-group` argument:

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

From `setUp()` of [`AcademicPersonsListPluginTest.php`](../../packages/fgtclb/academic-persons/Tests/Functional/Plugins/AcademicPersonsListPluginTest.php#L42-L58).

`academic-persons` also offers `addTestExtension()`/`addCoreExtension()` on its
own abstract case
([`AbstractAcademicPersonsTestCase.php:31-47`](../../packages/fgtclb/academic-persons/Tests/Functional/AbstractAcademicPersonsTestCase.php#L31-L47)),
which do the same thing more readably and skip an entry that is already present.
Prefer them where the abstract case provides them.

Everything appended must be done **before** `parent::setUp()`, because that call
is what builds the instance.

## Worked examples

| Test                                                                                                                                                                                     | What it demonstrates                                                                                                                                                                                                                                                                                                                                                                           |
|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| [`academic-base/Tests/Functional/ExtensionLoadedTest.php`](../../packages/fgtclb/academic-base/Tests/Functional/ExtensionLoadedTest.php)                                                 | The smallest possible functional test: a trait plus a list of identifiers.                                                                                                                                                                                                                                                                                                                     |
| [`academic-persons/Tests/Functional/Plugins/AcademicPersonsListPluginTest.php`](../../packages/fgtclb/academic-persons/Tests/Functional/Plugins/AcademicPersonsListPluginTest.php)       | Full frontend rendering: site configuration, three languages, TypoScript, a real request.                                                                                                                                                                                                                                                                                                      |
| [`academic-base/Tests/Functional/Core12/Environment/StateManagerTest.php`](../../packages/fgtclb/academic-base/Tests/Functional/Core12/Environment/StateManagerTest.php)                 | A whole test class that only applies to one core version, in a `Core12/` folder.                                                                                                                                                                                                                                                                                                               |
| [`academic-jobs/Tests/Functional/Upgrades/ContactTcaUpgradeWizardTest.php`](../../packages/fgtclb/academic-jobs/Tests/Functional/Upgrades/ContactTcaUpgradeWizardTest.php)               | An upgrade wizard against a table renamed for the test, using a fixture extension for the legacy schema.                                                                                                                                                                                                                                                                                       |
| [`academic-study-plan/Tests/Functional/Database/CreditPointsColumnUpdateTest.php`](../../packages/fgtclb/academic-study-plan/Tests/Functional/Database/CreditPointsColumnUpdateTest.php) | A column change against the column a former release declared: alter the table back, store a row, run the added and changed statements of the compare once, as `extension:setup` does. Doctrine's own table rebuild on SQLite drops the secondary indexes with DBAL 4 (TYPO3 v13), so put them back first; the compare itself keeps them. The altered schema stays in the class's own database. |
| [`academic-base/Tests/Functional/Tca/TableConfigurationTest.php`](../../packages/fgtclb/academic-base/Tests/Functional/Tca/TableConfigurationTest.php)                                   | Mutating `$GLOBALS['TCA']` safely, via `TcaHelperMethodsTrait`.                                                                                                                                                                                                                                                                                                                                |

Records come from CSV fixtures next to the test, imported with
`importCSVDataSet()` and, where the test writes, asserted back with
`assertCSVDataSet()`. Fixtures of a plugin test live in a
`Fixtures/<TestClassName>/` folder beside it, one file per scenario, which keeps
a fixture from being quietly reused by a test it was not written for.

## A form that redirects

A form that stores something in the session, a flash message for the next page
above all, is followed with `followSeeOther()`. It requests the `Location` with
the `fe_typo_user` cookie the answer set, as a browser does, and returns the
page. `frontendSessionCookie()` reads that cookie for a later request of the
same visitor. `postJob()` of the job form tests takes it as its third argument,
so a second submission continues the session of the first one:

```php
$answer = $this->postJob('https://www.acme.com/new-job', 'Research assistant');
$this->assertSame('https://www.acme.com/new-job', $answer->getHeaderLine('Location'));
$page = $this->followSeeOther($answer);
```

[`AcademicJobsNewJobFormRedirectPageTest`](../../packages/fgtclb/academic-jobs/Tests/Functional/Plugins/AcademicJobsNewJobFormRedirectPageTest.php)
is the worked example: it asserts that the confirmation is shown once, on the
redirect and not on a reload in the same session or for another visitor.
[`AcademicJobsNewJobFormTranslatedElementTest`](../../packages/fgtclb/academic-jobs/Tests/Functional/Plugins/AcademicJobsNewJobFormTranslatedElementTest.php)
submits the same form through a translated content element, as the
development seed has it: the German overlay carries a FlexForm of its own, and
on `/de/` that one decides the redirect page, the redirect leads to the German
address of the page and the confirmation is the German one.

## A mail that cannot be sent

The mail configuration and the log writers are part of the configuration of
the test instance. Every frontend request of the testing framework bootstraps
again from it, so a value changed at runtime is gone by the time the plugin
sends: one test class per configuration.

- **A failing transport** is a class of the tests that implements Symfony's
  `TransportInterface` and throws from `send()`, selected by its class name as
  `MAIL.transport`. Core instantiates it with the mail settings as the only
  argument, on TYPO3 v12 and v13. The `null` transport sends nothing and
  reports every mail as sent.
- **An address** that fails needs no transport of its own: an empty recipient
  or sender fails while the mail is built.
- **The log** is read from a file writer configured for the logger of the class
  under test, under `LOG` with the class name as the path. The writer keeps its
  file open across the requests of a class, so `setUp()` truncates the file
  rather than deleting it.
- **The form is posted** with its hidden fields. The testing helper of this
  branch has no helper for a post, so the test sets the encoded body and the
  parsed body of the `InternalRequest` itself. The form throws its redirect
  after a saved job, so the answer is a `303` with an empty body on TYPO3 v12
  and v13 alike (ACE-912). A test that only cares about the mail asserts the
  created record.
- **The messages of the form** are queued in the session of the visitor. The
  test takes the `fe_typo_user` cookie from the answer to the post with
  `frontendSessionCookie()`, or follows the redirect with `followSeeOther()`,
  which sends it, see
  [Testing helper](testing-helper.md#frontendpluginrenderingtrait). Without a
  redirect page the form renders its own queue,
  `academic_jobs.new_job_form.confirmation`, above itself. For a redirect page
  the test renders the queue of the plugin,
  `extbase.flashmessages.<plugin namespace>`, with `f:flashMessages` in a
  `COA_INT` on the page it requests.
- **A post that fails** ends the test with the exception that left the
  plugin. A test that expects an error catches it around the post only and
  rethrows any other exception, because a failed assertion of PHPUnit is a
  `RuntimeException` too.

[`AbstractAcademicJobsFailingNotificationMailTestCase`](../../packages/fgtclb/academic-jobs/Tests/Functional/Plugins/AbstractAcademicJobsFailingNotificationMailTestCase.php)
is the example, and its test classes cover a failing transport, an empty
recipient and sender, a recipient that cannot be parsed, the warning the
visitor sees on the form and on a redirect page, and the redirect.

## A file upload

`ResourceStorage::assureFileUploadPermissions()` refuses every file for which
`is_uploaded_file()` is false, on TYPO3 v12 and v13. A file that was never
posted to a web server never passes that check, so a functional test cannot
store an upload through the storage as it is.

- **The storage is replaced** for the test class with an XCLASS that skips
  only the `is_uploaded_file()` step for the files below one directory of the
  test instance. The upload size limit and `assureFileAddPermissions()`, which
  checks the file extension, the user and the target folder, still run for
  them, and every other file is checked as before. The XCLASS is part of the
  configuration of the test instance, under `SYS.Objects`, so every frontend
  request of the class uses it.
- **`makeInstance()` remembers** the class it resolved for the whole process.
  The test class flushes that with `GeneralUtility::flushInternalRuntimeCaches()`
  before and after `parent::setUp()` and in `tearDown()`, so a class that ran
  before does not hide the XCLASS and the next one does not inherit it.
- **The file is posted** as an `UploadedFile` with `withUploadedFiles()` on the
  `InternalRequest`, next to the parsed body of the form. The storage moves a
  stored upload away, so the test hands in a copy of the fixture.

[`AcademicJobsNewJobFormImageUploadTest`](../../packages/fgtclb/academic-jobs/Tests/Functional/Plugins/AcademicJobsNewJobFormImageUploadTest.php)
is the example, with its storage in
[`Fixtures/Classes/SimulatedUploadResourceStorage.php`](../../packages/fgtclb/academic-jobs/Tests/Functional/Plugins/Fixtures/Classes/SimulatedUploadResourceStorage.php).

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
one method per core version on the same class. They are not the only uses of
the groups. On 2026-10-16, 46 `not-core-*` group attributes in 26 files sat
outside `ExtensionCoreVersionCompatTestsTrait`, the
`ShowHiddenRecordsTranslation` tests of jobs, partners, programs and projects
among them. List them with

```bash
grep -rn "Group('not-core-" packages packages-dev --include='*.php' \
    | grep -v ExtensionCoreVersionCompatTestsTrait
```

The third form is used twice, and it is not an endorsement. Two selected-profile
list assertions differ from TYPO3 v12 on and skip themselves rather than assert
either behaviour:

```php
if ((new Typo3Version())->getMajorVersion() >= 12) {
    $this->markTestSkipped('Different behaviour since TYPO3 v12 - needs investigation in core first if this was intended.');
}
```

From [`AcademicPersonsListPluginTest.php:452`](../../packages/fgtclb/academic-persons/Tests/Functional/Plugins/AcademicPersonsListPluginTest.php#L452)
and [`AcademicPersonsListAndDetailPluginTest.php:521`](../../packages/fgtclb/academic-persons/Tests/Functional/Plugins/AcademicPersonsListAndDetailPluginTest.php#L521).

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
