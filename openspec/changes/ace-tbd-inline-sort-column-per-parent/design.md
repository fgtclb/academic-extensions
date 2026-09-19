## Context

Measured on `main` at `f7c4c5bf1` and on `origin/2` at `6af098433`:

- `tx_academicpartners_domain_model_partnership` has `ctrl.sortby = sorting`.
  The partner page relation (`Configuration/TCA/Overrides/pages.php`) declares
  `foreign_sortby = sorting`. The role relation
  (`tx_academicpartners_domain_model_role.php`, field `partnerships`, shown in
  the role form) declares none.
- `tx_academicpersons_domain_model_contract` has `ctrl.sortby = sorting`. The
  profile relation and the organisational unit relation (field `contracts`,
  shown in the unit form) both declare `foreign_sortby = sorting`.
- `RelationHandler::writeForeignField()` (v14, `:961`ff; the same logic on v12
  and v13) writes `$updateValues[$sortby] = ++$c` for every child of the saved
  parent, with `$sortby` = `foreign_sortby` or the table's sort field.
- `tx_academiccontacts4pages_domain_model_contact` has `ctrl.sortby = sorting`.
  The page relation (`Overrides/pages.php`) and the contract relation
  (`Overrides/tx_academicpersons_domain_model_contract.php`, a tab of the
  contract form, drag and drop enabled) declare `foreign_sortby = sorting`; the
  contacts role relation (`contacts`, in the role form) declares none.
- Nothing reads `Role::$partnerships` or `OrganisationalUnit::$contracts` in a
  template; the frontend renders `profile.contracts`, the partnerships of a
  page and the contacts of a page. Whether a contract's or a contacts role's
  contacts are rendered anywhere is re-checked by the implementation.
- A sweep of every `type => inline` relation in the extensions' TCA finds
  exactly these three tables with more than one parent.
- The relevant TCA is identical on both branches apart from labels.

## Decisions

### A sort column per second relation

Each second relation gets its own `foreign_sortby`, the pattern core uses for
`sys_file_reference` (`sorting_foreign` per parent field). The owning relation
keeps `sorting`, so nothing that renders today changes. Rejected:

- **Read-only second relations**: fixes the defect by removing a capability
  editors have today (creating and rearranging from the role or unit form).
- **Dropping the second relations from the forms**: the same, more so.
- **Ordering the frontend by something other than `sorting`**: undoes the
  editor's arrangement, which ACE-491 made visible deliberately.

### Seed the new columns

Without seeding, every existing child has `0` in the new column and the role
and unit forms would show ties in whatever order the database returns. A wizard
numbers the children of each parent in their current (`sorting`, `uid`) order,
which is what those forms show today. It runs once and reports nothing to do
afterwards.

### Name the columns after the parent

`role_sorting`, `organisational_unit_sorting` and `contract_sorting` say which
relation they belong to; `role_sorting` exists on two tables, for two
different role tables. They are declared in `ext_tables.sql`, because a `foreign_sortby`
column is not derived from TCA the way `ctrl.sortby` is. Verify this against
`DefaultTcaSchema` on every supported core version before relying on it.

## Risks / Trade-offs

- [A project renders a role's partnerships or a unit's contracts itself] → Its
  order follows the new column, which the wizard seeds from today's order, so
  it does not change on update.
- [Upgrade wizards are among the v15 blockers] → A wizard is still the
  documented way on v13 and v14; it uses the same API as the twelve existing
  ones.

## Backport

Recommended. The TCA and the relation handling are the same on branch `2` for
all three tables, and partnership lists there render in `sorting` order since
#670.
