..  _breaking-map-assets-are-built:

=========================================================
Breaking: The map assets are built and loaded as a module
=========================================================

Description
===========

The script of the partner map is now compiled from sources in the repository.
It moved into a :file:`frontend/` subdirectory and became an **ES module**:

..  code-block:: text

    EXT:academic_partners/Resources/Public/JavaScript/map.js
    ->  EXT:academic_partners/Resources/Public/JavaScript/frontend/map.js

The stylesheet of the map, :file:`Resources/Public/Css/map.css`, is not shipped
any more, see :ref:`breaking-partner-map-ships-no-stylesheet`.

The vendored Leaflet library, its marker cluster plugin and their stylesheets
are **unchanged**. They are third party files without sources here, they keep
their paths, and they are still loaded as classic scripts — the map module
reads the :js:`LeafletObject` global they define.

Impact
======

An installation that uses the shipped :file:`Map.html` template needs to do
nothing.

An installation that references either path keeps pointing at a file that no
longer exists.

Affected installations
======================

Installations that override :file:`Templates/Partner/Map.html` or reference
:file:`map.css` or :file:`map.js` from their own site package.

Migration
=========

In an overridden template, replace the reference to the script and drop the
one to :file:`map.css`:

..  code-block:: html

    <f:asset.module identifier="@fgtclb/academic-partners/frontend/map.js" />

The three Leaflet lines around it stay exactly as they are.
