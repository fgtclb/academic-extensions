## Why

An installation upgraded from a 2.x release to 3.0 keeps configuration that no
longer works, and nothing tells the integrator. A static template stored in a
TypoScript record that delivers nothing any more, a page TSconfig import of a
folder that was renamed, a site that depends on a set and also includes the
same extension's static template, an XCLASS of a class that is `final` on 3.0:
each fails silently or only at runtime. Six projects found these by hand.

## What Changes

- `academic_base` (`packages/fgtclb/academic-base`) gains a read-only
  configuration check with five findings:
  - a stored static template of an academic extension that the installation
    does not register or that has no TypoScript files;
  - an academic import in page TSconfig (the page field and the page's
    TSconfig includes) that does not resolve;
  - a site depending on an alias set (`fgtclb/academic-persons-default`,
    `fgtclb/academic-study-plan-default`): a notice to depend on the
    aggregate instead;
  - a site that depends on a set and includes the static template of the same
    extension: a warning;
  - an XCLASS of an academic class: a warning, and an error when the class is
    `final`.
- The findings are shown in the backend status report when EXT:reports is
  active.
- The same check becomes the second check group of the `academic:upgrade:check`
  command proposed by `ace-tbd-upgrade-check-template-overrides`, with a
  non-zero exit status on warnings and errors.
- Nothing is rewritten: no record, file or site configuration is changed.

The behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-base/upgrade-configuration-check`: what the configuration check
  reports, at which severity, and where an integrator sees it.

### Modified Capabilities

None.

## Impact

- A stateless checker service, a status provider and a command check group in
  `academic_base`, plus a new `Configuration/Services.php` next to the
  existing `Services.yaml` for the registration that depends on EXT:reports.
- Reads `sys_template`, `pages`, the site configurations and the XCLASS
  registry; writes nothing.
- No dependency change; EXT:reports stays optional.
- Integrator documentation and a 3.0 `Feature-` changelog entry in
  `academic_base`.

## Non-goals

- Rewriting stored static templates, imports or site dependencies. The rows
  and files belong to the project, and an automatic rewrite is guesswork.
- Detecting the replacement of a `final` class through dependency injection.
- Template override checks; they belong to
  `ace-tbd-upgrade-check-template-overrides`.
- A backport to branch `2`: the check targets the upgrade to 3.0.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`cross-cutting-09`). All six analysed projects carry their own code for this
today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-upgrade-check-configuration` when the issue is filed after
implementation.
