## Why

`academic:upgrade:check` reports what a TypoScript record, a page or a site
references **directly**. Four silent failures sit one step further out, and an
installation upgraded to 3.0 hits them exactly as quietly as the ones the
command already names.

The first one is not hypothetical. The 2.4 entry
`academic-persons/Documentation/Changelog/2.4/Breaking-SiteSetsAndStaticTemplatesRestructured.rst`
describes it in its own Impact section: "A site package that imported one of the
removed files by path fails to resolve it. `@import` of a missing file is
silent, so this shows up as missing configuration rather than as an error
message." One of the six analysed projects ships exactly that shape.

## What Changes

- The configuration check gains four findings:
  - an `@import` in the **Constants** or **Setup** field of a TypoScript record
    that resolves to no file of an academic extension — a warning naming the
    record, the field and the reference;
  - an academic `<INCLUDE_TYPOSCRIPT:` in either of those fields, which TYPO3
    v14 no longer reads at all — the syntax was most used in exactly that
    place before `@import` existed;
  - a TypoScript record on the root page of a site that is driven by site sets
    and carries a **clear** flag for a branch those sets deliver — a warning,
    because the record discards the whole set contribution to that branch;
  - an academic reference in a page TSconfig file that the installation
    **does** resolve — the check follows the file rather than stopping at the
    reference.
- The upgrade check command rejects `--upstream-path` given without
  `--override-path`, which it silently ignores today.
- Nothing is rewritten: no record, file or site configuration is changed.

The behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `academic-base/upgrade-configuration-check`: four findings are added, and
  the page TSconfig requirement follows a reference that resolves instead of
  stopping at it.
- `academic-base/upgrade-check`: `--upstream-path` without `--override-path`
  becomes invalid input.

## Impact

- `FGTCLB\AcademicBase\Upgrade\ConfigurationChecker` reads two more columns of
  a table it already queries, one more column for the site check, and follows
  page TSconfig files it already resolves. `UpgradeCheckCommand` gains one
  guard.
- Reads `sys_template`, `pages`, the site configurations and the files an
  academic extension ships; writes nothing.
- No dependency change.
- Integrator documentation and a 3.0 `Feature-` changelog entry in
  `academic_base`.

## Non-goals

- Reporting a resolving reference that no longer *means* what it did. The check
  answers "does TYPO3 read this", not "does it still do the same".
- Anything outside the academic extensions. A project's own or a core
  `@import`, static template or XCLASS stays none of this command's business.
- Rewriting records, files or site configurations.
- A backport to branch `2`: the check targets the upgrade to 3.0.

## Source

Follow-up of `ace-713-upgrade-check-configuration`, whose review named all four
as gaps. Filed as **ACE-714** before implementation, because the change is a
named follow-up of merged work rather than one of the 92 changes derived from
the project differences analysis of 2026-09-12.
