## Context

See `proposal.md` for the motivation. On `main`:

- `Service/ProgramFactsBuilder` (`final readonly`, no constructor) builds the
  facts of the three places from a `ProgramFactsSourceInterface`. Its callers
  are the `program-data` processor (page), `DetailsController::showAction()`
  (details element) and `<ace:program.facts>` (card). The built-in facts are
  `creditPoints`, `jobProfile`, `performanceScope` and `prerequisites`, read
  through the getters of the interface.
- `Domain/Model/ProgramFact` is `final readonly` with a private constructor
  and the named constructors `forCategoryType()` and `forBuiltIn()`.
- `Partials/Program/Facts/Item.html` prints the value of every built-in fact
  with `f:format.raw()`.
- The three text facts read the `pages` columns `job_profile`,
  `performance_scope` and `prerequisites`, which
  `Configuration/TCA/Overrides/pages.php` declares as `type => text` with
  `enableRichtext => true`. `credit_points` is a number. All four belong to the
  program page type, doktype 20 (`PageTypes::TYPE_ACADEMIC_PROGRAM`), which the
  same file registers with a `types` entry and `columnsOverrides` of its own.
  Every program the facts are built for is a page of that doktype: the list
  repository constrains on it and the page object block of the processor is
  conditioned on it.
- A functional probe on TYPO3 13.4.35 and 14.3.7 (2026-10-07) showed that
  `TcaSchemaFactory::get('pages')->getSubSchema('20')->getField(<column>)`
  returns a `TextFieldType` whose `isRichText()` is true for the three text
  columns and false for `credit_points` (a `NumberFieldType`), and that both a
  `columnsOverrides` of the program page type switching `enableRichtext` off
  and a change of the base column are honoured after `rebuild()`, and so is
  a base column switched off with a `columnsOverrides` of the program page
  type switching it on again.
- `TcaSchemaFactory` is already injected into nine classes of
  `academic_persons` and `academic_persons_edit` on `main`.
- Pull request #850 (ACE-818) puts
  `class="{f:if(condition: '{fact.identifier} == \'prerequisites\' || …')}"`
  on the value `span` of the same partial.

## Goals / Non-Goals

**Goals:**

- One place decides whether a fact is rich text, and it reads the decision
  from the configuration a project already changes to switch the editor.
- The partial asks the fact and holds no list of identifiers.

**Non-Goals:**

- A rich text flag on `ProgramFactsSourceInterface` or on the two classes that
  implement it. The interface is `@api` and describes values, not their
  editing.
- Any change to the markup of the facts beyond the class and the escaping, see
  the proposal.

## Decisions

### The fact carries `isRichText`

`ProgramFact` gets a public `bool $isRichText`, next to `isCategoryType`.
`forBuiltIn()` takes it as an optional last argument, `false` by default, and
`forCategoryType()` sets it to `false`. The template asks
`{fact.isRichText}`, which reads like the existing `{fact.isCategoryType}`.

A fact is a value object handed to a template, and the template is where the
answer is needed. Deciding in the partial, by identifier as #850 does or by a
ViewHelper that reads TCA, would put configuration lookups into Fluid and
would have to be repeated by every override of the partial.

### The builder reads the TCA schema of the program page type

`ProgramFactsBuilder` gets `TcaSchemaFactory` injected through its constructor
(autowired, the class stays `final readonly` and holds only that reference).
It owns a private map from fact identifier to column:

| Fact               | Column              |
|--------------------|---------------------|
| `jobProfile`       | `job_profile`       |
| `performanceScope` | `performance_scope` |
| `prerequisites`    | `prerequisites`     |

For a text fact it takes the schema of `pages`, the sub-schema of the program
page type when the schema has one and that sub-schema has the column, and the
base schema otherwise, and calls the fact rich text when that schema has the
column and the field is a `TextFieldType` whose `isRichText()` is true.
Anything else, a missing table, a column removed from `pages` or another field
type, is not rich text. Credit points and category type facts never ask.

The lookup happens in `builtInFact()`, on every build. `TcaSchemaFactory`
keeps its schemata in memory and in the core cache, so the lookup is an array
access per text fact, and the builder keeps nothing between calls.

Rejected:

- **A fixed map in the builder** (`jobProfile => true`, …). It moves the
  identifier list of #850 from the template into PHP and still ignores a
  project that switches the editor off, which is the defect this change
  fixes.
- **Reading `$GLOBALS['TCA']['pages']`** and merging the `columnsOverrides` of
  doktype 20 into the base column by hand. It duplicates the merge the schema
  factory already does, with the edge cases of a nested `config` array, and
  reads the global array the schema API was introduced to wrap. `academic_base`
  `Tca/TableConfiguration` carries the note to move to `TcaSchemaFactory` once
  TYPO3 v12 is gone, which it is on `main`.
- **A flag per fact in the site settings.** A second place to say what the TCA
  already says, and one that drifts from it.

### The sub-schema is chosen by the program page type

The builder asks for the sub-schema `(string)PageTypes::TYPE_ACADEMIC_PROGRAM`,
not for the doktype of the source. The interface does not expose a doktype,
and every source is a program page by construction: the list repository
constrains on doktype 20 and the page object block of the processor is
conditioned on it (see Context).

When `pages` has no sub-schema for the program page type, which happens only
when a project removes the type entry, the base schema decides. The base
column is what FormEngine would then show, so the output still matches the
backend. A sub-schema holds only the fields its `showitem` names, palettes
included, so a field a project leaves out of the program page form is read from
the base column too. A value stored while the field had the editor then still
renders as HTML.

### Rendering in the partial

A rich text fact renders its value in `<span class="ce-bodytext">` with
`f:format.raw()`. Every other fact keeps the plain `<span>` and renders in two
branches: categories as today, any other value with `f:format.nl2br()`, whose
children are escaped before the line breaks are added. Fluid's
`Nl2brViewHelper` sets `escapeOutput = false` and leaves `escapeChildren`
unset, which Fluid reads as "escape the children", on v13 and v14 alike.
Credit points take the escaped branch, which changes nothing for an integer.

Escaping a text field without the editor is the correct output, not a side
effect. Such a field is a plain textarea in the backend: what the editor types
is text, and an ampersand or an angle bracket is meant literally. Printing it
raw lets an editor of a program page inject markup into the frontend that the
backend form never presented as markup. A project that switched the editor
off and relied on stored HTML being interpreted has stored HTML in a field
that does not offer it, and either switches the editor on again or overrides
the partial. The `Feature` changelog entry says so.

The partial chooses between two `span` elements rather than computing the
class attribute inline: an inline `f:if` leaves an empty `class=""` on every
fact that is not rich text, as #850 renders it, and markup without meaning
is not wanted in the output. The value element of those facts stays the plain
`<span>` it was before.

### Relation to pull request #850 (ACE-818)

The two changes touch the same `span` and do not depend on each other.

- If this change is merged first, the partial already carries the class from
  the flag. #850 drops its hunk of `Program/Facts/Item.html` on rebase, or
  keeps only its own class changes on the `li`.
- If #850 is merged first, task 3.1 replaces its identifier condition with
  `fact.isRichText`.

The implementation does not wait for #850. When it is merged while #850 is
still open, the author of #850 is told in the pull request to drop the
identifier condition.

### DI and core versions

The builder is autowired by `Configuration/Services.yaml` of
`academic_programs`, like today, and needs no attribute: the constructor
argument is a concrete core class. The three callers receive the builder from
the container already, so none of them changes, and the unit test constructs
the builder with a `TcaSchemaFactory` double.

The schema API is the same on v13 and v14 for every call used here (`has()`,
`get()`, `hasSubSchema()`, `getSubSchema()`, `hasField()`, `getField()`,
`TextFieldType::isRichText()`), so there is no version switch.

## Risks / Trade-offs

- [On TYPO3 v13, `TcaSchemaFactory`, `TcaSchema` and `TextFieldType` carry
  `@internal This is an experimental implementation and might change until
  TYPO3 v13 LTS`, still in 13.4.35] → v13 has been LTS since 13.4, the API is
  public on v14 in the same shape, and `main` injects `TcaSchemaFactory` into
  nine classes already. A v13 patch level that changes it would break those
  first, and the functional tests of this change run against the v13 floor.
- [A project overrides `Program/Facts/Item.html` and keeps printing the value
  raw] → It keeps today's output and gets no class. The changelog entry shows
  the branch to adopt.
- [A project switched the editor off and stored HTML in the field anyway] →
  The HTML is now shown as text. Named as the intended change in the
  changelog, with the two ways out.
- [A translated program page] → The TCA of `pages` is the same for every
  language, so a translation gets the flag of its default language page.
