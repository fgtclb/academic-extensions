## Context

See `proposal.md` for the motivation. Verified on this branch at `f25a683c9`:

- `ContactsController.php` and `ContactsProcessor.php` are identical to `main`
  before the fix. Both hand every contact of `findByPid()` to the output and
  build the roles from all of them; `List/setup.typoscript` registers the
  processor by class name at `page.10.dataProcessing.400`.
- `ContactRepository::findByPid()` is the plain Extbase query this branch has
  always had (no ACE-484 pre-query): contact table restrictions, `page`
  constraint, ordering by `sorting`. It restricts only the contact table.
- `Contact::getUnfilteredContract()` (:68) and `getRole()` (:73) exist.
- A hidden contract, a hidden profile and an expired profile resolve to `null`
  on v12 and v13 - the new plugin test renders 4 and 5 cards instead of 1 and
  2 on the unchanged code on both versions.
- `ContentDataProcessor::getDataProcessor()` asks `$this->container->has()`
  before `makeInstance()` on v12.4.45 exactly as on v13, so a published
  processor receives constructor injection on both.
- PHP 8.1 is the floor of this branch: `readonly class` is a parse error, and
  `docs/architecture/class-design.md` records that no class here uses it.
- `academic_contacts4pages` has no plugin, processor or provider test on this
  branch; `FrontendPluginRenderingTrait` is available.

## Goals / Non-Goals

**Goals:**

- One place decides which contacts of a page are shown, and both entry points
  use it, on v12 and v13.
- The rule reuses Extbase's own visibility decision instead of repeating it.

**Non-Goals:**

- Extending the processor with `contactsWithoutRole`, `as` or
  `showHiddenRecords`.
- Backporting the language handling of ACE-484.

## Decisions

### A stateless provider shared by controller and processor

As on `main`: `PageContactsProvider` in `Classes/Service/` returns a
`PageContacts` value object (`contacts` as `list<Contact>`, `roles`,
`contactsWithoutRole`) for a page uid and the show-hidden flag. It iterates
`findByPid()` in its order, drops every contact whose
`getUnfilteredContract()` is `null` or whose contract has no profile, and
builds the role split from the rest. The processor is published with
Symfony's `#[Autoconfigure(public: true)]` (present in Symfony 6.4 and 7.4)
and receives the provider through its constructor; the controller keeps its
inject method style.

### Property-level `readonly` instead of `readonly class`

Both new classes are `final class` with `readonly` on every promoted
property. That is the same guarantee `main` gets from `final readonly class`
and the only form PHP 8.1 accepts.

### Filter the resolved objects, not the query

The provider works on whatever `findByPid()` returns, so it does not depend on
the repository differing between the branches.

### "Show hidden records" stays about contact rows

A hidden profile is left out in both modes, as on `main`.

### A minimal plugin test class

The plugin tests on `main` live in a class this branch does not have. A new
`AcademicContacts4PagesListPluginTest` carries a baseline and the two tests of
this change, with the fixtures copied from `main`, instead of porting the
whole class.

## Risks / Trade-offs

- [PHP code reads `contacts` as a query result] → `Important-` changelog
  entry; Fluid `f:for` and `f:count` are unaffected.
- [A project subclasses the processor] → The subclass has to be autowired by
  the project, stated in the changelog entry and in `docs/`.
- [A role disappears when all its contacts are hidden] → Intended.

## Migration Plan

None. Output changes on update; there is no stored data to migrate.

## Open Questions

None.
