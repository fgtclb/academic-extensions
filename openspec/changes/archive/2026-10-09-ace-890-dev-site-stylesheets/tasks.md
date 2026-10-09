## 1. Development site

- [x] 1.1 Move the five SCSS sources into partials of `packages-dev/dev-site`
      with one entry, build, compare the rules with the removed files
- [x] 1.2 `Stylesheet.typoscript`, the set `fgtclb/academics-dev-site-stylesheet`,
      the split of the page object, and the set in both instance site
      configurations
- [x] 1.3 `LegacyDeliveryTest::bothTreesLinkTheStylesheetOfTheDevelopmentInstancesOnce()`,
      shown red without the set in the site configuration and without the
      import in the static template

## 2. Extensions

- [x] 2.1 Remove the stylesheets, their sources and every `f:asset.css` of them
- [x] 2.2 Remove `plugin.tx_academicstudyplan.assets.css` from the settings
      definitions, the constants and the content object, and check the other
      extensions for a similar switch (none)
- [x] 2.3 Adapt the study plan tests and add the no-stylesheet tests of the
      partner map, the public profile, the profile lists and the profile
      editor, shown red with the previous templates
- [x] 2.4 `StylesheetClassesTest` (study plan, public profile, profile lists,
      map against the Leaflet stylesheets) and `StudyPlanControlIconRulesTest`
      in `packages-dev/dev-site`, shown red with a class no view renders, the
      list template of the base and a renamed icon rule

## 3. Definition of done

- [x] 3.1 `lintPhp`, `cgl -n`, `phpstan`, `unit` and `functional` on SQLite for
      v13 and v14, each after its own `composerUpdate`
- [x] 3.2 `buildJs`, `checkJsBuildClean`, `lintMarkdown -n`,
      `checkRstRenderingSingle` for the four extensions
- [x] 3.3 Verified in both DDEV instances, `/` and `/legacy/`
- [x] 3.4 `docs/`, the four manuals and a Breaking changelog entry per
      extension, and the older 3.0 entries that named the removed stylesheet
      or setting
- [x] 3.5 Commit message in TYPO3 Core format, `Resolves: ACE-890`
