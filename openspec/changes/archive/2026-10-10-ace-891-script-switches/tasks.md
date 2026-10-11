## 1. Switches

- [x] 1.1 `academic_jobs`: setting on the aggregate set, constant, setup, and
      the `f:if` around the CKEditor script and `rich-text.js` in
      `Templates/Job/New.html`
- [x] 1.2 `academic_partners`: setting on the map set, constant, setup, the
      argument `assets` of `Partials/Partner/Map.html` with the library
      stylesheets inside the condition, the plugin template passing
      `settings.assets`, and `mapAssets` from the data processor
      `partner-data`
- [x] 1.3 `academic_persons`: setting on the aggregate set, constant, setup,
      the `f:if` in `Templates/Profile/Detail.html`
- [x] 1.4 `academic_persons_edit`: setting on the profile editing set,
      constant, setup, the `f:if` in `Templates/Profile/Index.html`
- [x] 1.5 `academic_programs`: setting on the aggregate set, constant, setup,
      the `f:if` in `List.html`, `Finder.html` and the three partials of the
      list form

## 2. Tests

- [x] 2.1 A `*ScriptSettingTest` per extension: the definition, the default
      through both mechanisms, off through the site setting and the constant
      with the markup present, shown red with each `f:if` removed
- [x] 2.2 `AcademicPartnerPageMapTest`: the partner page with the switch off
      through the constant and through the site setting, and a page template
      without the argument that keeps the script, shown red with the
      partial's fallback removed and with `mapAssets` left out of the
      processor
- [x] 2.3 Every one of the four list module registrations of
      `academic_programs` shown red on its own
- [x] 2.4 The settings lists of the site set delivery tests of jobs, persons,
      programs and the map configuration test of partners

## 3. Definition of done

- [x] 3.1 `lintPhp`, `cgl -n`, `phpstan`, `unit` and `functional` on SQLite
      for v13 and v14, each after its own `composerUpdate`
- [x] 3.2 `lintMarkdown -n`, the node suites and `checkRstRenderingSingle`
      for the five extensions
- [x] 3.3 `docs/architecture/frontend-javascript-loading.md`, linked from the
      architecture index and `docs/development/frontend-assets.md`
- [x] 3.4 A section `configuration-javascript` in the five manuals and a
      `Feature-JavaScriptSwitch.rst` per extension
- [x] 3.5 Commit message in TYPO3 Core format, `Resolves: ACE-891`,
      `Related: ACE-818`
