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
resolves the `EXT:` paths through `GeneralUtility::getFileAbsFileName()` and
the extension through `PackageManager`, prints the findings and chooses the
exit status. The extension key is an **argument**, not the `--extension`
option of the sketch below: a Symfony option cannot be required, and this one
is. Rejected: the logic in the command,
which a follow-up check group (`cross-cutting-09`) could not reuse and a unit
test could not reach without a console.

### Mirror `Resources/Private/` by default

`--override-path` (repeatable) is compared with
`EXT:<extension>/Resources/Private/`, the layout projects use for override
folders. `--upstream-path` names another upstream folder for an override that
points straight at, for example, `Templates/`. Rejected: guessing the root by
searching all upstream folders for a matching file name, which is ambiguous
for `List.html` and `Item.html`.

### A partial failure still reports what it checked

An invalid input does not end the run. A view root path naming a folder that is
not there is reported with its TypoScript path and skipped, and the other root
paths of the site are still compared; a site that cannot be read at all does
not discard the findings of the folders `--override-path` named before it. Only
the exit status is affected — `Command::INVALID` wins over both `FAILURE` and
`SUCCESS`, because the run did not check everything it was asked to. Rejected:
returning on the first invalid input, which answers "0 problems and 0 notices
in 0 override folders" for a project full of dead overrides and is the worst
possible answer in a pipeline.

`GeneralUtility::getFileAbsFileName()` refuses an absolute path outside the
project root, so that case gets a message of its own rather than "does not
exist", which would send the integrator looking for a folder that is there.

### Findings and exit status

Precedence for a file: an exact match gives either `identical` or nothing,
then a case-insensitive match gives `case-mismatch`, else `missing-upstream`.
`identical` is a notice: a deliberate copy that pins markup must not fail CI.
Exit statuses are `Command::SUCCESS`, `Command::FAILURE` on problems, and
`Command::INVALID` on invalid input. Rejected: failing on `identical`.

### Three fixture extensions, because the closure has depth

`test_upgrade_check` is the checked extension, `test_upgrade_check_project` the
project's site package, and `test_upgrade_check_shared` sits between the first
and `academic_base`: the checked extension requires only the shared one, so
`academic_base` is reachable at depth two and nowhere else. Its partial folder
is a root path of the fixture TypoScript, so a closure that walks one level
instead of all of them turns four tests red. Without the third package every
exclusion in the suite was reachable at depth one, and the `while` queue could
have been a `foreach` without any test noticing.

### Decided: a `--site` mode through environment-state-manager

`--site=<identifier>` resolves the site's root page, builds a frontend
environment for it through the state manager of
`fgtclb/environment-state-manager` (`execute()` with a frontend
`StateBuildContext`, which restores the previous state afterwards), and reads
`plugin.tx_<extension>.view.templateRootPaths`, `partialRootPaths` and
`layoutRootPaths` from the `frontend.typoscript` request attribute. Every
path that is **not shipped by the extensions themselves** becomes an
override folder, compared with the upstream `Templates/`, `Partials/` or
`Layouts/` folder of its kind, so `--upstream-path` is not needed in this
mode.

**Correction of a premise.** The change said "every path except the
extension's own", and that is wrong: a plugin's root paths routinely name
folders of *other* extensions. `plugin.tx_academicpersons.view.partialRootPaths.-1`
is `EXT:academic_base/Resources/Private/Partials/`, and
`plugin.tx_academicpersonsedit.view.partialRootPaths` holds both
`EXT:academic_persons/…/Partials/` and
`EXT:fluid_styled_content/…/Partials/`. Under the original rule one `--site`
run for `academic_persons_edit` reports the 19 partials of `academic_persons`
and the 19 of `fluid_styled_content` as dead overrides - 38 findings before
the first real one. A root path counts as upstream when it lies inside the
checked extension, inside any package of its transitive requirement closure, or
inside a TYPO3 system extension (`MetaData::isFrameworkType()`). The closure is
read from `getValueFromComposerManifest('require')`, **not** from
`MetaData::getConstraintsByType('depends')`. That list is derived, and what it
holds depends on the core version *and* on how the package was registered.
Measured for academic_base, same source, four environments: the v13.4 composer
artifact gives `php symfony/serializer core extbase`, the v14.3 artifact gives
`typo3/cms-core typo3/cms-extbase`, a v13.4 functional test instance gives
`core backend extbase environment_state_manager` - from `ext_emconf.php`, which
is the only place `backend` is named - and a v14.3 test instance gives composer
names again. On v14 a package registered at runtime additionally loses a
requirement composer already installed, so the fixture requiring
`fgtclb/academic-base` gets `['typo3/cms-core']`: the entry this rule needs is
gone in exactly the environment the tests run in. The manifest `require` is the
same list in all four, and `getPackageKeyFromComposerName()` accepts both
spellings. Both methods are `@internal` on v13 and v14 - there is no public API
for the question, so this is a deliberate use of internal API and a TYPO3 v15
re-check item. The direction is what makes this safe: a
project's site package *depends on* the academic extensions, never the other
way round, so it is never excluded. `UpgradeCheckCommandSiteModeTest` goes red
under the original rule. The site's findings are printed
under the site identifier. The environment is built only in this mode; path
mode keeps working without a frontend context. academic_base requires
`fgtclb/environment-state-manager`, which the other eleven extensions already
require, so no installation gains a package.

**This reverses an unreleased 3.0 change and takes a changelog entry with
it.** `9a5ade53e` removed the `require` from academic_base and documented it
as `3.0/Breaking-RemovedEnvironmentStateManagerDependency.rst`; the dependency
had been added in 2.4.0 for the internal environment subsystem that 3.0
removes. With the command needing it again the entry states something that no
longer happens, and 3.0 is unreleased, so no installation ever saw the
removal: the entry is deleted and `Feature-UpgradeCheckCommand.rst` says the
2.4.0 dependency is kept for a new reason. The 2.4.0 deprecation of the
internal subsystem and `3.0/Breaking-RemovedInternalEnvironmentStateManagerSubsystem.rst`
are untouched - the classes are still gone. The emconf `depends` entry is
enforced by the testing framework as well (`Package "academic_base" depends on
package "environment_state_manager" which does not exist`), so the three
functional test classes of academic_base that name their own
`$testExtensionsToLoad` gained `fgtclb/environment-state-manager`. The
`ExtensionLoadedTest` classes of the other extensions did **not** need it:
their `$expectedLoadedExtensions` is an assertion list, not a load list, and
they already load the package through their own abstract test case.

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
  **Checked (task 1.1): only one of them does, so they are not treated as the
  same template.** Fluid 5 (TYPO3 v14, `TemplatePaths::resolveFileInPaths()`)
  tries `Name.fluid.html`, then `Name.html`, then `Name`, plus a `ucfirst()`
  fallback. Fluid 4 (TYPO3 v13) tries `Name.html` and `Name`, and nothing
  else. An override `Name.fluid.html` against an upstream `Name.html` is
  therefore dead on TYPO3 v13, so reporting it as `missing-upstream` is
  correct there and conservative on v14 - and the behaviour stays identical on
  both versions, which the proposal promises. No file of any academic
  extension uses the form.
- [A project override mirrors a different root than `Resources/Private/`] →
  `--upstream-path`, documented with an example.
- [A site sets a root path through a constant that points to a missing
  folder] → `--site` reports the missing folder as invalid input for that
  site, with the TypoScript path that named it.
- [The frontend environment of a site fails to build, for example when the
  site's root page does not exist] → the command names the site and exits with
  `Command::INVALID`, without findings for it. **The example in the original
  wording - "without a `sys_template` record or set" - behaves differently per
  core version**: TYPO3 v14 refuses to build the environment for such a site
  (`No site configuration or TypoScript template record found!`), TYPO3 v13
  builds it and renders the core default TypoScript it delivers to every site
  since v13.2 (#103485). Both end in `Command::INVALID`, on v13 through the
  second guard: a setup without anything below `plugin.tx<extension>.view` is
  rejected on its own, because reporting nothing and succeeding would tell a
  pipeline the project is clean when the site was never asked about the
  extension. The test for that guard gives its site a root TypoScript record
  with unrelated content, the only shape that reaches it on both versions.

## Open Questions

None.
