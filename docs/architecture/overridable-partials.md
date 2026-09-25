# Overridable partials

A project customises a plugin by overriding a Fluid partial through the
configured partial root path. How big that partial is decides how much of the
extension the project has to maintain afterwards: a project that copies a whole
item template to change one heading misses every correction made upstream, and
the project analysis of 2026-09 found exactly that in five of six installations.

So a template that a project is likely to touch in pieces is **built from
pieces**. This page is the rule for doing that; where it is applied today is in
each extension's own `Documentation/`.

## The entry partial keeps its arguments

A partial that callers already render — `Profile/Item`,
`Profile/List/ItemList` — stays where it is, keeps its name and keeps its
arguments. It delegates instead of holding the markup.

That is what makes the split non-breaking: a project that already copied the
entry partial renders exactly what it rendered before. Its copy simply does not
delegate, so it does not profit either, and the changelog entry tells it to drop
the copy.

Fluid sections inside the existing file were rejected for the same test: an
override of a section still has to copy the whole file.

## A partial that renders a value renders it raw

Most partials render markup. A few render a **value** that a caller hands on as
a ViewHelper argument — a name that goes into a heading partial, a URI that goes
into `f:link.typolink`. Those two kinds have opposite escaping rules, and
getting it wrong is silent.

Fluid escapes an object accessor in the **output** position. Whether it escapes
one inside a ViewHelper **argument** depends on the argument:
`TemplateParser::isArgumentEscaped()` returns true for a *content* argument
whose ViewHelper escapes its children, and otherwise only when the argument
definition sets `escape`. `f:render` registers `arguments` with no `escape` and
it is not its content argument, so `TemplateParser::recursiveArrayHandler()`
switches escaping off for the whole `arguments="{…}"` subtree — that, and not a
blanket rule about arguments, is what makes this split safe. (The method, not a
line number: Fluid 4 on v13 and Fluid 5 on v14 number it differently.) So:

- `header: '{profile.lastName}'` reaches the header partial unescaped, and
  `{header}` escapes it once on the way out. That is correct.
- `<f:render partial="…/Name" />` returns a string that the partial already
  escaped. Handing *that* to the same header partial escapes it a second time,
  and `O'Neill` reaches the browser as the markup `O&amp;#039;Neill`, which a
  visitor reads as `O&#039;Neill`.

A value partial therefore renders its variables through `f:format.raw` and says
so in its own comment, so that copying it as the starting point of an override
carries the rule along. `Profile/Item/Name.html` and
`Profile/Item/DetailLink.html` are the two that exist.

The other half of that contract is where such a partial may be rendered: **only
as an argument of something that escapes it**. Rendered in an output position it
emits stored values unescaped, and the profile fields it reads are ones a
profile owner can write through `academic_persons_edit`. Both partials and the
extension's `Templates` chapter say so.

Decoding the extra layer in the caller instead does **not** work:
`f:format.htmlentitiesDecode` maps its `keepQuotes` argument to `ENT_NOQUOTES`
or `ENT_COMPAT` and has no way to reach `ENT_QUOTES`, while Fluid's
`EscapingNode` escapes *with* `ENT_QUOTES`. An apostrophe survives the decode
and stays double-escaped. Verified in
`typo3/cms-fluid/Classes/ViewHelpers/Format/HtmlentitiesDecodeViewHelper.php`
and `typo3fluid/fluid/src/Core/Parser/SyntaxTree/EscapingNode.php`.

The alternative — teaching the heading partials to accept pre-rendered markup —
was rejected: `Profile/Header.html` renders eleven heading variants and
`Profile/SectionHeader.html` ten, `academic_contacts4pages` renders them through
partial root paths of its own, and the group header passes a database value that
has to keep being escaped. (`academic_persons_edit` registers the persons
partials too, but ships its own `Profile/Header.html` at a higher root path key,
so it renders that one and is not affected either way.)

## Classes are added, never moved

Every new partial that renders an element of its own gets a BEM class next to
the classes that were already there, never instead of one: project stylesheets
build on `card`, `card-title`, `card-img-top` and the extension's own list
classes. A partial that renders no element — a value partial, an empty hook —
gets no class, because a wrapper element would be a markup change.

The block the class names is the block the element is **in**, which is not
always the partial's folder. `Profile/List/Items.html` and
`Profile/List/EmptyState.html` live under `List/` but render inside the list,
the card, the selected profiles and the selected contracts alike, so they are
blocks of their own (`academic-persons-grid`, `academic-persons-empty-state`)
rather than `academic-persons-list__…`. A class is a published contract from the
release on, so this is decided before it ships, not after.

Where a class belongs to a heading that a shared partial renders, it is passed
through that partial's existing `positionClass` argument rather than wrapped in
a new element.

## An empty partial is a legitimate partial

`Profile/List/ResultCount.html` renders nothing. Printing a result count by
default would change every list that exists, and an empty partial is what
removes one project's template copy without touching any other installation.
It receives everything an implementation would need and is documented as a hook.

## A list renders its items through a partial of its own

A list template that arranges records — groups them, sorts them into columns —
renders each record through an item partial of its own extension, even when
that partial does nothing but render a shared one. `academic_contacts4pages`
renders every contact through `Contacts/Item.html`, which by default only
renders `Profile/Item` of `academic_persons`, so a project changes the contact
card without copying the list template that groups the contacts.

Overriding the extension's partial root path key `10` with a directory that
lacks that partial does not break the element: Extbase adds the extension's own
`Resources/Private/Partials/` as the lowest-priority path whenever the
configured paths do not contain it (`ActionController::addDefaultPathToPaths()`,
the same on v13 and v14).

## Tests

The split is testable in one shape: a fixture extension that overrides **one**
partial with marker text, a TypoScript constant that registers its partial root
path, and assertions that every plugin rendering that partial shows the marker
while the rest of the item is unchanged. See
`academic-persons/Tests/Functional/Plugins/AcademicPersonsProfileTemplatePartialsTest.php`
and `test_profile_partial_overrides`.

Three things are worth pinning beyond that:

- **The output before the split**, so the refactoring is provably one. Those
  assertions are written and shown to pass before any template is touched.
- **The escaping**, with a fixture name that carries an apostrophe. A double
  escape is invisible in a name made of plain letters.
- **The classes**, one assertion per class. They are a published contract, and
  nothing else in a rendering test touches them.

An extension that resolves the partials through root paths of its own —
`academic_contacts4pages` does — needs the override registered there as well,
and that second registration gets a test of its own in that extension.

## See also

- [Shared partials](shared-partials.md) — the partials `academic_base` ships
  for several extensions, and the root path key that keeps a project override
  winning.
- [Content element rendering](content-element-rendering.md) — what wraps a
  plugin's output.
- [Fixture extensions](../testing/fixture-extensions.md) — how an override
  fixture is wired.
