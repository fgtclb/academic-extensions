# Architecture

The rules the code follows, and the reasoning behind them. Where the code base
does not yet follow a rule consistently, the page says so rather than
describing an intention as if it were the state.

## The short version

- A difference between core versions is a **switch** while it is one or two
  lines, and a **class split** only when a whole class has to differ.
- Services are **stateless**. New services must be; existing ones must not gain
  state.
- Never hand a raw array to `in()` or `notIn()`, and build a constraint on the
  query builder that executes it. Both rules come from defects that reached a
  release.

## Pages

| Page                                                  | Contents                                                                                                                                              |
|-------------------------------------------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------|
| [Core version aware code](core-version-aware-code.md) | The version switches that exist today, what a `Core13/`/`Core14/` split would look like, and the APIs that cannot be migrated while v13 is supported. |
| [Dependency injection](dependency-injection.md)       | How services are configured across the extensions, why they must be stateless, and which TYPO3 attributes are safe on both core versions.             |
| [Class design](class-design.md)                       | `final`, `readonly`, constructor versus method injection, data objects, and the traps in Extbase models.                                              |
| [Database queries](database-queries.md)               | Quoting value lists, and keeping a constraint on the builder that executes it.                                                                        |

## See also

- [Dual core setup](../development/dual-core-setup.md) — running against both
  core versions.
- [Quality gates](../development/quality-gates.md) — PHPStan is configured per
  core version.
- [Testing](../testing/Index.md) — how core version differences are covered by
  tests.
