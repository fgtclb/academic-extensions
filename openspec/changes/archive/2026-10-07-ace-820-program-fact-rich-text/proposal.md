## Why

The facts partial of `academic_programs` (`packages/fgtclb/academic-programs`)
prints the value of every program field fact raw, because the three text
fields `job_profile`, `performance_scope` and `prerequisites` ship with the
rich text editor. A project that switches the editor off for one of them still
gets the stored text printed as HTML, unescaped and without its line breaks.

Pull request #850 (ACE-818) marks rich text output with the class
`ce-bodytext` so the content styles of a site apply to it. For the facts it
names the three identifiers in the partial, which repeats in a template what
the TCA of the program page type already says, and is wrong for the same
project. A fact should state whether its value is rich text, and the
partial should only ask.

## What Changes

- A program field fact knows whether its value is rich text. Job profile,
  performance scope and prerequisites are rich text when their field has the
  rich text editor enabled for the program page type, whether the project
  changes the field itself or only the program page type. Credit points and
  category type facts are never rich text.
- The value of a rich text fact carries the class `ce-bodytext` and renders as
  HTML, as today.
- The value of a text fact whose field has no rich text editor renders escaped,
  with its line breaks kept. This changes the output for a project that has
  switched the editor off: stored markup is shown as text instead of being
  interpreted, which is what the backend form let the editor enter.
- The program page, the program details content element and the program card
  behave the same, on TYPO3 v13 and v14 alike.
- The partial override contract gains one property of the fact. An override
  that keeps printing the value raw keeps working as before.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `academic-programs/program-facts`: program field facts render according to
  whether their field is rich text, and rich text facts are marked for the
  content styles.

## Impact

- `academic_programs`: the fact data object, the facts builder (reads the TCA
  schema of `pages` for the program page type), the facts item partial, the
  configuration chapter and a `Feature` changelog entry, `docs/` page on the
  program facts.
- `ProgramFactsSourceInterface` and the three callers of the builder are
  unchanged.
- Coordinates with #850 (ACE-818): whichever lands second uses the fact's rich
  text flag instead of the identifier condition, see `design.md`.

## Non-goals

- Changing the element the value is rendered in. A `span` around block level
  rich text stays as it is, and #850 owns the markup of the facts.
- Running rich text through `lib.parseFunc_RTE`. The facts print the stored
  HTML as today, the program page content does its own processing.
- Rich text for credit points or category titles, or a flag a project sets per
  fact in a setting.
- Backporting to branch `2`: the facts builder and partial exist on `main`
  only (ACE-733).
