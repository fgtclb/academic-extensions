..  index:: Configuration; Route enhancers
..  _configuration-route-enhancers:

===============
Route enhancers
===============

This extension ships route enhancers for the :guilabel:`Projects` and the
:guilabel:`Projects (selected)` content elements in
:file:`Configuration/Routes/List.yaml`. They turn the category filter, the
active state and the sorting of a list into path segments in the language of
the site:

..  code-block:: text

    /projects/filter/quantum-physics-1/status/completed/start-date/desc
    /de/projekte/filter/quantenphysik-1/status/abgeschlossen/startdatum/absteigend

TYPO3 does not read the file on its own. A site imports it, and nothing
changes for a site that does not.

Importing it into a site configuration
======================================

Add the file to the :yaml:`imports` of the site that shows the plugins, and
limit each enhancer to the pages that carry its plugin:

..  code-block:: yaml
    :caption: config/sites/my_site/config.yaml

    imports:
      - resource: 'EXT:academic_projects/Configuration/Routes/List.yaml'

    routeEnhancers:
      AcademicProjectsList:
        limitToPages: [21]
      AcademicProjectsListSingle:
        limitToPages: [22]

TYPO3 offers every enhancer of a site to every page unless it carries
:yaml:`limitToPages`, and the first route whose path matches wins. The uids
are those of the pages in the **default language**, one entry covers every
translation of a page.

A site that wrote its own enhancer for one of the two plugins removes it when it
imports this file. Two enhancers for one plugin compete for the same URLs.

What the URLs look like
=======================

A list URL carries up to three arguments, in this order:

..  list-table::
    :header-rows: 1

    *   -   Argument
        -   English
        -   German
    *   -   Category filter
        -   :file:`/filter/quantum-physics-1`
        -   :file:`/filter/quantenphysik-1`
    *   -   Active state
        -   :file:`/status/all`, :file:`/status/active`, :file:`/status/completed`
        -   :file:`/status/alle`, :file:`/status/aktiv`, :file:`/status/abgeschlossen`
    *   -   Sorting
        -   :file:`/title/desc`
        -   :file:`/titel/absteigend`

Each combination of them is a route of its own, so a filter alone, a state
alone or a sorting alone resolves as well as all three together. The lists
themselves always link the state and the sorting.

*   **The filter** is mapped by the :yaml:`CategoryFilterMapper` aspect of
    :guilabel:`category_types`, with the group :yaml:`projects`: the title of
    each category in the language of the page, followed by its uid. See
    `its documentation
    <https://docs.typo3.org/p/fgtclb/category-types/main/en-us/Developers/Index.html>`__.
*   **The sorting** is two segments, field and direction:

    ..  list-table::
        :header-rows: 1

        *   -   Sorting
            -   English
            -   German
        *   -   Title
            -   :file:`title`
            -   :file:`titel`
        *   -   Last updated
            -   :file:`last-updated`
            -   :file:`zuletzt-aktualisiert`
        *   -   Sorting of the page tree
            -   :file:`sorting`
            -   :file:`sortierung`
        *   -   Budget
            -   :file:`budget`
            -   :file:`budget`
        *   -   Start date
            -   :file:`start-date`
            -   :file:`startdatum`
        *   -   Ascending, descending
            -   :file:`asc`, :file:`desc`
            -   :file:`aufsteigend`, :file:`absteigend`

A state or sorting value of the other language is a 404, not a second
address of the same list. The filter segment is read by the uid, so it
resolves under the title of any language.

The enhancers declare no defaults. A link with the default state or sorting
keeps them in the path, because the page without any argument is where the
content element's preselected categories, state and sorting apply. A visitor
who removed a preselection must not get it back.

The links the list renders itself use the routes: the redirect after the filter
form and the tags of :ref:`configuration-active-filters`.

Another language
================

The file maps English and German. A site with a further language adds
:yaml:`localeMap` items to the aspects in its own site configuration. The
import appends list items, so the site's own items come after the shipped
ones:

..  code-block:: yaml
    :caption: config/sites/my_site/config.yaml

    routeEnhancers:
      AcademicProjectsList:
        limitToPages: [21]
        aspects:
          state_key:
            localeMap:
              - locale: 'fr.*'
                value: 'statut'
          active_state:
            localeMap:
              - locale: 'fr.*'
                map:
                  tous: 'all'
                  en-cours: 'active'
                  termines: 'completed'

The aspects :yaml:`sorting_field` and :yaml:`sorting_direction` take a
:yaml:`map` the same way, and :yaml:`AcademicProjectsListSingle` the same
items.

A language without :yaml:`localeMap` items of its own uses the English keys
and values. A value missing from the map of a language keeps its query
argument in that language.

The page cache
==============

The state and the sorting are static route arguments, so each combination of
them in a path is a page cache entry of its own: thirty for state and sorting,
ten for a sorting alone, three for a state alone, one for a path with neither,
and one for the page without arguments, forty-five per page and language. The
filter is a dynamic argument and adds no entry: the demand of the lists is
excluded from the cache hash, so every filter of a list shares one entry. The
lists themselves always link state and sorting, so the thirty are the ones a
visitor fills.
