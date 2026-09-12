## Context

- Nothing on `main` compares project overrides with upstream. The only upgrade
  aids are specific to the persons settings:
  `academic-persons/Classes/Report/LegacySettingsStatus.php` and
  `Classes/Command/MigrateSettingsCommand.php`.
- academic_base configures its services through `Configuration/Services.yaml`
  (`resource: '../Classes/*'`, autowire, autoconfigure), so a class carrying
  Symfony's `#[AsCommand]` is registered without further configuration. That
  is how `academic-partners/Classes/Command/GeocodeCommand.php` is registered.
- academic_base requires only `typo3/cms-core`, `typo3/cms-extbase` and
  `symfony/serializer`; `fgtclb/environment-state-manager` (`^2.0.1@dev`) is
  required by the other eleven academic extensions, not by academic_base.
- The frontend environment builder of `fgtclb/environment-state-manager`
  (`Core13/` and `Core14/FrontendEnvironmentBuilder.php`) sets the full setup
  TypoScript of the site as the request attribute `frontend.typoscript` on
  both core versions, from a `StateBuildContext` holding the page id.
- The upstream Fluid roots differ per extension (`Templates/`, `Partials/`,
  `Layouts/`, `Pages/`, `Frontend/Default/...` in academic_study_plan), but
  all of them lie below `Resources/Private/`.

## Goals / Non-Goals

**Goals:**

- One invocation per extension, usable in CI; a frontend context only in
  `--site` mode.

**Non-Goals:**

- Comparing with a recorded hash of the upstream file a project once copied.

## Decisions

### A stateless checker and a thin command

`FGTCLB\AcademicBase\Upgrade\TemplateOverrideChecker`, final and stateless,
takes two resolved absolute folders. It returns a list of
`TemplateOverrideFinding`, a final readonly class with the relative path, the
upstream name and a `TemplateOverrideFindingKind` enum (`MissingUpstream`,
`CaseMismatch`, `Identical`). `FGTCLB\AcademicBase\Command\UpgradeCheckCommand`
is final and carries `#[AsCommand(name: 'academic:upgrade:check')]`. It
resolves the `EXT:` paths and the extension through `PackageManager`, prints
the findings and chooses the exit status. Rejected: the logic in the command,
which a follow-up check group (`cross-cutting-09`) could not reuse and a unit
test could not reach without a console.

### Mirror `Resources/Private/` by default

`--override-path` (repeatable) is compared with
`EXT:<extension>/Resources/Private/`, the layout projects use for override
folders. `--upstream-path` names another upstream folder for an override that
points straight at, for example, `Templates/`. Rejected: guessing the root by
searching all upstream folders for a matching file name, which is ambiguous
for `List.html` and `Item.html`.

### Findings and exit status

Precedence for a file: an exact match gives either `identical` or nothing,
then a case-insensitive match gives `case-mismatch`, else `missing-upstream`.
`identical` is a notice: a deliberate copy that pins markup must not fail CI.
Exit statuses are `Command::SUCCESS`, `Command::FAILURE` on problems, and
`Command::INVALID` on invalid input. Rejected: failing on `identical`.

### Decided: a `--site` mode through environment-state-manager

`--site=<identifier>` resolves the site's root page, builds a frontend
environment for it through the state manager of
`fgtclb/environment-state-manager` (`execute()` with a frontend
`StateBuildContext`, which restores the previous state afterwards), and reads
`plugin.tx_<extension>.view.templateRootPaths`, `partialRootPaths` and
`layoutRootPaths` from the `frontend.typoscript` request attribute. Every
path except the extension's own becomes an override folder, compared with the
upstream `Templates/`, `Partials/` or `Layouts/` folder of its kind, so
`--upstream-path` is not needed in this mode. The site's findings are printed
under the site identifier. The environment is built only in this mode; path
mode keeps working without a frontend context. academic_base requires
`fgtclb/environment-state-manager`, which the other eleven extensions already
require, so no installation gains a package.

Rejected: path mode only, as first proposed, which leaves the integrator to
read the root paths of each site by hand, the step the command exists to
remove. Rejected: resolving the `page.10` root paths as well. The site's theme
shares them, so every theme partial would be reported as `missing-upstream`;
page template overrides are checked with `--override-path`. Rejected: an own
frontend bootstrap in academic_base, which would duplicate the version split
that environment-state-manager already carries.

The configuration check of `ace-tbd-upgrade-check-configuration` stays path
and database based; it may use the `--site` mode when a future check needs
rendered TypoScript.

### What the integrator sees

Guessed layout — a sketch, not a design:

```text
$ typo3 academic:upgrade:check --extension=academic_persons_edit --override-path=EXT:site/...
 x missing-upstream  Templates/Profile/Show.html
 = identical         Partials/Profile/Show/Image.html
 ! case-mismatch     Pages/Default/Academicprogram.html -> AcademicProgram.html
```

## Risks / Trade-offs

- [Fluid also resolves `.fluid.html` on the installed Fluid versions] →
  Task 1.1 checks both Fluid versions and treats `Name.fluid.html` and
  `Name.html` as the same template if they do.
- [A project override mirrors a different root than `Resources/Private/`] →
  `--upstream-path`, documented with an example.
- [A site sets a root path through a constant that points to a missing
  folder] → `--site` reports the missing folder as invalid input for that
  site, with the TypoScript path that named it.
- [The frontend environment of a site fails to build, for example without a
  `sys_template` record or set] → the command names the site and exits with
  `Command::INVALID`, without findings for it.

## Open Questions

None.
