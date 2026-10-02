.. _feature-1791043403:

==============================================
Feature: Route enhancers for the project lists
==============================================

Description
===========

The extension ships route enhancers for the :guilabel:`Projects` and the
:guilabel:`Projects (selected)` content elements in
:file:`Configuration/Routes/List.yaml`. A site that imports the file gets
readable list URLs in the language of the site:

..  code-block:: text

    /projects/filter/quantum-physics-1/status/completed/start-date/desc
    /de/projekte/filter/quantenphysik-1/status/abgeschlossen/startdatum/absteigend

Every combination of filter, active state and sorting is a route of its own,
so a filter alone or a state alone resolves as well. A single enhancer route
with defaults generates a broken path for one of them and query arguments for
the other. The filter segment uses the :yaml:`CategoryFilterMapper` aspect of
:guilabel:`category_types`.

..  code-block:: yaml
    :caption: config/sites/my_site/config.yaml

    imports:
      - resource: 'EXT:academic_projects/Configuration/Routes/List.yaml'

    routeEnhancers:
      AcademicProjectsList:
        limitToPages: [21]
      AcademicProjectsListSingle:
        limitToPages: [22]

See :ref:`configuration-route-enhancers`.

Impact
======

*   Nothing changes for a site that does not import the file.
*   A site that imports it removes its own enhancer for the two plugins, if it
    has one. The redirect of the filter form and the active filter tags lead to
    the paths.
*   Each combination of state and sorting in a path is a page cache entry of
    its own, forty-five per page and language with the bare page, thirty of
    them for the links the lists render. Filters share one entry, as with query
    arguments.

.. index:: Frontend, NotScanned
