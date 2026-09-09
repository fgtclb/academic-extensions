## Context

Three integer year columns are replaced by SQL date columns, and the frontend
editor gets the native date control that was deferred when it was rewritten.
The deferral is recorded in three places that all have to be revised together —
`docs/architecture/profile-editing-contract.md`, the `Control.html` comment and
`field-clone.ts` — because the reason given there was that the native control
follows the browser's locale. It still does. What changes is that the site
locale now governs everything the *server* renders, and the picker is accepted
for what it is: the browser's calendar, in the browser's notation.

## Decisions

### The columns are renamed, not only retyped

`year`, `year_start` and `year_end` become `date`, `date_start` and `date_end`,
`date DEFAULT NULL`, with the model properties `date`, `dateStart` and
`dateEnd` typed `?\DateTime`. The closed pull request #520 kept the old names
and only changed their type; a column called `year` holding 14 March 2019 is a
trap for every reader after us, and 3.0.0 is unreleased, so the rename costs
nothing that the retype does not cost already.

TCA follows the shape `academic_jobs` already ships for
`employment_start_date`, which is the only date column in the monorepo:

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
conversion enters the picture. Identical on v13 and v14; no version switch.

**Rejected:** a `year_only` column, as #520 had it. It is presentation, not
data — two records with the same date would render differently depending on a
flag an editor could toggle by accident, and the same decision would have to be
taken again for every new field. It is configuration instead.

### Display granularity is configuration, and the locale does the formatting

A date field declares which parts a visitor sees:

```yaml
dates:
  date:
    display: { year: true, month: false, day: false }
```

The parts become an ICU skeleton, and the skeleton becomes the locale's own
pattern through `\IntlDatePatternGenerator::getBestPattern()`. Verified output
for 14 March 2019:

| Parts            | Skeleton   | `de-DE`      | `en-US`        |
|------------------|------------|--------------|----------------|
| year, month, day | MEDIUMDATE | `14.03.2019` | `Mar 14, 2019` |
| year, month      | `yMMM`     | `März 2019`  | `Mar 2019`     |
| year             | `y`        | `2019`       | `2019`         |
| month, day       | `MMMd`     | `14. März`   | `Mar 14`       |

A complete date deliberately uses the core keyword `MEDIUMDATE` rather than the
`yMMMd` skeleton `DateDisplay` builds for all three parts: `MEDIUMDATE` is what
the contract rows and the editor already render, and the skeleton would move
`14.03.2019` to `14. März 2019` on every existing installation. `ext-intl` is
an unconditional requirement of `typo3/cms-core` on both supported versions and
is loaded in every `ghcr.io/typo3/core-testing-php8*` image, so no availability
guard is written.

**Rejected:** deriving the display from the input granularity. An installation
that asks its editors for a full date but publishes the year alone is exactly
the case the dropped `year_only` flag existed for, and it must stay expressible.

### Input granularity, and how the missing parts are filled

```yaml
    input:
      granularity: month      # date (default) | month | year
      completeMonth: first    # first | last  — the first/last month of the year
      completeDay: last       # first | last  — the first/last day of the month
```

| Granularity | Control                                  | Submits |
|-------------|------------------------------------------|---------|
| `date`      | `<input type="date">`                    | `Y-m-d` |
| `month`     | `<input type="month">`                   | `Y-m`   |
| `year`      | `<input type="number" min max step="1">` | `Y`     |

There is no native year-only input (whatwg/html#3281 is open), so a year is a
number field — the same control the timeline years have today, which keeps that
case visually unchanged.

**Desktop Firefox has never implemented `<input type="month">`** and degrades
it to a plain text field. That is acceptable and documented rather than worked
around: the value it submits is the same `2019-03`, the server validates it
either way, and the alternative is shipping a hand-written month picker, which
is the library this change exists to avoid.

`completeMonth` and `completeDay` express the four rules by their two axes:
`completeMonth: first` is the first month of the year, `completeDay: last` the
last day of the month. Both default to `first`. They apply only to the parts
the editor was not asked for; a full date is never completed.

### The site locale never reaches the picker, and that is a browser fact

The display format of `<input type="date">` follows the browser or operating
system locale. No attribute, stylesheet or script changes it; WebKit's
`::-webkit-datetime-edit*` pseudo-elements restyle the sub-fields but cannot
reorder them. The DOM value is always `yyyy-mm-dd` regardless. So the site
language governs the read-only display, the row summaries and the hint — and
the picker shows what the visitor's own browser shows it in.

The editor is told when it publishes less than it enters: a field whose display
drops a part carries a hint saying which part reaches the visitor. That is the
"mention as a hint in the edit" the deferred `year_only` flag would have needed
anyway.

### The primitives live in `academic_base`

`FGTCLB\AcademicBase\Date\` gains the neutral pieces, because
`academic_persons`, `academic_persons_edit` and `academic_jobs` all require
`fgtclb/academic-base`:

| Class                    | Role                                                                               |
|--------------------------|------------------------------------------------------------------------------------|
| `DateDisplay`            | Which parts are shown; produces the skeleton. Value object                         |
| `DateGranularity`        | `DATE`, `MONTH`, `YEAR`; the control type and the submit format                    |
| `DateCompletionEdge`     | `FIRST`, `LAST`                                                                    |
| `DateCompletion`         | The month and day edges; completes a partial date                                  |
| `DateFieldSettings`      | The display, the granularity and the completion, as one field's date configuration |
| `LocalizedDateFormatter` | `\DateTimeInterface` + `DateDisplay` + `Locale` → string. Stateless                |
| `DateValueParser`        | A submitted string + granularity + completion → `?\DateTimeImmutable`. Stateless   |

The value objects are `#[Exclude]`d and carry `__set_state()`, like every other
settings value object, because the persons settings graph is cached as a
`var_export`. The two services are stateless and wired by attribute.

`DateValueParser` keeps the existing strictness: `createFromFormat('!'.$format)`
plus a round trip comparison, so `2019-02-31` is refused rather than read as
3 March. It accepts the granularity's own format and, always, the full ISO
format, so a client written against the current endpoint is not broken.

**Rejected:** putting them in `academic_persons`. `academic_jobs` needs the
formatter and does not depend on the persons extension.

### The settings graph carries the date configuration on `Validation`

`Validation` already carries `inputType`, which is where `date` arrives today.
It gains a `DateFieldSettings`, never null — a neutral default for a field that
is not a date — so every consumer that already resolves a `Validation` by
property name resolves the date configuration with it, and no consumer reaches
into the raw array.

The vocabulary follows the record: the document row field and validator key
`year` becomes `date`, and `from` and `to` alias `dateStart` and `dateEnd`. The
`dates:` map of a document section is keyed like the existing `helptext:` map;
a contract or profile field carries a `date:` block, like its scalar
`helptext:`. No new shape is invented.

### `academic_jobs` is repaired, not extended

Its form renders a native `<input type="date">` and prefills it with
`f:format.date(format:'d.m.Y')`. A browser discards a value that is not
`yyyy-mm-dd`, so **every stored job date renders empty in the edit form today**
and is submitted back as empty. The prefill becomes `Y-m-d`, and the dead
`data-render="datepicker"` attribute — which has no consumer anywhere in the
repository — goes. Job dates shown to a visitor are formatted for the site
locale through the same formatter.

Its settings system is a second, un-normalised implementation that ACE-508 is
scheduled to replace, so the granularity vocabulary is **not** duplicated into
it. Writing it twice to delete one copy is the wrong trade.

### The migration is a starting point, not a wizard

The extension ships `AbstractMigrateProfileInformationDatesUpgradeWizard`:
`abstract readonly`, implementing `UpgradeWizardInterface` and
`RepeatableInterface`, and deliberately **without** an `#[UpgradeWizard]`
attribute, so nothing registers it and no installation is offered a migration
that invents data. A project subclasses it, adds the attribute, and chooses the
completion rules through the constructor:

```php
#[UpgradeWizard('myProject_migrateProfileInformationDates')]
final readonly class MigrateDates extends AbstractMigrateProfileInformationDatesUpgradeWizard
{
    public function __construct(ConnectionPool $connectionPool)
    {
        parent::__construct(
            $connectionPool,
            // A start year becomes the 1st of January.
            new DateCompletion(),
            // An end year becomes the 31st of December.
            new DateCompletion(DateCompletionEdge::LAST, DateCompletionEdge::LAST),
        );
    }
}
```

An `abstract readonly class` with constructor-promoted defaults, overridden by
a child constructor, is valid on PHP 8.2 — verified in the
`core-testing-php82` image, not assumed. The class is `readonly` and **not**
`final`, so it can be extended.

It reads the old integer columns and writes the new date columns only where the
target is still empty, never overwriting. `TYPO3\CMS\Install\Attribute\UpgradeWizard`
and `TYPO3\CMS\Install\Updates\*` are used, not `TYPO3\CMS\Core\Upgrades\*`:
the latter does not exist on v13 and the migration of the ten existing wizards
is ACE-296.

**Rejected:** a registered wizard. The user's judgement on ACE-365 was that a
wizard which guesses is worse than no wizard, and an integer year genuinely
carries no month and no day. **Rejected too:** shipping nothing at all, as #520
did — the query, the batching and the "only where empty" guard are the same in
every project, and there is no reason for each of them to write it again.

## Risks

- **Data loss on an unprepared upgrade.** The schema analyzer offers to drop
  three columns and add three others. The Breaking entry says so first, and the
  Important entry next to it explains the wizard. Nothing in code can prevent
  an integrator from clicking through.
- **Desktop Firefox has no month control.** Documented; the submitted value is
  identical.
- **The complete-date notation must not move.** `MEDIUMDATE` rather than the
  `yMMMd` skeleton is what keeps it where it is; a test pins it.
