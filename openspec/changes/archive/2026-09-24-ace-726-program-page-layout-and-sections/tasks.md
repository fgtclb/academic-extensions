## 1. Tests first

- [x] 1.1 Add a fixture site package to
  `Tests/Functional/Pages/Fixtures/` in `academic-programs` with a
  `Layouts/Default.html` that prints a marker around section `Main`; assert
  the marker and `academic-programs-detail` are both present, and record that
  the marker is missing on the unchanged code.
- [x] 1.2 Add a case with the layout setting `Wide` and a fixture layout
  `Wide`; record that it fails on the unchanged code.
- [x] 1.3 Add a `PAGEVIEW` fixture page object with its own `paths.100` and a
  partial of its own used by its layout; assert it resolves on doktype 20,
  and record that it fails on the unchanged code.
- [x] 1.3a Add a case for a site package without any layout: the program
  page renders its content through the fallback layout, without an error.
- [x] 1.4 Add cases for a header partial override at index 75, for the back
  link with and without `plugin.tx_academicprograms.page.listPid`, and for a
  whole
  `AcademicProgram.html` override; record which fail on the unchanged code.

## 2. Implementation

- [x] 2.1 Split `Resources/Private/Pages/AcademicProgram.html` into layout,
  section `Main` and the partials `Program/Page/Header`, `Program/Page/Media`,
  `Program/Page/Facts` and `Program/Page/Content`, keeping today's output
  inside `Main`.
- [x] 2.2 Add `plugin.tx_academicprograms.page.layout` (default `Default`)
  and `plugin.tx_academicprograms.page.listPid` (default 0) to
  `Configuration/Sets/Full/settings.definitions.yaml` (create it, or extend it
  when the facts field list change landed first) and to
  `Configuration/TypoScript/constants.typoscript` with identical defaults, and
  pass them as `page.10.variables.programPageLayout` and
  `page.10.variables.programListPid` inside the doktype 20 condition; verify
  1.2 on a `FLUIDTEMPLATE` and a `PAGEVIEW` fixture.
- [x] 2.2a Ship the fallback layout `Default` (section `Main` only) in
  `Resources/Private/PageLayoutFallback/Layouts/` and register it under a
  unique negative key in `layoutRootPaths` and `paths`.
- [x] 2.3 Move the page paths in
  `Configuration/TypoScript/Page/AcademicPrograms.typoscript` from index 100 to
  50 and remove `layoutRootPaths.100`; verify 1.1 to 1.4 pass on v13 and v14.
- [x] 2.4 If the dynamic layout name fails on either core version, ship a
  literal `Default`, drop the setting and adjust 1.2. Not needed: it renders
  on v13 and v14, the compiled template included.
- [x] 2.5 Revert the `<f:layout>` line, watch 1.1 go red, restore it.

## 3. Documentation

- [x] 3.1 Document the layout contract, the settings, the partials and the
  path index in `Documentation/Configuration/`.
- [x] 3.2 Add
  `Documentation/Changelog/3.0/Breaking-ProgramPageRendersInsideTheSiteLayout.rst`
  from `Build/Documentation/Templates/Changelog-Breaking.rst`, covering the
  visible change, index 100 to 50 and the layout requirement.
- [x] 3.3 Add or update the `docs/` section that describes how the page types
  of the extensions are rendered, and link it from its `Index.md`.

## 4. File the issue

- [x] 4.1 After implementation, file the ACE issue in YouTrack and rename the
  change to `ace-726-program-page-layout-and-sections`.
- [x] 4.2 Commit in TYPO3 Core format, `[!!!][FEATURE] ACE-726: <subject>`.

## 5. Definition of done

- [x] 5.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 13`.
- [x] 5.2 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 14`.
- [x] 5.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 5.4 `docs/` and the `Documentation/` changelog updated in the same
  change.
- [x] 5.5 Archive the change as the last commit of the pull request.
