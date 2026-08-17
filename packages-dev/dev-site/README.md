# Academics Development Site

Extension key `academics_dev_site`, composer package
`fgtclb/academics-monorepo-dev-site`.

## What this is

This package carries the **seed definitions** for the development instances of
this mono repository, [`core-12/`](../../core-12) and
[`core-13/`](../../core-13). It holds content and instance configuration, no
application code: the YAML file below `Configuration/Seeds/` describes the page
tree, the records and the content elements a freshly set up instance is filled
with, so that an instance can be rebuilt from nothing and still look the same on
every machine.

There is one definition, `Configuration/Seeds/Instance.yaml`, and it serves both
instances: the backend layouts, the CTypes and the plugin FlexForms in it are
identical on TYPO3 v12 and v13. It is applied from within an instance:

```bash
cd core-12
ddev composer instance:seed
```

which runs
`vendor/bin/typo3 theme:seed EXT:academics_dev_site/Configuration/Seeds/Instance.yaml`.
The `theme:seed` command comes from `sbuerk/theme-extension-development`, which
both instances require **for its seeder only** — the theme itself is not used,
the instances are themed with `bk2k/bootstrap-package`.

A seed is always addressed through its extension path, never through a relative
filesystem path: that is the one form which resolves inside DDEV and on a host
stack alike.

Two rules the definition follows, both of which matter when changing it:

- **It declares uids**, because the committed site configurations point at
  `rootPageId: 1` and the plugins name their pages and records by uid. A
  declared uid is a suggestion to DataHandler, honoured only for an admin
  backend user.
- **It expects an empty page tree.** Seeding on top of an existing tree collides
  rather than adding, so a seed run belongs to a freshly set up instance — see
  [Rebuilding an instance from nothing](../../docs/development/environment.md#rebuilding-an-instance-from-nothing).

## The TypoScript of the instances

Site sets would be the obvious way to wire a site today, and they are not
available here: they arrived in TYPO3 v13.1 and this branch also supports v12,
where a site configuration has no `dependencies` key and provides no TypoScript
at all. The definition therefore writes one root `sys_template` record, which
works unchanged on both versions — and every set the academic extensions ship is
a plain `@import` of exactly the files that record includes, so nothing is lost
by taking the older road.

This package carried two pieces of glue for a while — the page template name of
the custom page types, and the backend layout of the `EXT:academic_partners`
page type. Both were workarounds for defects of those extensions rather than
instance configuration, both are fixed at the source now (ACE-450, ACE-451), and
both are gone. The package holds nothing but the seed definition again.

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
