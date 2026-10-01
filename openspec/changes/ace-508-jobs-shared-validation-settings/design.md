## Context

`academic_jobs` reads `Configuration/AcademicJobs/Settings.yaml` through
`Classes/Loader/AcademicJobsSettingsLoader.php`, which folds the files of all
active packages with a top-level `array_merge()`, caches the raw array as
`AcademicJobs_Settings` in `cache.core` and memoizes the registry in a
property (listed as non-compliant in `docs/architecture/dependency-injection.md`).
`Classes/Registry/AcademicJobsSettingsRegistry.php` has three readers:
`getValidationsForFrontend()` hands the raw flag lists to the view,
`getValidationsForValidator()` maps `required`, `email` and `url` to Extbase
validators for the final `JobValidator`, and `getValidationsForTca()` has no
caller. The partials `Job/Forms/FieldWrapper.html` and `Textfield.html` call
`j:validation.requiredFromValidation` (the asterisk) and
`j:validation.fieldTypeFromValidation`, which prints the first flag that is
not `required` as the input type. `Classes/ServiceProvider.php` only fetches
the registry on `BootCompletedEvent`, and `composer.json` does not name it, so
TYPO3 never loads it.

`academic_base` ships `SettingsFileLoader` (recursive merge, `null` removes a
key, cached object with `__set_state()`), `ValidationNormalizer`,
`Validation`, `ValidationSet`, `TcaValidationMerger`, the two validator
exceptions and `ValidationEnsureViewHelper`, all `@internal`.
`academic_persons` uses them, and `ApplySettingsToTca` of persons is the model
for a TCA listener on `AfterTcaCompilationEvent`.

The job TCA already marks `title`, `type`, `employment_type` and
`employment_start_date` as required, `contact_email` is an `email` column and
`contact_phone` an `input` column. `company_name` and `description` are not
required there. `academic_bite_jobs` writes no job records.

## Goals / Non-Goals

**Goals:**

- One reading of the jobs settings file, shared by the new-job form, the
  server-side check and the backend record editor.
- The same file syntax as today: `validations.job.<property>: [flags]`, so an
  existing override file still parses.

**Non-Goals:**

- ACE-429's `0` for job type and employment type.
- Rendering `readonly` or `disabled` fields as locked in the new-job form.
- Moving the validation engine of `academic_persons_edit` to `academic_base`.

## Decisions

### A settings object of jobs, built by a stateless factory

`FGTCLB\AcademicJobs\Settings\AcademicJobsSettings` (final, `__set_state()`,
registered in `Services.yaml` through the factory, like
`AcademicPersonsSettings`) holds the normalised sets and answers
`getValidationSet('job')`, an empty set with that identifier for an unknown
one. `AcademicJobsSettingsFactory::get()` calls
`SettingsFileLoader::load()` with the existing file path, the cache identifier
`AcademicJobs_Settings_v3` and `ValidationNormalizer::normalizeValidationSets()`
over `validations`. `Services.yaml` registers the object as a public service
through the factory, as the registry is registered today and as persons does.
The new identifier keeps a raw array cached by the old loader from being read.

Rejected: registering the base `ValidationSet` itself as a jobs service. It is
`#[Exclude]`d, and a service id of another extension's value object would
hide where the configuration comes from.

### The form reads `Validation` objects

`newAction()` assigns `validations` as the `Validation` map of the `job` set,
still after `ModifyPluginViewEvent`. `FieldWrapper.html` and `Textfield.html`
resolve the field with `p:validationEnsure` of `academic_base` and read
`required` and `inputType`. Fluid 4.6 and 5.3 both read public properties.
The markup stays as it is: the asterisk, no `required` attribute.

Rejected: keeping the jobs ViewHelpers on the new objects. Decided by the
maintainer: everything jobs-only goes in 3.0, with a Breaking entry.

### `JobValidator` runs the validators of the set

`JobValidator` takes `AcademicJobsSettings`, runs the `validatorClassNames` of
every `Validation` of the `job` set against the property of that name, and
throws the exceptions of `academic_base` with the existing codes 1753702412
and 1753702335. A field the job has no gettable property for is skipped: today
a settings entry like `salary` adds a `NotEmptyValidator` for a value that is
always `null`, and the form refuses every submission. A project that adds the
property, through an XCLASS of the model, gets it checked. The engine of
`academic_persons_edit` stays where it is.

### A TCA listener, after the overrides

`FGTCLB\AcademicJobs\EventListener\ApplySettingsToTca`, `final readonly`,
`#[AsEventListener]` of TYPO3 with the identifier
`academic-jobs/apply-settings-to-tca`, `after: 'content-blocks-tca'`, merges
the `job` set into `tx_academicjobs_domain_model_job` through
`TcaValidationMerger::merge()`, leaving out fields the table does not have,
and adds `email[subst]` to an `email` column the flags created. Decided by the
maintainer over a form-only change. The identifier is public API and goes on
the extension points page of `academic_base`, as the persons one is.

The merger writes `required` and `readOnly` for every configured field, also
when false, so the settings overrule a project TCA override of those keys.
That is the persons behaviour and is documented as Breaking.

Rejected: calling the merger at the end of the TCA file. Every override runs
after it, which is why persons moved away from that place (ACE-763).

### The shipped file

`contactPhone` changes from `number` to `tel`. With the TCA merge, `number`
would turn `contact_phone` into a number column, and the number input already
refuses `+49 30 123`. The comment block is rewritten to the shared vocabulary.

### Removed without aliases

`AcademicJobsSettingsLoader`, `AcademicJobsSettingsRegistry`, both
ViewHelpers, `Exception\UnknownValidatorException`,
`Exception\UnsuitableValidatorException` and `ServiceProvider` are deleted.
The Breaking entry names each and the migration of a partial override.

## Risks / Trade-offs

- [A job record without company name or description can no longer be saved in
  the backend until it is filled in] → Breaking entry. Jobs created through
  the form already have both, since the form requires them.
- [A project TCA override of `required` or `readOnly` on a configured column
  stops working] → Breaking entry with both ways out: the settings file, or a
  listener ordered after `academic-jobs/apply-settings-to-tca`.
- [An override file that dropped a field to drop its flags keeps them now] →
  Breaking entry: name the field with `[]`.
- [A partial override calling `j:validation.*` fails with an unknown
  ViewHelper] → Breaking entry with the replacement markup.
- [Cached settings of the old shape] → a new cache identifier, and the core
  cache is flushed on an extension update.
