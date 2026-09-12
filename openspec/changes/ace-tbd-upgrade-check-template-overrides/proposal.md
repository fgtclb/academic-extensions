## Why

3.0 replaced the academic_persons_edit templates and renamed or restructured
templates in other extensions. A project override of a file that no longer
exists upstream is never rendered, a byte-identical copy only freezes the
upstream markup, and a name that differs from upstream only in case resolves
on some file systems and not on others. Nothing reports any of this. The
project inventories count at least 35 dead academic_persons_edit overrides in
three projects alone.

## What Changes

- academic_base (`packages/fgtclb/academic-base`) ships the console command
  `academic:upgrade:check`. It compares the Fluid files below one or more
  override folders with those of one installed academic extension.
- It reports per file:
  - `missing-upstream`: the upstream file does not exist;
  - `case-mismatch`: the upstream file exists under a name that differs only
    in case;
  - `identical`: a byte-identical copy, reported as a notice.
- With `--site=<identifier>` the command takes the override folders from the
  TypoScript of that site: it builds a frontend context for the site's root
  page through `fgtclb/environment-state-manager` and reads the extension's
  plugin view root paths (`templateRootPaths`, `partialRootPaths`,
  `layoutRootPaths`). `--site` and explicit override folders can be combined.
- The exit status is non-zero when a problem is reported, so the command can
  run in CI. A notice alone does not fail.
- The command only reads; it changes no file.
- The behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-base/upgrade-check`: the template override check an integrator
  runs before or after an upgrade.

### Modified Capabilities

None.

## Impact

- academic_base: one command and one stateless checker service, registered
  through the existing `Configuration/Services.yaml` autoconfiguration.
- academic_base requires `fgtclb/environment-state-manager` in its
  `composer.json` and `ext_emconf.php`, with the constraint the other academic
  extensions already use; eleven of the twelve require it today, so no
  installation gains a new package.
- No database, TCA or TypoScript change.

## Non-goals

- Resolving the `page.10` root paths of a site in `--site` mode; they are
  shared with the site's theme, and page template overrides are checked with
  explicit override folders.
- Checks of static templates, TSconfig imports, sets or XCLASSes (candidate
  `cross-cutting-09`, which extends this command).
- Reporting that an upstream file changed since it was copied.
- Backporting to branch `2`; the check compares against the installed 3.x.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`cross-cutting-08`). All six analysed projects carry their own code for this
today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-upgrade-check-template-overrides` when the issue is filed after
implementation.
