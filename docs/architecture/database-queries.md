# Database queries

Two rules govern every hand-written query in this repository. Both were learned
from defects that reached a release — ACE-349 and ACE-356 — and both share a
property that makes them expensive: the broken code runs green on the default
test setup and only fails somewhere else, on another database, in production.

On this branch the first rule is more than a precaution. TYPO3 v12 has **no
guard at all** against an empty array reaching `in()`, so the malformed SQL
really is built and really is sent — and SQLite, the default test database,
accepts it.

Neither rule is a style preference. Each one describes a mechanism in the query
builder that is easy to misread, and the shape of code that stays correct once
the mechanism is understood.

## Which query builder this is about

Both rules are about the **decorated TYPO3 query builder**,
`TYPO3\CMS\Core\Database\Query\QueryBuilder`, obtained from the connection pool:

```php
$queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_category');
```

It is declared at `QueryBuilder.php:66` on TYPO3 v13.4.34 and `:55` on v12.4.45,
and describes itself as "a facade to the Doctrine DBAL QueryBuilder that
implements PHP7 type hinting and automatic quoting of table and column names".
Its expression builder is
`TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder`
(`ExpressionBuilder.php:39` on v13.4.34, `:35` on v12.4.45), which extends the
Doctrine one.

It is **not** the Extbase query object. `TYPO3\CMS\Extbase\Persistence\Generic\Query`
also offers an `in()` method (line 503 on v13.4.34, line 512 on v12.4.45), but
that one takes a plain PHP array by design and builds an object-level
constraint, not SQL. Extbase repository code such as
`packages/fgtclb/academic-persons/Classes/Domain/Repository/ProfileRepository.php:198`
(`$query->matching($query->in('uid', $profileUidArray))`) is therefore outside
the scope of both rules below — ten such call sites exist across five
repositories. Check which object is in the variable before applying either rule
to a piece of code.

## Where the version claims below come from

Line numbers and source quotations for **TYPO3 v13.4.34** were read from
`.Build/vendor/`, which currently holds a v13 tree with `doctrine/dbal` 4.4.4.
That tree was installed for the `main` branch, so it is not this branch's
dependency set; the `typo3/cms-core` and `doctrine/dbal` sources in it are
nevertheless the released v13.4.34 and DBAL 4.4.4 sources, and v13.4 is
supported here.

There is **no TYPO3 v12 vendor tree in this checkout**. Everything stated about
**v12.4.45** was read out of the `typo3/cms-core` v12.4.45 and `doctrine/dbal`
3.x dist archives in `.cache/composer/files/` — shipped release sources, but
not an installed tree. `core-12/composer.lock` pins exactly `typo3/cms-core`
v12.4.45 with `doctrine/dbal` 3.10.6.

The per-DBMS behaviour of a literal `IN ()` is the one claim below that could
not be re-checked from this checkout at all; it is taken from the ACE-349
analysis.

## Rule 1 — never hand a raw array to `in()` or `notIn()`

### The correct form

Quote the list with the query builder helper meant for it, and pass the
resulting string:

```php
$queryBuilder->expr()->in('uid', $queryBuilder->quoteArrayBasedValueListToIntegerList($uids));
$queryBuilder->expr()->in('CType', $queryBuilder->quoteArrayBasedValueListToStringList($types));
```

Use the integer variant for uid-like lists and the string variant for
identifier lists such as `CType` values or category type keys.

### Why this works — verified on both core versions

Both helpers live on the query builder itself, exist on both supported versions,
and start with the same guard:

| Method                                    | v12.4.45                | v13.4.34                |
|-------------------------------------------|-------------------------|-------------------------|
| `quoteArrayBasedValueListToIntegerList()` | `QueryBuilder.php:1196` | `QueryBuilder.php:1145` |
| `quoteArrayBasedValueListToStringList()`  | `QueryBuilder.php:1230` | `QueryBuilder.php:1176` |

The integer variant reads (v13.4.34, `QueryBuilder.php:1145-1158`):

```php
public function quoteArrayBasedValueListToIntegerList(array $values): string
{
    if (empty($values)) {
        return 'NULL';
    }
    // Ensure values are all integer
    $values = GeneralUtility::intExplode(',', implode(',', $values));
    // Ensure all values are quoted as int for used dbms
    $connection = $this;
    array_walk($values, static function (mixed &$value) use ($connection): void {
        $value = $connection->quote((string)$value);
    });
    return implode(',', $values);
}
```

The v12.4.45 body is the same apart from the `quote()` call, which still passes
`Connection::PARAM_INT` there. The docblock is word-for-word identical on both
versions.

Two things matter here. The empty array returns the **string** `NULL`, so the
condition renders as `field IN (NULL)` — syntactically valid on every supported
DBMS and matching no row, because a comparison against `NULL` is never true.
And the non-empty case quotes every element through the connection, which is
what makes it safe to inline the values into the SQL instead of binding them.

The core docblock states the intent explicitly: the value list is meant "to be
used as direct value list for database 'in(...)' or 'notIn(...)' expressions.
Empty array will return 'NULL' as string to avoid database query failure, as
'IN()' is invalid, but 'IN(NULL)' is fine." It also carries a caveat worth
knowing: the returned string cannot be used in a prepared statement that is
re-bound with different values for a subsequent execution.

### What happens without the helper

Passing the array straight through behaves differently on the two core versions
this branch supports, which is exactly why the defect survived review:

| Core version | Empty array passed to `in()` / `notIn()`            | Where it surfaces                    |
|--------------|-----------------------------------------------------|--------------------------------------|
| v13          | `\InvalidArgumentException` before any SQL is built | Everywhere, immediately              |
| v12          | No guard; `field IN ()` reaches the database        | MariaDB, MySQL, PostgreSQL reject it |
| v12          | No guard; `field IN ()` reaches the database        | SQLite accepts it and returns no row |

The v13 guard (`ExpressionBuilder.php:227-234` on v13.4.34):

```php
public function in(string $fieldName, $value): string
{
    if ($value === []) {
        throw new \InvalidArgumentException(
            'ExpressionBuilder::in() can not be used with an empty array value.',
            1701857902
        );
    }
```

`notIn()` carries the same guard with code `1701857904`
(`ExpressionBuilder.php:255-262`); the empty-string variants are `1701857903`
and `1701857905`.

The v12 method has no guard whatsoever — the whole body is three lines
(`ExpressionBuilder.php:262-269` on v12.4.45):

```php
public function in(string $fieldName, $value): string
{
    return $this->comparison(
        $this->connection->quoteIdentifier($fieldName),
        'IN',
        '(' . implode(', ', (array)$value) . ')'
    );
}
```

`implode()` over an empty array produces the empty string, so the expression is
literally `field IN ()`. `notIn()` (`:278-285`) is identical bar the operator.
No exception is raised, nothing is logged, and the statement is handed to the
driver.

This is the live case on this branch, and it is the reason the rule is worth a
page. **The default `-d sqlite` functional run does not reveal it**: SQLite
parses `IN ()` and returns no rows, which for a positive selection is the
intended result, so the test passes. The same code on the same core version
throws a driver error on MariaDB, MySQL and PostgreSQL — that is, in every
production installation.

On v13 the failure is louder but no less real: exception 1701857902 the moment
a list happens to be empty, which for request-derived data means in production
rather than in the test.

### Named parameters are equally safe

Binding the list instead of inlining it has the same empty-array behaviour on
both versions:

```php
$queryBuilder->expr()->in(
    't3ver_wsid',
    $queryBuilder->createNamedParameter([0, $workspaceUid], Connection::PARAM_INT_ARRAY)
)
```

That is the form used in
`packages/fgtclb/typo3-category-types/Classes/Domain/Repository/CategoryRepository.php:317-320`,
and in `academic-persons/Classes/Upgrades/ListTypeToCTypeUpgradeWizard.php:129-132`
with `PARAM_STR_ARRAY`. Doctrine expands an array parameter when the statement
is prepared, and an empty one becomes the literal `NULL` — in DBAL 4.4.4 at
`.Build/vendor/doctrine/dbal/src/ExpandArrayParameters.php:99-103`, and in DBAL
3.x at the same file's lines 111–115:

```php
if (count($value) === 0) {
    $this->convertedSQL[] = 'NULL';

    return;
}
```

The two constants resolve differently, which is worth knowing but changes
nothing at the call site: on v13.4.34 `Connection::PARAM_INT_ARRAY` and
`PARAM_STR_ARRAY` are declared on TYPO3's own `Connection`
(`.Build/vendor/typo3/cms-core/Classes/Database/Connection.php:75` and `:80`).
On v12.4.45 TYPO3's `Connection` declares no `*_ARRAY` constants at all — the
names resolve through inheritance to `\Doctrine\DBAL\Connection`, where DBAL 3
marks them `@deprecated` in favour of `ArrayParameterType`. Both spellings work
on both versions; keep using the TYPO3 ones for consistency with the rest of
the code base.

Pick between the two forms on the merits: a named parameter is the general
default, while the quoting helpers are the way out when a list can grow large
enough to approach the driver's placeholder limit, or when the statement is a
prepared statement that would otherwise need re-binding.

### Do not guard on the caller side

The tempting repair after hitting exception 1701857902 is a guard at the call
site:

```php
// Wrong: works around the API instead of using it.
if ($uids !== []) {
    $categories = $categoryRepository->findByGroupAndUidList($group, $uids);
}
```

This is worse than it looks. It pushes a database-layer detail into every
caller, it has to be repeated at each one, and it forces every caller to invent
an answer for "what does no selection mean" — which is a domain question, not a
query question. In ACE-349 the answer was already settled: the uid list is a
**positive** selection, so an empty list means "no categories", deliberately
not "no restriction". Expressing that in the query with `IN (NULL)` gives every
caller the same answer for free, and the changelog entry for that fix
explicitly invites existing caller-side guards to be removed.

It is also the wrong repair on this branch specifically: a caller-side guard
only silences the v13 exception. On v12 there was never an exception to
silence, so a guard added in response to one leaves the v12 code path exactly
as broken as it was.

There is one legitimate caller-side condition, and it is a different thing: a
check that decides whether a constraint applies **at all**. In
`packages/fgtclb/academic-persons/Classes/Provider/FrontendUserProvider.php:84-99`
an empty include or exclude list means "do not narrow the query", so the whole
`andWhere()` is skipped — and the list is still quoted with the helper when it
is added. The rule is about how a list becomes SQL, not about whether a
constraint is added.

### Literal lists are not affected

A hard-coded list can never be empty, so it does not need the helper:

```php
$queryBuilder->expr()->in('sys_category.sys_language_uid', [0, -1]),
```

That form appears five times in
`packages/fgtclb/typo3-category-types/Classes/Domain/Repository/CategoryRepository.php`
(lines 69, 111, 155, 207 and 250) and three times in
`packages/fgtclb/academic-study-plan/Classes/Service/StudyPlanService.php`
(lines 218, 235 and 254), and is correct as written. The rule targets lists
whose length comes from outside the function — request data, a repository
result, a configuration value.

### Call sites to copy from

Eight classes under `packages/fgtclb/*/Classes/` use the quoting helpers, 16
call sites in total. A representative selection, all paths relative to
`packages/fgtclb/`:

| File                                                                       | Line    | Form                                              |
|----------------------------------------------------------------------------|---------|---------------------------------------------------|
| `typo3-category-types/Classes/Domain/Repository/CategoryRepository.php`    | 156-159 | `...ToIntegerList()` with `in()`, the ACE-349 fix |
| `typo3-category-types/Classes/Domain/Repository/CategoryRepository.php`    | 317-320 | `createNamedParameter()` array parameter          |
| `academic-persons/Classes/Provider/FrontendUserProvider.php`               | 86-89   | `...ToIntegerList()` with `notIn()`               |
| `academic-contact4pages/Classes/Backend/FormEngine/AddressRecordItems.php` | 158-161 | `...ToIntegerList()` on a literal list            |
| `academic-projects/Classes/Upgrades/FlexFormUpgradeWizard.php`             | 51-54   | `...ToStringList()` with `in()`                   |
| `academic-jobs/Classes/Upgrades/PluginUpgradeWizard.php`                   | 62-65   | `...ToStringList()` with `in()`                   |

The fourth row quotes the constant `[-1, 0]`, which the previous section marks
as not strictly needing it. That is harmless and arguably preferable: it keeps
one shape for every value list in the class, so the reader never has to decide
whether a given list can be empty.

The canonical one is the ACE-349 fix itself
(`typo3-category-types/Classes/Domain/Repository/CategoryRepository.php:156-159`):

```php
$queryBuilder->expr()->in(
    'uid',
    $queryBuilder->quoteArrayBasedValueListToIntegerList($idList),
),
```

## Rule 2 — build the constraint on the builder that executes it

### The mechanism

`createNamedParameter()` does not return a value, it returns a **placeholder
name** and registers the value on the builder it was called on
(`QueryBuilder.php:991-997` on v13.4.34, `:1046-1049` on v12.4.45, delegating to
the concrete Doctrine builder in both). The placeholder is therefore bound to
one specific query builder instance.

A `WHERE` fragment assembled on builder A and handed to builder B carries a
placeholder that was never bound on B. Worse, the counters are per builder, so
the name A produced can collide with a name B produced independently — and
`set()` produces one silently: it wraps its value in `createNamedParameter()`
by default (`QueryBuilder.php:719-727` on v13.4.34, `:751-759` on v12.4.45):

```php
public function set(string $key, $value, bool $createNamedParameter = true, ParameterType|ArrayParameterType $type = Connection::PARAM_STR): QueryBuilder
{
    $concreteQueryBuilder = $this->concreteQueryBuilder;
    $concreteQueryBuilder->set(
        $this->quoteIdentifier($key),
        $createNamedParameter ? $this->createNamedParameter($value, $type) : $value
    );
    return $this;
}
```

The v12 signature types `$type` as `int` rather than
`ParameterType|ArrayParameterType`; the parameter-claiming behaviour is
identical.

So an `UPDATE` that calls `set()` once has already claimed `:dcValue1` on its
own builder before the `WHERE` is attached.

### The defect (ACE-356)

`FGTCLB\AcademicProjects\Upgrades\FlexFormUpgradeWizard` built the constraint of
its `UPDATE` on the query builder of the enclosing `SELECT`:

```php
// Wrong: the expression and its parameter belong to the select builder.
$updateQueryBuilder->update('tt_content')
    ->set('pi_flexform', $this->array2xml($flexFormData))
    ->where(
        $queryBuilder->expr()->in(
            'uid',
            $queryBuilder->createNamedParameter($record['uid'], Connection::PARAM_INT)
        )
    )
    ->executeStatement();
```

Both `set()` and the misplaced `createNamedParameter()` produced `:dcValue1`,
each on its own builder. The first record therefore executed:

```sql
UPDATE tt_content SET pi_flexform = :dcValue1 WHERE uid IN (:dcValue1)
```

comparing the FlexForm XML against `uid`. Every following record referred to a
placeholder that did not exist on the update builder at all, because the select
builder's counter kept advancing while the fresh update builder's did not.

The wizard reported success and migrated nothing. The correct form keeps
expression, parameter and execution on the same object
(`packages/fgtclb/academic-projects/Classes/Upgrades/FlexFormUpgradeWizard.php:80-91`,
using `eq()` because the value is a single uid):

```php
$updateQueryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_content');
$updateQueryBuilder->update('tt_content')
    ->set('pi_flexform', $this->array2xml($flexFormData))
    ->where(
        // The constraint must be built on the builder that executes it: a named
        // parameter is bound to the query builder that created it.
        $updateQueryBuilder->expr()->eq(
            'uid',
            $updateQueryBuilder->createNamedParameter($record['uid'], Connection::PARAM_INT)
        )
    )
    ->executeStatement();
```

The pattern to internalise: when a loop over a result set builds one statement
per record, the statement gets its **own** builder, and everything that goes
into that statement is created on it.

### PostgreSQL is the database that names the mechanism

The per-DBMS outcome of the broken statement was:

| DBMS       | Outcome                                                                       |
|------------|-------------------------------------------------------------------------------|
| MySQL      | Reported success, changed nothing                                             |
| MariaDB    | Reported success, changed nothing                                             |
| SQLite     | Reported success, changed nothing                                             |
| PostgreSQL | `Doctrine\DBAL\Exception\DriverException`, *invalid input syntax for integer* |

PostgreSQL is strict about types and quotes the offending value in its error
message, so the failure text contains the FlexForm XML that was being compared
against an integer `uid` column. That message points straight at the mechanism.
The three permissive databases coerce the comparison and update zero rows —
which `executeStatement()` does not treat as an error.

**Run a suspected parameter defect on PostgreSQL first.** It is the fastest
route from "this does nothing" to "this is comparing the wrong two things".

## Testing this class of defect

Both rules fail in the direction the default test run cannot see: rule 1 hides
behind SQLite's tolerance of `IN ()` on v12, rule 2 hides behind three
databases silently updating nothing. The functional suite defaults to SQLite
(`Build/Scripts/runTests.sh:398`, `DBMS="sqlite"`) and to core version 12
(line 397), so a green local run proves less than it appears to — and on this
branch the default core version is the one *without* the empty-array guard.

Three habits follow.

**Run anything that writes on PostgreSQL as well.** After the SQLite run:

```bash
Build/Scripts/runTests.sh -t 12 -p 8.1 -s composerUpdate
Build/Scripts/runTests.sh -t 12 -p 8.1 -s functional -d postgres \
    packages/fgtclb/academic-projects/Tests/Functional/Upgrades
```

CI reaches the same coverage eventually — `functional-dbms` in
`.github/workflows/ci.yml:260-278` runs MySQL 8.0, MariaDB 10.4, MariaDB 10.6
and PostgreSQL 10 against four core/PHP combinations — but only after the
SQLite jobs pass, so a defect of this kind is reported one stage late unless it
was reproduced locally first.

**Assert the effect, not the return value.** `executeStatement()` returning
without an exception says nothing about rows changed. Read the record back and
compare it, as
`packages/fgtclb/academic-projects/Tests/Functional/Upgrades/FlexFormUpgradeWizardTest.php`
does.

**Seed more than one record.** The class docblock of that test spells out why
(lines 18–21):

> **More than one record per test on purpose.** The wizard builds one update
> statement per record; a defect in how that statement is parameterised shows up
> differently in the first iteration than in the following ones, so a
> single-record fixture can pass while the wizard is broken (ACE-356).

For rule 1, cover the empty list explicitly. The ACE-349 fix added
`emptyUidListReturnsAnEmptyCollection()` and
`collectionOfAnEmptyUidListStillKnowsTheGroupTypes()` to
`packages/fgtclb/typo3-category-types/Tests/Functional/Domain/Repository/CategoryRepositoryGroupSelectionTest.php`
(lines 158 and 169) — one asserting that the query runs and returns nothing, the
other asserting that the empty result still has the shape a template expects.
`emptyUidListOfAnUnknownGroupIsRejectedAsWell()` (line 192) covers the same list
against a group that does not exist.

## See also

- `AGENTS.md`, section *Database queries* — the condensed form of both rules.
- `packages/fgtclb/typo3-category-types/Documentation/Changelog/2.4/Important-CategoryRepositoryEmptyUidList.rst`
  — the ACE-349 changelog entry, including the per-DBMS analysis for TYPO3 v12.
- `packages/fgtclb/academic-projects/Documentation/Changelog/2.4/Important-FlexFormUpgradeWizardMigratesRecords.rst`
  — the ACE-356 changelog entry, including how to re-run the repaired wizard.
- `.Build/vendor/typo3/cms-core/Classes/Database/Query/QueryBuilder.php` and
  `.../Query/Expression/ExpressionBuilder.php` — the authoritative source for
  both mechanisms; read the installed version, not the documentation of another,
  and remember that a v13 tree cannot answer a question about v12.
- TYPO3 forge issue [#96434](https://forge.typo3.org/issues/96434) — the change
  that introduced the array quoting helpers.
