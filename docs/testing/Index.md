# Testing

Three suites, all containerized, all discovered across all extensions at once:
PHPUnit for unit and functional tests, and `node --test` for the behavioural
tests of the frontend TypeScript.

## Quick start

```bash
# Always install the dependency set for the version you are about to test.
Build/Scripts/runTests.sh -t 13 -p 8.2 -s composerUpdate

Build/Scripts/runTests.sh -t 13 -p 8.2 -s unit
Build/Scripts/runTests.sh -t 13 -p 8.2 -s functional

# Restrict a run to a directory or a single file with a trailing path.
Build/Scripts/runTests.sh -t 13 -s unit packages/fgtclb/academic-base/Tests/Unit

# Anything that writes to the database is also run on PostgreSQL.
Build/Scripts/runTests.sh -t 13 -p 8.2 -d postgres -s functional

# The JavaScript suite takes neither "-t" nor a composerUpdate.
Build/Scripts/runTests.sh -s testJs
```

## Rules that apply to every test

- **Prove a new test can fail**, whatever the suite — see below.
- **The PHP suites are hard breaking.** Deprecations, notices, warnings and risky
  tests all fail the run. Never silence one to get a run green; fix the cause.
- **A test without an assertion still passes** — that flag is deliberately
  relaxed. So prove a new test can fail: break what it covers, watch it go red,
  restore.
- **SQLite is the default and is not sufficient.** Several defects in this
  repository were invisible on SQLite and only appeared on MySQL, MariaDB or
  PostgreSQL — and at least one was the other way round.
- A test that applies to only one core version is scoped with the
  `not-core-13` / `not-core-14` groups, never with a runtime condition.
- **Assertions are called on `$this`, not on `self`.** `cgl` decides this, not
  taste: `Build/php-cs-fixer/config.php` sets
  `php_unit_test_case_static_method_calls` to `['call_type' => 'this']`, so
  `self::assertSame(...)` written by hand is rewritten to
  `$this->assertSame(...)` on the next `cgl` run and shows up as noise in the
  diff. Write `$this->` in the first place. The one exception the fixer leaves
  alone is a call inside a closure that has no `$this` — there is exactly one in
  the repository, in `FrontendUserPhoneNumberTypeResolverTest`.

## Pages

| Page                                                                                         | Contents                                                                                                     |
|----------------------------------------------------------------------------------------------|--------------------------------------------------------------------------------------------------------------|
| [PHPUnit configuration](phpunit-configuration.md)                                            | Where the configuration comes from, what deviates from the template, and the exact strictness policy.        |
| [Unit tests](unit-tests.md)                                                                  | Running them, discovery, conventions, core version aware tests.                                              |
| [Functional tests](functional-tests.md)                                                      | Databases and why the default is not enough, loading extensions, real defects each DBMS caught.              |
| [Fixture extensions](fixture-extensions.md)                                                  | The test-only extensions and the mechanism that registers them.                                              |
| [Testing helper](testing-helper.md)                                                          | Every trait in `packages-dev/testing-helper/`, and the defect each one exists for.                           |
| [Seed verification](seed-verification.md)                                                    | The manifest of the development seed, and the four checks that keep it, the YAML and the snapshots together. |
| [JavaScript tests](javascript-tests.md)                                                      | The `node --test` harness: the runner, the resolve hook, the stubs and what they do not cover.               |
| [Frontend verification for `academic-persons-edit`](academic-persons-edit-frontend-tests.md) | How the profile editing TypeScript is verified, by which suite, and what none of them executes.              |

## See also

- [Quality gates](../development/quality-gates.md) — where the suites sit among
  the other gates.
- [Dual core setup](../development/dual-core-setup.md) — the group convention
  and the dependency set rule.
- [Database queries](../architecture/database-queries.md) — the defects that
  motivate running PostgreSQL.
