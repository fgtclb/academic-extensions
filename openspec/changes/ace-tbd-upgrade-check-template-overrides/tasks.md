## 1. Checker

- [ ] 1.1 Check in the Fluid sources of the v13 and v14 vendor trees whether
      `.fluid.html` is resolved next to `.html`, and record the result in
      `design.md`.
- [ ] 1.2 Add committed fixture folders (an upstream tree and an override tree
      with one dead file, one identical copy, one case-only difference and one
      changed copy) and a unit test for `TemplateOverrideChecker` asserting the
      three findings and no finding for the changed copy; break each branch of
      the checker once and record that its assertion goes red.
- [ ] 1.3 Implement the checker, the finding class and the kind enum; verify
      1.2 is green.

## 2. Command

- [ ] 2.1 Add a functional test with a fixture extension as upstream, running
      the command through Symfony's `CommandTester`; assert the output lines,
      exit status `FAILURE` with a problem, `SUCCESS` with only a notice, and
      `INVALID` for an unknown extension and a missing folder; record that it
      fails before the command exists.
- [ ] 2.2 Implement `UpgradeCheckCommand`; verify 2.1 on v13 and v14, and
      verify `vendor/bin/typo3 list` shows the command in a development
      instance.

## 3. Site mode

- [ ] 3.1 Add `fgtclb/environment-state-manager` with the constraint of the
      other academic extensions to the `composer.json` and `ext_emconf.php`
      of academic_base, then `composerUpdate` for v13 and v14.
- [ ] 3.2 Add a functional test with a fixture site whose TypoScript adds a
      project template, partial and layout root path for the fixture
      extension; run the command with `--site` and assert the findings of all
      three folders and no finding for the extension's own paths; record that
      it fails before the option exists. Show the "own path is excluded"
      assertion red by comparing every root path.
- [ ] 3.3 Add cases for an unknown site identifier and a site whose frontend
      environment cannot be built (no TypoScript record and no set): both
      exit `INVALID`; and a combined run of `--site` with `--override-path`.
- [ ] 3.4 Implement `--site` through the state manager's `execute()`; verify
      3.2 and 3.3 on v13 and v14, and that the global request, context and
      TSFE are restored after the run (assert one of them before and after).

## 4. Documentation

- [ ] 4.1 Add a section on the command to `docs/`, next to the other upgrade
      aids, and link it from its section `Index.md`.
- [ ] 4.2 Document the command in the academic_base `Documentation/` (usage,
      `--upstream-path` and `--site` examples, exit statuses, CI example),
      and add
      `academic-base/Documentation/Changelog/3.0/Feature-UpgradeCheckCommand.rst`
      from `Build/Documentation/Templates/Changelog-Feature.rst`.

## 5. File the issue

- [ ] 5.1 File the ACE issue in YouTrack, rename the change to
      `ace-<NNN>-upgrade-check-template-overrides`, and commit as
      `[FEATURE] ACE-<NNN>: Add the template override upgrade check` in TYPO3
      Core format.

## 6. Definition of done

- [ ] 6.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
      `functional` for TYPO3 v13; the same after its own `composerUpdate` for
      TYPO3 v14.
- [ ] 6.2 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 6.3 `docs/` and the academic_base `Documentation/` changelog updated in
      the same change.
- [ ] 6.4 Archive the change as the last commit of the pull request.
