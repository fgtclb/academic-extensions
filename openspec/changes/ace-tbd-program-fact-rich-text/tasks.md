## 1. Verify the premises

- [ ] 1.1 On `main` HEAD, re-run the schema probe of `design.md` as a
  functional test on both core versions: the program page sub-schema of
  `pages` reports the three text columns as rich text and `credit_points` as
  not, a `columnsOverrides` of doktype 20 that switches `enableRichtext` off
  and a change of the base column are both honoured, and a base column
  switched off with a `columnsOverrides` switching it on again reports rich
  text. If any of it does not hold, stop and update this change.
- [ ] 1.2 Check the state of pull request #850 (ACE-818). If it is merged, task
  3.1 replaces its identifier condition. If it is open, leave its lines alone.

## 2. The fact and the builder

- [ ] 2.1 Add `public bool $isRichText` to `Domain/Model/ProgramFact`, as an
  optional last argument of `forBuiltIn()` (default `false`) and `false` in
  `forCategoryType()`. Update the class docblock.
- [ ] 2.2 Inject `TcaSchemaFactory` into `Service/ProgramFactsBuilder`, add the
  identifier to column map of the three text facts, and decide `isRichText` per
  build from the program page sub-schema of `pages`, the base schema when that
  sub-schema is missing. A missing table, a missing column or another field
  type is not rich text. Credit points pass no flag. Update the class
  docblock.
- [ ] 2.3 Give `Tests/Unit/Service/ProgramFactsBuilderTest.php` a
  `TcaSchemaFactory` double so the existing list cases keep running
  unchanged, and confirm the unit suite is green on both core versions.

## 3. The partial

- [ ] 3.1 In `Partials/Program/Facts/Item.html`, give the value `span`
  `class="{f:if(condition: fact.isRichText, then: 'ce-bodytext')}"`, render a
  rich text value with `f:format.raw()` and any other built-in value with
  `f:format.nl2br()`. Rewrite the `f:comment` of the partial to say what
  `isRichText` decides. If #850 is merged, this replaces its identifier
  condition.

## 4. Tests

- [ ] 4.1 Functional builder tests in `Tests/Functional/Facts/`, on both core
  versions, with a program page that has all four built-in values and a
  category:
  - job profile, performance scope and prerequisites are rich text, credit
    points and the category type fact are not
  - a `columnsOverrides` of the program page type switching the editor off for
    one field makes that fact not rich text and leaves the others
  - a base column switched off makes the fact not rich text
  - a base column switched off and switched on again for the program page type
    keeps the fact rich text
  - the data processor path (program page) and the controller path (details
    element) both hand the flag to the template, asserted on the rendered
    class.

  The rendering cases take their TCA changes from a fixture extension's
  `Configuration/TCA/Overrides/`, so the frontend request builds the schema
  the way a project's site package does. The builder cases may change
  `$GLOBALS['TCA']` and `rebuild()` the schema factory, as the probe did. Show the switched off cases to fail by
  returning `true` for every text fact in the builder, and the flag cases to
  fail by dropping the argument in `builtInFact()`.
- [ ] 4.2 Functional rendering tests, extending `ProgramFactsTest` or in a class
  of their own, on both core versions:
  - the value of a rich text fact carries `ce-bodytext` and renders the stored
    HTML on the page, in the details element and on a card listing
    `prerequisites`
  - the degree and credit points values carry no `ce-bodytext`
  - with the editor switched off for the program page type, a value
    `Research & teaching` and `<b>Industry</b>` on two lines renders as
    `Research &amp; teaching<br />` and `&lt;b&gt;Industry&lt;/b&gt;`, without
    the class.

  Show the class assertions to fail by restoring the plain `<span>`, and the
  escaping assertion to fail by restoring `f:format.raw()` for every value.
- [ ] 4.3 Check that the existing "rich text raw" assertions of
  `ProgramFactsTest` still hold unchanged, which proves the shipped
  configuration renders as before apart from the class.

## 5. Documentation

- [ ] 5.1 `Documentation/Configuration/Index.rst` of `academic_programs`: the
  facts partial table and the list of `{fact}` properties name `isRichText`,
  the class and the escaping of a field without the editor.
- [ ] 5.2 `Documentation/Changelog/3.0/Feature-ProgramFactsKnowRichText.rst`
  from the template in `Build/Documentation/Templates/`: the flag, the class,
  the escaping as an intended change for a project that switched the editor
  off with its two ways out, and the branch an override of
  `Program/Facts/Item.html` adopts.
- [ ] 5.3 `docs/architecture/program-facts.md`: replace the paragraph that says
  a program field value is rendered raw, describe where the flag comes from
  and why the TCA schema, and extend the Tests section.

## 6. File the issue

- [ ] 6.1 File the ACE issue (Story, Version 3.0.0, subtask of ACE-10), relate
  it to ACE-818 and ACE-733, and rename the change to
  `ace-<NNN>-program-fact-rich-text`.
- [ ] 6.2 When #850 is still open after the merge, ask its author in #850 to
  drop the identifier condition of `Program/Facts/Item.html` on rebase.

## 7. Definition of done

- [ ] 7.1 After `composerUpdate` for TYPO3 v13: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` green.
- [ ] 7.2 After `composerUpdate` for TYPO3 v14: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` green.
- [ ] 7.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 7.4 `docs/` is updated, and `README.md` and `CONTRIBUTING.md` still
  only summarize.
- [ ] 7.5 Commit as `[FEATURE] ACE-<NNN>: Mark rich text program facts`
  in TYPO3 Core format, and archive the change as the last commit of the pull
  request.
