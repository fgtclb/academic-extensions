..  index:: ! Integration
..  _integration:

===========
Integration
===========

How the academic extensions fit together with what an installation runs
beside them: the new content element wizard of TYPO3, a search index built
with EXT:solr, and backend permissions kept in files with
b13/permission-sets.

None of this is shipped as configuration. The wizard page is covered by a
test of this extension. The pages on EXT:solr and permission sets name the
release they were checked against, because those tools change on their own
schedule.

..  card-grid::
    :columns: 1
    :columns-md: 2
    :gap: 4
    :class: pb-4
    :card-height: 100

    ..  card:: :ref:`New content element wizard <integration-wizard>`

        Move or rename the group of the academic content elements, rename
        or hide an element, and order the elements.

    ..  card:: :ref:`EXT:solr <integration-solr>`

        Index queues for profiles and for program, project and partner
        pages.

    ..  card:: :ref:`Permission sets <integration-permission-sets>`

        What an editor group needs for each academic extension, as presets
        for b13/permission-sets.

..  toctree::
    :hidden:
    :titlesonly:

    NewContentElementWizard
    Solr
    PermissionSets
