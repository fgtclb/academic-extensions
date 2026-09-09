# Dates

Three extensions store and render dates, and until 3.0.0 each of them spelled
its own format. This page is what they share since ACE-552: where a date lives,
who decides how much of it a visitor sees, and the one thing about a native date
control that no amount of configuration can change.

## The one browser fact everything else follows from

**The display format of `<input type="date">` follows the browser or operating
system locale, and a page cannot influence it.** There is no attribute, no
stylesheet and no script that changes it; WebKit's `::-webkit-datetime-edit*`
pseudo-elements restyle the sub-fields but cannot reorder them. The DOM value is
always `yyyy-mm-dd` regardless of what is drawn.

So the site language cannot drive the picker. It drives everything the *server*
renders instead — the public profile, the editor's compact rows, the display
values of the JSON responses and the field hints. That split is deliberate and
is the reason the editor's date fields were a text control until 3.0.0: the
decision then was to wait for a picker of our own. The decision now is that the
browser's calendar is worth more than a matching notation, and that the
notation is recovered everywhere else.

Two consequences worth knowing before reaching for a control:

- `<input type="month">` exists and submits `YYYY-MM`, but **desktop Firefox has
  never implemented it** and degrades it to a plain text field. That is
  accepted rather than worked around — the submitted value is identical and the
  server validates it either way. The alternative is a hand-written month
  picker, which is the library this design avoids.
- **There is no native year-only input** in any browser (`whatwg/html#3281` is
  open). A year is therefore an `<input type="number">` with `min`, `max` and
  `step`, which is also what the timeline years were before they became dates.

## The primitives

`packages/fgtclb/academic-base/Classes/Date/`, namespace
`FGTCLB\AcademicBase\Date`, all `@internal`:

| Class                    | Role                                                                             |
|--------------------------|----------------------------------------------------------------------------------|
| `DateDisplay`            | Which parts a visitor sees; produces the ICU skeleton. Value object              |
| `DateGranularity`        | `DATE`, `MONTH`, `YEAR` — the control type and the exchange format               |
| `DateCompletionEdge`     | `FIRST`, `LAST`                                                                  |
| `DateCompletion`         | The month and day edges; completes a partial date                                |
| `DateFieldSettings`      | The display, the granularity and the completion, as one field's configuration    |
| `LocalizedDateFormatter` | `\DateTimeInterface` + `DateDisplay` + `Locale` → string. Stateless service      |
| `DateValueParser`        | A submitted string + granularity + completion → `?\DateTimeImmutable`. Stateless |

They live in `academic_base` because `academic_persons`, `academic_persons_edit`
and `academic_jobs` all require it, and the jobs extension needs the formatter
without needing the persons extension.

The value objects are `#[Exclude]`d and carry a `__set_state()`, like every
other settings value object, because the persons settings graph is cached as a
`var_export()`.

## Display is locale-driven, and never a format string

`DateDisplay` names the parts; the parts become an ICU skeleton; the skeleton
becomes the locale's own pattern through `\IntlDatePatternGenerator`. Nothing in
this repository writes a date format for output any more. Verified for the 14th
of March 2019:

| Parts            | Skeleton     | `de-DE`      | `en-US`        |
|------------------|--------------|--------------|----------------|
| year, month, day | `MEDIUMDATE` | `14.03.2019` | `Mar 14, 2019` |
| year, month      | `yMMM`       | `März 2019`  | `Mar 2019`     |
| year             | `y`          | `2019`       | `2019`         |
| month, day       | `MMMd`       | `14. März`   | `Mar 14`       |

A complete date deliberately uses the core keyword `MEDIUMDATE` rather than the
`yMMMd` skeleton. `MEDIUMDATE` is what the contract rows and the editor already
rendered, and the skeleton would move `14.03.2019` to `14. März 2019` on every
existing installation. **A change to that branch is a change to every fully
published date**, so it is pinned by a test.

`ext-intl` is an unconditional requirement of `typo3/cms-core` on v13 and v14
and is loaded in every `ghcr.io/typo3/core-testing-php8*` image, so no
availability guard is written anywhere.

## Input granularity, and completing what was not asked for

`DateGranularity` decides the control and the format in one place:

| Granularity | Control                                  | Exchanges    |
|-------------|------------------------------------------|--------------|
| `DATE`      | `<input type="date">`                    | `2019-03-14` |
| `MONTH`     | `<input type="month">`                   | `2019-03`    |
| `YEAR`      | `<input type="number">` with `min`/`max` | `2019`       |

`DateCompletion` fills the parts the editor was not asked for, on two axes:
`FIRST` or `LAST` month of the year, `FIRST` or `LAST` day of the month. Those
two axes are the four rules an integrator can name. The trap they hide is that
a last day is the last day of the **resulting** month, so a last day of a last
month of 2019 is the 31st of December and never the 31st of January; a unit
test pins exactly that, and the leap February next to it.

A part the editor did give is never completed over, and a complete date is
never touched.

## Reading a submitted value

`DateValueParser` accepts the granularity's own format, always the full ISO
format, and the German `d.m.Y` notation at the full granularity — the last two
so that a client written against the endpoint before the control changed keeps
working.

Every format is read strictly. `createFromFormat()` reads `2019-02-31` as the
third of March and `32.01.2026` as the first of February, so the parsed date is
formatted back and compared with what came in, and anything that does not match
is refused. A caller that means "clear the field" checks for an empty value
before asking: the parser answers `null` for an empty string as for an invalid
one, because an empty string is not a date either.

## Who carries the configuration

`FGTCLB\AcademicBase\Settings\Validation` carries a `DateFieldSettings`, **never
null** — a field that is not a date carries the neutral default. So every
consumer that already resolves a `Validation` by property name resolves the date
configuration with it, and no consumer reaches into the raw settings array.

Turning the field type `date` into the concrete input type of its granularity
happens in **two** places, one on each side of the Fluid boundary:
`Validation::getControlInputType()`, which `Control.html` reads for the
server-rendered control, and `inputTypeOf()` in `field-clone.ts`, which the
prototype filler reads for the cloned one. The TypeScript one additionally maps
an empty type to `text`, a case no `Validation` produces.

The two agree today and **nothing enforces that they keep agreeing** — the one
control partial keeps the markup from drifting apart, not the mapping. A change
to one of them is a change to both, and each side has its own tests: the
rendered control in the functional plugin tests, the clone in
`Tests/JavaScript/`.

The vocabulary an integrator writes is documented in
[Validation settings](validation-settings.md); this page is about the machinery
below it.

## Rendering without naming a format

Two ViewHelpers, both `@internal`:

- `FGTCLB\AcademicBase\ViewHelpers\Format\LocalizedDateViewHelper` —
  `<b:format.localizedDate date="{job.employmentStartDate}"/>`, with optional
  `year`, `month` and `day` arguments. It resolves the site language's locale
  exactly as TYPO3 core's own `f:format.date` does, and falls back to the system
  locale when there is no request or no matched language, which is every backend
  rendering.
- `FGTCLB\AcademicPersons\ViewHelpers\Format\ProfileInformationDateViewHelper`
  extends it and resolves the display configuration itself, from the record type
  and the property name. That is why the public profile and the editor's compact
  rows cannot drift apart, and why no timeline template names a format or knows
  which parts its section publishes.

## Storing a date

A date column is `date DEFAULT NULL` in `ext_tables.sql` with

```php
'config' => [
    'dbType' => 'date',
    'type' => 'datetime',
    'format' => 'date',
    'nullable' => true,
    'default' => null,
],
```

`dbType => 'date'` keeps a native SQL `DATE`, so no timestamp and no timezone
conversion is involved, and the Extbase property is `?\DateTime`. It is
identical on TYPO3 v13 and v14 and needs no version switch.
`academic_jobs`'s `employment_start_date` had this shape before ACE-552 and is
the reason it was chosen for the timeline columns rather than invented.

**A required date is still a nullable column.** The requirement is a validator,
not a schema constraint, which is how every other required field in these
extensions works — the backend, the frontend editor and an import all reach the
same refusal, and a column that a legacy row left empty does not block a
deployment.

## See also

- [Validation settings](validation-settings.md) — the YAML vocabulary a date
  field is configured with, and the two consumers that read it.
- [The profile editing contract](profile-editing-contract.md) — how a date field
  descriptor reaches the browser and what the editor does with it.
- [Class design](class-design.md) — the value object conventions the seven
  classes above follow.
- `packages/fgtclb/academic-base/Tests/Unit/Date/` — the unit tests that pin
  every table on this page.
