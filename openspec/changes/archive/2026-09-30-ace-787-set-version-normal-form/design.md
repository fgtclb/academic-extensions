## Context

See the design of the `main` change,
`openspec/changes/archive/2026-09-30-ace-787-set-version-normal-form/design.md`
there, for the mechanisms and the test. The backport analysis:

- `bin/set-version` and packwright behave the same on both branches, the
  scripts differ in the per-branch lines only.
- A real run here rewrites five files, measured in a clone of `origin/2`
  (`12f5c328b`), `Tests/` included: the three `ext_emconf.php` and the two
  instance manifests. No comment, no `providesPackages`.
- The test is byte-identical to `main` and runs on PHP 8.1: its newest
  construct is an arrow function, PHP 7.4.

## Goals / Non-Goals

**Goals:** the same form and the same test as on `main`.

**Non-Goals:** anything `main` does not do.

## Decisions

The five files come from one real run of `bin/set-version 2.4.0 post-release`,
and a second run is the check, as on `main`. The instance manifests are sorted
by `composer require` itself, which is what keeps them sorted afterwards.

## Risks / Trade-offs

- [The `providesPackages` check is vacuous here] → it is kept to keep the test
  identical to `main`, and it starts to hold the day a manifest here declares
  the key.
