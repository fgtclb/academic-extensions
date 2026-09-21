## Context

On branch `2`, `Resources/Private/TypeScript/frontend/academic-study-plan.ts`:

- `buildCategoryFilter()` takes `.filter li` as `outerHTML`, replaces the three
  `*-placeholder` strings in it per category, and parses the result into a
  detached `<li>` with `innerHTML`.
- `categoriesOf()` reads `module.dataset.categories`, which is the **decoded**
  value of the attribute Fluid wrote with `f:format.json(...) ->
  f:format.htmlspecialchars()`.
- The item the template renders carries the title twice, as the button's text
  and inside its `aria-label`, and the colour twice, as
  `data-category-color` and inside `style="--category-color: …"`.

The same two places differ from `main` as `proposal.md` records, and neither is
in this function's substitution.

## Goals / Non-Goals

**Goals:**

- A category title cannot become markup.
- A category colour cannot add a declaration to the `style` attribute.
- The rendered result is unchanged for an ordinary title and colour.

**Non-Goals:**

- Changing what the filter looks like or how it behaves.
- A JavaScript test suite on this branch.

## Decisions

### Substitute into the clone, not into a string

The item is cloned with `cloneNode(true)` and the placeholders are replaced in
the clone's **attribute values and text nodes**, walking it recursively. Neither
an attribute value nor a text node is parsed as HTML, so a title cannot become
markup whatever it contains, and the contract the placeholders are part of is
unchanged: they are still substituted wherever they appear in the item.

Rejected: escaping the title before substituting it into the string. It leaves
the parse in place, it has to escape differently for text and for an attribute,
and the next placeholder added would have to remember.

### The colour is validated, not escaped

`setAttribute()` makes the `style` attribute safe from markup, not from CSS: the
value lands inside a declaration, and a `;` opens the next one. Only the shapes
a colour field can legitimately produce pass - a hexadecimal value, a plain
keyword, or an `rgb()` / `rgba()` function. Anything else becomes the empty
string, which is what an absent colour already meant and which `hexToRgba()`
already handles.

### Arriving without a test, deliberately

The fix is proven on `main`: a fixture with a category titled
`<img src=x onerror=…>` asserts the title as the button's text and asserts that
nothing it names reached the document, and it goes red when the substitution is
put back. That suite does not exist here, and nothing on this branch can execute
a line of the shipped JavaScript.

The alternative - backporting `Build/tests/`, the jsdom dependency, the resolve
hook, a `testJs` suite in `runTests.sh` and a CI step - is a change of a wholly
different size, and leaving a known injection on a maintained branch until that
happens is worse than shipping the same diff unproven. The gap is stated here,
in the commit message and in the pull request rather than left to be discovered.

## Risks / Trade-offs

- [The fix is not covered on this branch] → It is covered on `main`, and the
  two files differ in two places that `proposal.md` names, neither of them in
  this function. The diff applied here is the same diff.
- [A colour form nobody thought of is dropped] → `hexToRgba()` already returned
  a transparent black for anything it could not parse, so a colour it could not
  use was already no colour. The accepted set is wider than what it parses.

## Migration Plan

Nothing is required on update. A category title that contained markup rendered
as markup before and renders as text now.

## Open Questions

None.
