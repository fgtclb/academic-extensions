# Architecture

The rules the code follows, and the reasoning behind them. Where the code base
does not yet follow a rule consistently, the page says so rather than
describing an intention as if it were the state.

This branch supports **TYPO3 v12 and v13**.

## The short version

- A difference between core versions is a **switch** while it is one or two
  lines, and a **class split** only when a whole class has to differ. Both
  exist here: `academic-base` carries a real `Core12/`/`Core13/` split.
- Services are **stateless**. New services must be; existing ones must not gain
  state.
- Never hand a raw array to `in()` or `notIn()`, and build a constraint on the
  query builder that executes it. Both rules come from defects that reached a
  release, and the first one bites hardest on v12, which has no guard at all.

## Pages

| Page                                                  | Contents                                                                                                                                         |
|-------------------------------------------------------|--------------------------------------------------------------------------------------------------------------------------------------------------|
| [Core version aware code](core-version-aware-code.md) | The four mechanisms in use, the `Core12/`/`Core13/` split in `academic-base`, and how version specific tests are grouped.                        |
| [Dependency injection](dependency-injection.md)       | How services are configured across the extensions, why they must be stateless, and which Symfony and TYPO3 attributes exist on both v12 and v13. |
| [Class design](class-design.md)                       | `final`, `readonly`, constructor versus method injection, data objects, and the traps in Extbase models.                                         |
| [Database queries](database-queries.md)               | Quoting value lists, and keeping a constraint on the builder that executes it.                                                                   |

## See also

- [Dual core setup](../development/dual-core-setup.md) — running against both
  core versions.
- [Quality gates](../development/quality-gates.md) — PHPStan is configured per
  core version.
- [Testing](../testing/Index.md) — how core version differences are covered by
  tests.
