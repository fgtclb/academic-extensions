## Context

See proposal.md for the why. The selected profiles and selected contracts
actions of `ProfileController` resolve the mode from `requestedViewMode()`,
which reads the string argument `viewMode` of the plugin namespace. Every
element of one CType shares that namespace, and the switch partial
`Partials/Profile/ViewMode/Switch.html` builds its links from the mode alone.
The list elements carry their mode in the demand, through
`activeListArguments` and `persons:listArguments`, and behind the route
enhancers of ACE-802. They are out of scope.

## Goals / Non-Goals

**Goals:** the mode of a selected element is scoped to its content element,
links survive a change of language, the list path stays byte for byte as it
is.

**Non-Goals:** a generic element scope for other plugin arguments, a route
enhancer for the selected elements.

## Decisions

### The key is the uid of the default-language record

The element is identified by `l18n_parent` of its row when that is set,
otherwise by its `uid`. With an overlaid translation the row carries the
default uid as `uid` and as `l18n_parent`, with `_LOCALIZED_UID` holding the
translation, on TYPO3 v13 and v14 alike (`PageRepository::getRecordOverlay()`
keeps `uid` and `pid`). A translation selected in its own language, fallback
type `free`, carries its own `uid` and the default uid in `l18n_parent`. A
record without a default-language parent, an element created in another
language or one for all languages, is keyed by its own uid. A plugin rendered
without a content object gets the key `0`.

Rejected: the uid of the rendered record (`_LOCALIZED_UID` or `uid`). A link
copied from the default language would not reach the translation, and with
fallback type `free` the key would differ from the one under overlay.
Rejected: a separate `element` argument compared with the uid. Two values per
link for one fact, and an element would still have to ignore a mode meant for
another one.

### The controller hands the switch the arguments of each link

Fluid cannot build an array with a computed key, so the partial cannot turn
the uid into `{viewMode: {<uid>: mode}}` itself. The two selected actions
therefore assign `viewModeArguments`, the plugin arguments of the link to each
offered mode, empty for the default mode, and the templates pass it to the
partial. The partial uses it when it is given and keeps the list branch as it
is.

Rejected: a ViewHelper that nests the mode under a key. It would be a new
public template API for a single partial. Rejected: moving the list links into
the controller as well. It would touch the list path and its route enhancers
without need.

### No other argument travels

A link carries the element's own key only. Every element of a type receives
the whole `viewMode` map and could pass the other keys on, but it could only
carry the elements of its own type, never the other type or a list, so a link
would keep some choices of the page and drop others. The keys of the request
are not validated against the elements of the page, so carrying them would
copy whatever a request names into cached links. And the cache entries of a
page would multiply with every combination of modes.

## Risks / Trade-offs

- [The URLs of the switch of both elements change] → acceptable, the switch is
  unreleased. The cache hash still covers the argument.
- [A project copy of a selected template from a 3.0 development state renders
  links no element answers to] → the shipped templates pass the argument, the
  template documentation names it, and 3.0 is not released.
- [An element shown twice on one page, through an "Insert records" element,
  shares its key with itself] → it is the same element, so it shows the same
  mode.

## Migration Plan

None. No stored data changes.
