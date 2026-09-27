## Why

The backport of the `main` change of the same name, ACE-751, archived there as
`openspec/changes/archive/2026-09-27-ace-751-category-types-yaml-docs`.

`category_types` lets a later package change a category type that another
package ships: `useExisting: true` merges the given keys onto the loaded type,
and `remove: true` drops it. ACE-664 added the page
`Documentation/Developers/CategoryTypes/` on this branch too, with the key
list, a relabel through `useExisting` and a removal. What it leaves out is the
case projects need most, another icon for a shipped type, and the edges they
run into:

- no icon-only override;
- nothing on a redeclaration without `useExisting` resetting every key it
  leaves out to its default;
- the load order is stated for `composer.json` only, and the failure of an
  override is stated only as "no earlier extension declared" the type, without
  its message and without spelling out that a declaring extension loaded later
  or an earlier removal fail the same way;
- nothing on a removal that comes before the definition, or on removing a type
  of an extension that is not always installed;
- `getExtensionKey()` is described as the declaring extension, while an
  override hands it to the overriding one;
- nothing on the `groups:` section two academic extensions ship, or on the
  identifier being stored without its group.

## What Changes

- The page `Documentation/Developers/CategoryTypes/Index.rst` of
  `category_types` (`packages/fgtclb/typo3-category-types`) gains the same
  sections as on `main`: `useExisting` and `remove` in the key list, the
  icon-only override, the redeclaration rule, the load order rule with the
  exception message, the removal order and `suggest` for an optional
  extension, the extension key after an override, the unused `groups:`
  section and a link to the identifier advice of the TCA page.
- Without `inlineIcon`, which this branch does not have: the key list, the
  icon-only example and the redeclaration defaults leave it out.
- The icons page links the icon-only override.
- The same four loader unit tests and three fixture packages as on `main`,
  without `inlineIcon`, and the functional test for the `sys_category` type
  select. Its group heading assertion is split per core version, because
  TYPO3 v12 groups the items in the form data provider and v13 in a separate
  service.

Nothing changes at runtime, on TYPO3 v12 or v13.

## Capabilities

### New Capabilities

None. The change documents existing behaviour and sets `skip_specs: true`.

### Modified Capabilities

None.

## Impact

- One existing documentation page extended, one link added to the icons page.
- Four unit tests with three fixture packages, one functional test class and
  one group heading test class per core version.
- No code, configuration or dependency change.

## Non-goals

- The follow-up changes on category types; they are planned on `main` only.
- A changelog entry: nothing an installation observes changes.

## Source

Resolves ACE-751, a subtask of the category types epic ACE-42.
