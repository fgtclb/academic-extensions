## 1. Tests first

- [x] 1.1 For each of the fourteen plugin views, a frontend rendering test
      with a header, a subheader and the header layout 2 on the default
      (fluid_styled_content) layout: exactly one heading and one subheader,
      counted in the DOM with `ContentElementHeaderAssertionTrait` of the
      testing helper (added by the jobs change), and no heading reading the
      header or the subheader inside the plugin output - the profile detail
      renders a `header` of its own (`Profile/PublicProfile/Headline`), so
      counting every `header` does not work there. It passes today; it pins
      that the switch off changes nothing.
- [x] 1.2 The same views with the switch on and a fixture content element
      layout without a `Header` section: the header once inside the plugin
      output for the header layouts "Default" and 2, none for "Hidden".
      Record that the cases fail on the current templates: 34 of them on
      TYPO3 v13 (persons 14, partners 8, programs 4 and the finder 2,
      projects 4, contacts 2), each at the first heading count.
- [x] 1.3 Run the switched-on cases on v14 without the `record` assignment
      and record the failure, which proves the controller part is needed:
      every one fails with the `InvalidArgumentValueException` of
      `f:render.text`, "The record argument must be an instance of ... Given:
      null".

## 2. Implementation

- [x] 2.1 Constant and setting `renderContentElementHeader` (default `0`),
      a site setting where the extension declares settings, and
      `settings.defaultHeaderType` in the five extensions,
      the partial path of EXT:fluid_styled_content below every project slot;
      verify a project override of `Header/All` at the project slot wins
      (one test per extension, red with the path moved above the project
      key).
- [x] 2.2 Assign `record` in the persons, partners, programs and projects
      controllers and render `Header/All` behind the switch in the fourteen
      templates; verify group 1 on v13 and v14.

## 3. Documentation

- [x] 3.1 The switch in the configuration chapter of each extension: what it
      does and when a site needs it; `docs/architecture/content-element-rendering.md`
      lists the extensions that have it.
- [x] 3.2 `Documentation/Changelog/3.0/Feature-PluginsCanRenderContentElementHeader.rst`
      in the five extensions.

## 4. File the issue

- [x] 4.1 File the ACE issue in YouTrack, rename the change to
      `ace-<NNN>-plugin-content-element-header`, and commit as
      `[FEATURE] ACE-<NNN>: <subject>` in TYPO3 Core format. Filed as ACE-743
      (`[3.x]`, Story, Version 3.0.0, subtask of ACE-10, depends on ACE-729).

## 5. Definition of done

- [x] 5.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
      `functional` for TYPO3 v13; the same after its own `composerUpdate` for
      TYPO3 v14.
- [x] 5.2 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 5.3 `docs/` and the five extensions' `Documentation/` changelogs updated
      in the same change.
- [x] 5.4 Archive the change as the last commit of the pull request.
