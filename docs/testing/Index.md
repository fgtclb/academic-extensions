# Testing

Two suites, both containerized, both discovered across all extensions at once.

## Quick start

```bash
# Always install the dependency set for the version you are about to test.
Build/Scripts/runTests.sh -t 12 -p 8.2 -s composerUpdate

Build/Scripts/runTests.sh -t 12 -p 8.2 -s unit
Build/Scripts/runTests.sh -t 12 -p 8.2 -s functional

# Restrict a run to a directory or a single file with a trailing path.
Build/Scripts/runTests.sh -t 12 -s unit packages/fgtclb/academic-base/Tests/Unit

# Anything that writes to the database is also run on PostgreSQL.
Build/Scripts/runTests.sh -t 12 -p 8.2 -d postgres -s functional
```

This branch supports TYPO3 v12 and v13. `-t` accepts `12` or `13` and defaults
to `12`; `-p` accepts `8.1` through `8.5` and defaults to `8.2`.

## Rules that apply to every test

- **The suites are hard breaking.** Deprecations, notices, warnings and risky
  tests all fail the run. Never silence one to get a run green; fix the cause.
- **A test without an assertion still passes** — that flag is deliberately
  relaxed. So prove a new test can fail: break what it covers, watch it go red,
  restore.
- **SQLite is the default and is not sufficient.** Several defects in this
  repository were invisible on SQLite and only appeared on MySQL, MariaDB or
  PostgreSQL — and at least one was the other way round.
- A test that applies to only one core version is scoped with the
  `not-core-12` / `not-core-13` groups, never with a runtime condition. The name
  says what the group is excluded from, so `not-core-13` runs on v12 only.

## Pages

| Page                                              | Contents                                                                                              |
|---------------------------------------------------|-------------------------------------------------------------------------------------------------------|
| [PHPUnit configuration](phpunit-configuration.md) | Where the configuration comes from, what deviates from the template, and the exact strictness policy. |
| [Unit tests](unit-tests.md)                       | Running them, discovery, conventions, core version aware tests.                                       |
| [Functional tests](functional-tests.md)           | Databases and why the default is not enough, loading extensions, real defects each DBMS caught.       |
| [Fixture extensions](fixture-extensions.md)       | The test-only extensions and the mechanism that registers them.                                       |
| [Testing helper](testing-helper.md)               | Every trait in `packages-dev/testing-helper/`, and the defect each one exists for.                    |

## See also

- [Quality gates](../development/quality-gates.md) — where the suites sit among
  the other gates.
- [Dual core setup](../development/dual-core-setup.md) — the group convention
  and the dependency set rule.
- [Database queries](../architecture/database-queries.md) — the defects that
  motivate running PostgreSQL.
