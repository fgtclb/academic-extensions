## Why

The credit points of semesters and modules in `academic_study_plan`
(`packages/fgtclb/academic-study-plan`) are integer columns, and the backend
field accepts integers only, so a module worth 2.5 credit points cannot be
entered. ace-demo redefines the columns as float but keeps the integer form
field, another project changes both, and a third marked its request as done
without a code change.

## What Changes

- Editors can enter credit points with up to two decimals on semesters and
  modules.
- The columns become exact decimal columns; existing integer values keep
  their value. A database compare is required after the update.
- Visitors see credit points without trailing zeros: "2.5", and "30" rather
  than "30.00", in template overrides as well.

Behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-study-plan/credit-points`: how editors enter and visitors see the
  credit points of semesters and modules.

### Modified Capabilities

None.

## Impact

- TCA of `tx_academicstudyplan_domain_model_semester` and
  `tx_academicstudyplan_domain_model_module`; two lines of `ext_tables.sql`
  removed so the core derives the decimal columns.
- A data-preserving column change applied by the database compare, on every
  supported DBMS.
- The study plan data handed to the template carries credit points as
  numbers instead of database strings.

## Non-goals

- Credit points of program pages in `academic_programs`, which stay an
  integer.
- Formatting the number per language (decimal comma); it stays a template
  concern (see the design).
- A float column, which rounds when values are summed.
- A backport to branch `2`: no project on that line would consume it (see
  the design).

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`programs-studyplan-15`). Three of the six analysed projects carry their own
code for this today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-study-plan-decimal-credit-points` when the issue is filed after
implementation.
