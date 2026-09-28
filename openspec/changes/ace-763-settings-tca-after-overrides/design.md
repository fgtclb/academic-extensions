## Context

See `proposal.md`. On `main` the six person TCA files call
`GeneralUtility::makeInstance(AcademicPersonsSettings::class)` and merge the
validation of their section with `TcaValidationMerger`, each under the same
`@todo` that the call belongs elsewhere. `TcaFactory::create()` loads the TCA
files, dispatches `BeforeTcaOverridesEvent`, runs the overrides, the TCA
migration and preparation, and dispatches `AfterTcaCompilationEvent` last. The
result is written to the `tca_base` entry of the core cache, and
`TcaSchemaFactory` writes its own `TcaSchema` entry from it. Both cores do this
the same way.

## Goals / Non-Goals

**Goals:** the settings reach the TCA after every override, in the cached
state, on both cores. **Non-Goals:** see `proposal.md`.

## Decisions

### A listener of `AfterTcaCompilationEvent`

`ApplySettingsToTca`, TYPO3's `#[AsEventListener]` with the identifier
`academic-persons/apply-settings-to-tca`. It runs the same five merges and the
`types` fragment of the profile information table on the compiled TCA and
leaves out the `disabled` column of the profile, as the TCA file did.

Rejected: `BootCompletedEvent`, which comes after both caches are written, so a
change there would be lost or rebuilt on every request. Rejected:
`BeforeTcaOverridesEvent`, which keeps today's order and still sees no project
column. Rejected: an override file of `academic_persons`, which runs among the
overrides of every other package in package order.

### The settings win over the overrides (decided, Breaking)

Every fragment writes `readOnly` and `required`, also when false, so a
fragment can take a flag away from a column whose TCA file sets it. Kept as it
is: the settings are the single source of both flags, and a column replaced by
an override keeps them. A site package that has to differ in the backend
orders a listener of its own after the identifier. Rejected: keeping the
shipped columns in `BeforeTcaOverridesEvent` and merging only project columns
later. No break, but two merge points, and replaced columns keep losing their
flags.

### Only columns the TCA has

A validation whose column the compiled TCA does not have is left out of the
merge. The TCA files merged it and created a `columns.<field>.config`
fragment without a `type`, which made the TCA migration stop the whole TCA
build with `Missing "type" in TCA of field`. The project field change adds a
notice on top of this for a declared project column (`custom: true`) only. A
regular field without a column stays silently left out.

### The e-mail soft reference

`TcaPreparation::configureEmailSoftReferences()` sets `softref =
email[subst]` on every `email` column, before the event. The e-mail column of
the contact records is `input` in its TCA file and becomes `email` through the
settings, so the listener sets the soft reference on every `email` column of
the five tables with flags on their columns. The profile information table
carries its flags per record type, where the core sets none either. Rejected:
running the whole preparation again on the six tables, which touches file and
category columns that are already prepared.

### Ordered after EXT:content_blocks

`after: 'content-blocks-tca'`. That listener of content_blocks 1.6.5 (v13) and
2.4.10 (v14) builds its TCA in `BeforeTcaOverridesEvent` and is earlier anyway. An
ordering that names no listener of the event is dropped by
`DependencyOrderingService::orderByDependencies()`, which keeps only registered
items, so the extension is neither required nor suggested. A fixture listener
under that identifier proves the ordering.

## Risks / Trade-offs

- [A site package relied on its override of `required` or `readOnly`] →
  Breaking changelog entry with the migration, the identifier to order after,
  and a paragraph in the upgrade guide. The identifier becomes public API on
  the extension points page of `academic_base`, the class stays internal.
- [The TCA checks of the install tool build the TCA with
  `TcaFactory::createNotMigrated()`, which dispatches no
  `AfterTcaCompilationEvent`] → They see the TCA without the settings flags.
  No TCA migration touches those keys, so they report nothing different. The
  install tool itself builds the TCA through the regular container, listener
  included.
- [The TCA migration does not see the fragments any more] → They only carry
  `readOnly`, `required`, `minitems` and the `email` and `number` types, all in
  their current form.

## Open Questions

None.
