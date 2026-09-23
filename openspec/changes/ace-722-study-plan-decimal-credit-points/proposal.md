## Why

The backport of the `main` change of the same name, ACE-722, archived
there as
`openspec/changes/archive/2026-09-23-ace-722-study-plan-decimal-credit-points`
in its own pull request.

The credit points of semesters and modules in `academic_study_plan`
(`packages/fgtclb/academic-study-plan`) are integer columns, and the backend
field accepts integers only, so a module worth 2.5 credit points cannot be
entered. Projects carry their own column and TCA overrides for it today.

## What Changes

- Editors can enter credit points with up to two decimals on semesters and
  modules.
- The columns become `decimal(10,2)`; existing integer values keep their
  value. A database compare is required after the update.
- Visitors see credit points without trailing zeros: "2.5", and "30" rather
  than "30.00", in template overrides as well. A semester or module without
  credit points keeps showing none.

Behaviour is identical on TYPO3 v12 and v13.

## Capabilities

### New Capabilities

- `academic-study-plan/credit-points`: how editors enter and visitors see the
  credit points of semesters and modules.

### Modified Capabilities

None.

## Impact

- TCA of `tx_academicstudyplan_domain_model_semester` and
  `tx_academicstudyplan_domain_model_module`; the two `credit_points` lines of
  `ext_tables.sql` declare a decimal column.
- A data-preserving column change applied by the database compare, on every
  supported DBMS - a schema change in a minor release, decided by the
  maintainer.
- The study plan data handed to the template carries credit points as
  numbers instead of database strings.

## Non-goals

- Credit points of program pages in `academic_programs`, which stay an
  integer.
- Formatting the number per language (decimal comma); it stays a template
  concern.
