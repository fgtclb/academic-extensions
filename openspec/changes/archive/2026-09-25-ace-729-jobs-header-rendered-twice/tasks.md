## 1. Tests first

- [x] 1.1 Rewrite the four header tests (`listPluginRendersContentElementHeader`,
  `detailPluginRendersContentElementHeader`,
  `newJobFormPluginRendersContentElementHeader`,
  `biteJobsListPluginRendersContentElementHeader`) to set a subheader and the
  header layout 2 and assert exactly one heading and one subheader, counted in
  the DOM. Record that they fail against the unchanged templates with two of
  each (the probe of design.md saw it on v13 and v14).
- [x] 1.2 Add a case per plugin with the header layout "Default": the header
  once, and no `header` element inside the plugin's wrapper. Record that it
  fails today on the empty element.
- [x] 1.3 Add a case with the header layout "Hidden": no header. It passes
  today; say so, it pins the layout's behaviour.
- [x] 1.4 Add a case with the switch on and a fixture content element layout
  without a `Header` section (as a site package ships it): the header once,
  inside the plugin output, for the header layout "Default" and for 2. Record
  that the "Default" case fails without the `defaultHeaderType` mapping.

## 2. Implementation

- [x] 2.1 The constant and setting `renderContentElementHeader` (default `0`)
  in both extensions, the site setting definition in `academic_jobs`, and
  `settings.defaultHeaderType` mapped in both plugin setups;
  check an undefined `styles.content.defaultHeaderType` and guard it if needed.
- [x] 2.2 Wrap the `Header/All` line of `Job/List.html`, `Job/Show.html`,
  `Job/New.html` and `BiteJobs/List.html` in the switch; keep `record`, the
  partial path and the requirement. Verify group 1 on v13 and v14.

## 3. Documentation

- [x] 3.1 `Documentation/Changelog/2.4/Important-*.rst` in both extensions:
  the markup before and after, the switch for sites whose layout renders no
  header, and the line a project copy of a template should drop.
- [x] 3.2 Document the setting in both extensions' `Documentation/`
  configuration chapter: what it does and when a site needs it.
- [x] 3.3 `docs/architecture/content-element-rendering.md`: who renders a
  plugin header (the layout by default, the plugin behind the switch), the
  table of templates reaching `Header/All`, and the reasoning of the `record`
  section; `docs/architecture/Index.md` if the summary changes.

## 4. File the issue

- [x] 4.1 After implementation, file the ACE issue (`[3.x][2.x]`, Bug,
  Version 2.4.0, relates to ACE-728 and ACE-270) and rename the change to
  `ace-<NNN>-jobs-header-rendered-twice`.
- [x] 4.2 Commit in TYPO3 Core format `[BUGFIX] ACE-<NNN>: <subject>`,
  subject at most 52 characters, body wrapped at 72. This proposal commit is
  the first commit of the same pull request.

## 5. Definition of done

- [x] 5.1 `Build/Scripts/runTests.sh -t 13 -s composerUpdate`, then
  `lintPhp`, `cgl -n`, `phpstan`, `unit` and `functional` with `-t 13` green.
- [x] 5.2 The same for `-t 14` after its own `composerUpdate`.
- [x] 5.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 5.4 `docs/` and the `Documentation/` changelog entries are part of the
  change; `README.md` and `CONTRIBUTING.md` still only summarize and link.
- [x] 5.5 Archive the change as the last commit of the pull request and
  verify the delta specs landed in `openspec/specs/`.
