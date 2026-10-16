## Context

See proposal.md, Why. The finder resolves its selects with the resolver the
lists of partners, programs and projects share: it offers each named type that
is a type of the group with at least one category, disabled or not, and every
such type for an empty value. The finder passed its own default, the degree and
the topic, in place of an empty value, so the resolver never saw the empty
value the list relies on.

## Goals / Non-Goals

**Goals:** a finder without configured types that always offers a select as
long as the group has a type with categories, while every finder that offers
the degree or the topic today stays as it is.

**Non-Goals:** a new decision of which types count. The check stays the one
the list makes.

## Decisions

- **Fall back after resolving the default, not before.** The controller
  resolves the degree and the topic first and resolves again with an empty
  value only when that offered nothing. "Exists" is therefore exactly what the
  resolver decides for the list: a type of the group, removed types excluded,
  with a category in the collection, a disabled category included. Checking
  the type registry beforehand was rejected, because a registered type without
  categories would still leave the finder empty.
- **Only the built-in default falls back.** A value of the element or the site
  is resolved as it is, as for the list, where a named type without categories
  is left out and a list whose types all have none renders no filter. Widening
  that would make a configured value mean something else on one site than on
  another.
- **One of the two is enough.** A site with degrees and without topics keeps
  getting the degree alone, as before, so no finder in use changes. The
  development seed has both and keeps its selects, manifests and screenshots.
- **In the controller, not in the resolver.** The default is a concern of the
  finder only. A second parameter of the shared resolver for one caller was
  rejected.

## Risks / Trade-offs

- [A finder that offered nothing now offers up to every type of the group] →
  that is the purpose, and the editor narrows it with the field as before.

## Migration Plan

None. Stored elements and settings are read as before.
