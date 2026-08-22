# Academics Development Site

Extension key `academics_dev_site`, composer package
`fgtclb/academics-monorepo-dev-site`.

## What this is

This package carries the **seed sets** for the development instances of this
mono repository, [`core-13/`](../../core-13) and [`core-14/`](../../core-14). It
holds content, no code: the YAML files below `Configuration/DataFactory/`
describe the page tree, the records and the content elements a freshly set up
instance is filled with, so that an instance can be rebuilt from nothing and
still look the same on every machine.

There is one set, `Configuration/DataFactory/academics-instance/`, and it serves
both instances: the backend layouts, the CTypes and the plugin FlexForms in it
are identical on TYPO3 v13 and v14. A set is a directory with a `config.yml`
naming it and the scenario files carrying its records:

| File            | Holds                                                             |
|-----------------|-------------------------------------------------------------------|
| `config.yml`    | The identifier `academics-instance`, the title and the scenarios. |
| `Scenario.yaml` | Every record, in the scenario format of `sbuerk/data-factory`.    |

It is applied from within an instance:

```bash
cd core-13
ddev composer instance:seed
```

which runs `vendor/bin/typo3 data-factory:import academics-instance`. The
`data-factory:import` command comes from `sbuerk/data-factory`, which both
instances require for it; `data-factory:list` shows every set the installation
provides.

A set is addressed by its **identifier**, never by a path: the identifier is
globally unique across the active packages, and discovery finds the set wherever
the extension shipping it is installed — which is what resolves inside DDEV and
on a host stack alike.

Three rules the set follows, all of which matter when changing it:

- **It declares uids.** The committed site configurations point at
  `rootPageId: 1` and the plugins name their pages and records by uid. A
  declared `id` is a suggestion to DataHandler, honoured only for an admin
  backend user. A record without one is *not* written with an auto increment
  uid: it gets a value from a counter that starts at 10000 and runs per entity
  name, so every record here declares one.
- **`hidden: 0` sits on the wildcard entity.** The `pages` TCA defaults the
  column to `1`, so a page written without it exists and renders nothing. It
  must not be repeated on a declared entity: the wildcard is merged with
  `array_merge_recursive()`, so a key on both sides becomes a list and reaches
  the database as the string `Array`.
- **It expects an empty page tree.** Importing on top of an existing tree
  collides on the suggested uids and is refused, so a run belongs to a freshly
  set up instance — see
  [Rebuilding an instance from nothing](../../docs/development/environment.md#rebuilding-an-instance-from-nothing).

## What this is not

This is a **development aid**. It is not part of any release:

- it is never tagged, never split out to a read-only repository, and never
  published to the TER,
- it is never installed in a customer project, and nothing in `packages/` may
  depend on it,
- it is not analysed by phpstan and not covered by the test suites, like the
  other packages below `packages-dev/`.

It lives in `packages-dev/` for exactly that reason — that directory holds the
packages that support the development of the academic extensions rather than
being one of them.

## See also

- [Development environment](../../docs/development/environment.md)
- [Monorepo layout](../../docs/development/monorepo-layout.md)

## Credits

This extension was created by [FGTCLB GmbH](https://www.fgtclb.com/).

[Find more TYPO3 extensions we have developed](https://github.com/fgtclb/).
