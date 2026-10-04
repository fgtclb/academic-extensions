..  _feature-frontend-icon-endpoint:

=================================================
Feature: An icon endpoint for frontend JavaScript
=================================================

Description
===========

Where frontend JavaScript learns the identifier of an icon only after the page
was rendered, from data it loads or from a choice of the visitor, the
:ref:`JSON icon map <feature-frontend-icon-map>` of the page cannot carry it.
The JavaScript asks the new icon endpoint below the base of the site or site
language instead:

..  code-block:: text

    GET https://example.com/_academic/icons.json?i=tx-academicbase-action-add,tx-academicbase-info-phone&s=small&v=<token>

`i` names up to 32 identifiers, separated by commas, `s` the size, `small`
when it is left out, and `v` the version token of the served icons. The answer
is a JSON object in the shape of the JSON icon map, for the identifiers that
are served, in the order asked for. It serves the same icons with the same
markup as the view helper. A malformed identifier, more than 32 of them or an
unknown size are answered with `400`, a method other than `GET` and `HEAD`
with `405`, and a site in maintenance mode with `503`.

The endpoint answers before the frontend user authentication: it never starts
a session and never sets a cookie, so a proxy or a CDN can cache it like a
file. The answer is cached for a year
(`Cache-Control: public, max-age=31536000, immutable`) when `v` is the current
version token, and for five minutes otherwise, both with an `ETag` that a
matching `If-None-Match` answers with `304`. The view helper hands the endpoint
of the current site language and the current token to the JavaScript with
`endpoint="1"`:

..  code-block:: html

    <ab:frontendIconMap identifiers="{}" endpoint="1" />

It adds `data-academic-icons-url` and `data-academic-icons-version` to the
data block. The token changes with the frontend icon registrations, the
modification time of their files and of the classes of their providers, and
with every TYPO3 update and every change of :file:`composer.lock`.

The path, the parameters, the answer and the caching of the endpoint are
public API of this extension, see :ref:`developers-extension-points-api`.

Impact
======

The path `_academic/icons.json` below the base of every site is answered by
this extension, a page with that slug can no longer be reached there. A web
server or CDN rule that serves `*.json` as static files has to pass this path
on to TYPO3, the way `sitemap.xml` usually is, see
:ref:`icons-frontend-endpoint`. Web nodes that do not share one build compute
different tokens, so a page rendered on one node gets the five-minute answer
from another.

..  index:: Frontend, JavaScript, ext:academic_base
