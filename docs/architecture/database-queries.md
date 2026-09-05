# Database queries

Three rules govern every hand-written query in this repository. All were
learned from defects that reached a release — ACE-349, ACE-356 and
ACE-482/ACE-491 — and all share a property that makes them expensive: the
broken code runs green on the default test setup and only fails somewhere
else, on another database, in production.

None of the rules is a style preference. The first two describe a mechanism in
the query builder that is easy to misread; the third describes a guarantee SQL
never gave, and the shape of code that stays correct once that is understood.

## Which query builder this is about

Rules 1 and 2 are about the **decorated TYPO3 query builder**,
`TYPO3\CMS\Core\Database\Query\QueryBuilder`, obtained from the connection pool:

```php
$queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_category');
```

It is declared in
`typo3/cms-core/Classes/Database/Query/QueryBuilder.php` — line 66 in
`core-13/vendor/`, line 67 in `core-14/vendor/` — and describes itself as "a
facade to the Doctrine DBAL QueryBuilder that implements PHP7 type hinting and
automatic quoting of table and column names" (class docblock). Its expression
builder is `TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder`
(`typo3/cms-core/Classes/Database/Query/Expression/ExpressionBuilder.php:39` on
both), which extends the Doctrine one.

It is **not** the Extbase query object. `TYPO3\CMS\Extbase\Persistence\Generic\Query`
(`typo3/cms-extbase/Classes/Persistence/Generic/Query.php:42` in `core-13/vendor/`,
`:47` in `core-14/vendor/`) also offers an `in()` method (`:503` and `:574`
respectively), but that one takes a plain PHP array
by design and builds an object-level constraint, not SQL. Extbase repository
code such as
`packages/fgtclb/academic-persons/Classes/Domain/Repository/ProfileRepository.php:214`
(`$query->matching($query->in('uid', $profileUidArray))`) is therefore outside
the scope of the first two rules below — rule 3 applies to **both** query
objects. Check which object is in the variable before applying a rule to a
piece of code.

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

### Why this works — verified in the pinned core trees

Verified against the two development instances, which their tracked
`composer.lock` pins: `core-13/vendor/` carries **TYPO3 v13.4.34** and
`core-14/vendor/` **v14.3.6**, both with `doctrine/dbal` 4.4.4. The line
numbers below are keyed to those two trees rather than to `.Build/vendor/`,
which carries whichever version the last `composerUpdate -t 13|14` installed.

```bash
grep -n "protected const VERSION" \
  core-13/vendor/typo3/cms-core/Classes/Information/Typo3Version.php \
  core-14/vendor/typo3/cms-core/Classes/Information/Typo3Version.php
```

Both helpers live on the query builder itself and start with the same guard:

| Method                                    | v13.4.34 (`core-13/vendor/`) | v14.3.6 (`core-14/vendor/`) |
|-------------------------------------------|------------------------------|-----------------------------|
| `quoteArrayBasedValueListToIntegerList()` | `QueryBuilder.php:1145`      | `QueryBuilder.php:1148`     |
| `quoteArrayBasedValueListToStringList()`  | `QueryBuilder.php:1176`      | `QueryBuilder.php:1179`     |

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

Two things matter here. The empty array returns the **string** `NULL`, so the
condition renders as `field IN (NULL)` — syntactically valid on every supported
DBMS and matching no row, because a comparison against `NULL` is never true.
And the non-empty case quotes every element through the connection, which is
what makes it safe to inline the values into the SQL instead of binding them.

The core docblock states the intent explicitly
(`QueryBuilder.php:1129-1144`): the value list is meant "to be used as direct
value list for database 'in(...)' or 'notIn(...)' expressions. Empty array will
return 'NULL' as string to avoid database query failure, as 'IN()' is invalid,
but 'IN(NULL)' is fine." It also carries a caveat worth knowing: the returned
string cannot be used in a prepared statement that is re-bound with different
values for a subsequent execution.

### What happens without the helper

Passing the array straight through has three different outcomes depending on
the core version and the database, which is exactly why the defect survived
review:

| Core version | Empty array passed to `in()` / `notIn()`            | Where it surfaces                    |
|--------------|-----------------------------------------------------|--------------------------------------|
| v13, v14     | `\InvalidArgumentException` before any SQL is built | Everywhere, immediately              |
| v12          | No guard; `field IN ()` reaches the database        | MariaDB, MySQL, PostgreSQL reject it |
| v12          | No guard; `field IN ()` reaches the database        | SQLite accepts it and returns no row |

The v13 guard is verifiable from this checkout
(`ExpressionBuilder.php:227-234` on v13.4.34, `:223-230` on v14.3.6):

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
(`ExpressionBuilder.php:255-262` on v13.4.34, `:251-258` on v14.3.6); the
empty-string variants are `1701857903`
and `1701857905`.

**What could not be verified from this checkout:** no TYPO3 v12 vendor tree
exists here — `main` supports v13 and v14 only — so the statement that v12 has
no guard at all, and the per-DBMS behaviour of a literal `IN ()`, are taken
from the ACE-349 analysis recorded in
`packages/fgtclb/typo3-category-types/Documentation/Changelog/3.0/Important-CategoryRepositoryEmptyUidList.rst`
and not re-checked against a v12 installation. The same applies to the claim
that both helpers exist since TYPO3 v11.5: only v13.4.34 and v14.3.6 were
inspected. The v12 case still matters for branch `2`, which supports v12 and
v13.

The practical consequence is the same on every branch: **the default
`-d sqlite` functional run does not reveal this defect on v12**, and on v13/v14
it turns a silently empty result into a hard exception the moment a list
happens to be empty in production.

### Named parameters are equally safe

Binding the list instead of inlining it has the same empty-array behaviour:

```php
$queryBuilder->expr()->in(
    't3ver_wsid',
    $queryBuilder->createNamedParameter([0, $workspaceUid], Connection::PARAM_INT_ARRAY)
)
```

That is the form used in
`packages/fgtclb/typo3-category-types/Classes/Domain/Repository/CategoryRepository.php:337-340`.
Doctrine expands an array parameter when the statement is prepared, and an
empty one becomes the literal `NULL`
(`.Build/vendor/doctrine/dbal/src/ExpandArrayParameters.php:99-103`):

```php
if (count($value) === 0) {
    $this->convertedSQL[] = 'NULL';

    return;
}
```

`Connection::PARAM_INT_ARRAY` and `Connection::PARAM_STR_ARRAY` are aliases for
Doctrine's `ArrayParameterType` cases
(`.Build/vendor/typo3/cms-core/Classes/Database/Connection.php:75` and `:80`).

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
(lines 69, 117, 165, 221 and 270, all of them the same
`sys_language_uid IN (0, -1)`) and three times in
`packages/fgtclb/academic-study-plan/Classes/Service/StudyPlanService.php`
(line 267 for `[0, -1]`, lines 284 and 303 for `[0, 1]`), and is correct as
written. The rule targets lists whose length comes from outside the function —
request data, a repository result, a configuration value.

Re-derive both:

```bash
grep -rn "expr()->\(not\)\?[iI]n(.*\[[^]]*\])" --include='*.php' packages/fgtclb/*/Classes/
```

### Call sites to copy from

Nine classes under `packages/fgtclb/*/Classes/` use the quoting helpers, 17
call sites in total:

```bash
grep -rln "quoteArrayBasedValueListTo" packages/fgtclb/*/Classes/ | wc -l
grep -rn  "quoteArrayBasedValueListTo" packages/fgtclb/*/Classes/ | wc -l
```

A representative selection, all paths relative to `packages/fgtclb/`:

| File                                                                                   | Line    | Form                                              |
|----------------------------------------------------------------------------------------|---------|---------------------------------------------------|
| `typo3-category-types/Classes/Domain/Repository/CategoryRepository.php`                | 166-169 | `...ToIntegerList()` with `in()`, the ACE-349 fix |
| `typo3-category-types/Classes/Domain/Repository/CategoryRepository.php`                | 337-340 | `createNamedParameter()` array parameter          |
| `academic-persons/Classes/Provider/FrontendUserProvider.php`                           | 86-89   | `...ToIntegerList()` with `notIn()`               |
| `academic-contact4pages/Classes/Backend/FormEngine/AddressRecordItems.php`             | 158-161 | `...ToIntegerList()` on a literal list            |
| `academic-projects/Classes/Upgrades/FlexFormUpgradeWizard.php`                         | 51-54   | `...ToStringList()` with `in()`                   |
| `academic-jobs/Classes/Upgrades/PluginUpgradeWizard.php`                               | 69-72   | `...ToStringList()` with `in()`                   |
| `academic-persons-edit/Classes/Upgrades/RepairLocalizedProfileImagesUpgradeWizard.php` | 326-329 | `...ToIntegerList()` with `in()`                  |

The fourth row quotes the constant `[-1, 0]`, which the previous section marks
as not strictly needing it. That is harmless and arguably preferable: it keeps
one shape for every value list in the class, so the reader never has to decide
whether a given list can be empty.

The canonical one is the ACE-349 fix itself
(`typo3-category-types/Classes/Domain/Repository/CategoryRepository.php:166-169`):

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
(`QueryBuilder.php:991-998`, delegating to the concrete Doctrine builder). The
placeholder is therefore bound to one specific query builder instance.

A `WHERE` fragment assembled on builder A and handed to builder B carries a
placeholder that was never bound on B. Worse, the counters are per builder, so
the name A produced can collide with a name B produced independently — and
`set()` produces one silently: it wraps its value in `createNamedParameter()`
by default (`QueryBuilder.php:719-727`):

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

## Rule 3 — order every result a caller renders or limits

### The rule

A statement without an `ORDER BY` returns rows in an order the database is
free to choose. In practice SQLite, MySQL and MariaDB return uid order for the
queries of this repository — uid is the integer primary key, so a table scan
walks it — which is what lets an unordered query survive review and years of
production. PostgreSQL's planner picks the access path per query, and the
moment an index gives it an alternative, the order changes: making the person
tables workspace aware added an index that reversed the unordered profile
queries (ACE-482), and the partnership teaser rendered a *different partner*
on two renders of the same seed data, caught only as a CI flake (ACE-491).

So: every query whose result reaches a user — a frontend list, backend select
items — or that limits its result (`setLimit(1)` picks *which* row!) carries
an explicit ordering. Unlike rules 1 and 2 this applies to both query objects:
`setOrderings()`/`$defaultOrderings` on the Extbase query,
`orderBy()`/`addOrderBy()` on the decorated query builder.

What to order by, learned from the ACE-482/ACE-491 sweeps:

- **A manually sortable table** (TCA ctrl `sortby`) rendered to users orders
  by `sorting` with `uid` breaking ties — that is the order the editor
  arranged in the backend. Extbase does **not** read `sortby` or
  `default_sortby`, so an Extbase query on such a table stays unordered
  without this. The exception is a table whose `sorting` is scoped per parent
  through an inline relation — the persons contracts, sorted within their
  profile — where a global `ORDER BY sorting` would interleave meaninglessly
  across parents; such a cross-parent list orders by `uid`.
- **A demanded ordering** (a plugin's sort option) gets `uid` appended as a
  tiebreaker — records equal in the demanded ordering must keep a stable
  relative order.
- **Everything else** orders by `uid` ascending. That is the order every
  supported database returned in practice, so no installation sees its lists
  change — the order becomes guaranteed rather than coincidental.

### Testing an ordering

Assert the exact result order against a fixture whose `sorting` values
**contradict** uid order — then the assertion fails on every DBMS, SQLite
included, as soon as the ordering is dropped. A uid-only ordering cannot be
made to fail on SQLite at all (uid is the rowid, so uid order *is* its natural
order): pin the exact order anyway and say so in the test docblock — the
assertion guards the databases where the order was arbitrary before. The
ordering tests of `PartnershipRepositoryFindByPidTest` (academic-partners) and
`CategoryRepositoryOrderingTest` (typo3-category-types) are the references for
the first shape, `ContractRepositoryFindAllTest` (academic-persons) for the
second.

## Testing this class of defect

Rules 1 and 2 fail in the direction the default test run cannot see: rule 1
hides behind SQLite's tolerance of `IN ()` on v12, rule 2 hides behind three
databases silently updating nothing (rule 3's testing has its own section
above). The functional suite defaults to SQLite —
`DBMS="sqlite"` in `runTests.sh` — so a green local run proves less than it
appears to.

Three habits follow.

**Run anything that writes on PostgreSQL as well.** After the SQLite run:

```bash
Build/Scripts/runTests.sh -t 13 -p 8.3 -s composerUpdate
Build/Scripts/runTests.sh -t 13 -p 8.3 -s functional -d postgres \
    packages/fgtclb/academic-projects/Tests/Functional/Upgrades
```

CI reaches the same coverage eventually — the `functional-dbms` job runs MySQL
8.0, MariaDB 10.4, MariaDB 10.6 and PostgreSQL 10 — but only after the SQLite
jobs pass, so a defect of this
kind is reported one stage late unless it was reproduced locally first.

**Assert the effect, not the return value.** `executeStatement()` returning
without an exception says nothing about rows changed. Read the record back and
compare it, as
`packages/fgtclb/academic-projects/Tests/Functional/Upgrades/FlexFormUpgradeWizardTest.php`
does.

**Seed more than one record.** The class docblock of that test spells out why:

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

## See also

- `AGENTS.md`, section *Database queries* — the condensed form of all three
  rules.
- `packages/fgtclb/typo3-category-types/Documentation/Changelog/3.0/Important-CategoryRepositoryEmptyUidList.rst`
  — the ACE-349 changelog entry, including the per-DBMS analysis for TYPO3 v12.
- `packages/fgtclb/academic-projects/Documentation/Changelog/3.0/Important-FlexFormUpgradeWizardMigratesRecords.rst`
  — the ACE-356 changelog entry, including how to re-run the repaired wizard.
- `.Build/vendor/typo3/cms-core/Classes/Database/Query/QueryBuilder.php` and
  `.../Query/Expression/ExpressionBuilder.php` — the authoritative source for
  both mechanisms; read the installed version, not the documentation of another.
- TYPO3 forge issue [#96434](https://forge.typo3.org/issues/96434) — the change
  that introduced the array quoting helpers.
