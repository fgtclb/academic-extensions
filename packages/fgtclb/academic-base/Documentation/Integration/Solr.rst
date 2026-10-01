..  index:: Integration; EXT:solr
..  _integration-solr:

========
EXT:solr
========

The academic extensions ship no search configuration. This page shows the
index queues a site needs to find profiles of :guilabel:`EXT:academic_persons`
and the pages of :guilabel:`EXT:academic_programs`,
:guilabel:`EXT:academic_projects` and :guilabel:`EXT:academic_partners` with
`EXT:solr <https://extensions.typo3.org/extension/solr>`__.

..  note::

    Written for EXT:solr 13.1 on TYPO3 v13 and checked against the source of
    release 13.1.4. This repository does not install EXT:solr, so nothing on
    this page is covered by a test. A section for TYPO3 v14 follows once an
    EXT:solr release for it has been checked.

The TypoScript below belongs to the site package, after the TypoScript of
EXT:solr.

..  contents::
    :local:
    :depth: 1

..  _integration-solr-profiles:

Profiles
========

Profiles are records, so they get an index queue of their own that names the
table. The text of a profile is spread over several rich text fields, and the
link leads to the detail plugin on the page that the constant
:typoscript:`plugin.tx_academicpersons.detailPid` names, the detail page the
list plugins link to unless a content element names another one:

..  code-block:: typoscript

    plugin.tx_solr.index.queue {
      profiles = 1
      profiles {
        type = tx_academicpersons_domain_model_profile

        fields {
          title = TEXT
          title {
            value = {field:title} {field:first_name} {field:last_name}
            insertData = 1
          }

          content = SOLR_CONTENT
          content {
            cObject = COA
            cObject {
              10 = TEXT
              10.field = teaching_area
              10.noTrimWrap = || |
              20 = TEXT
              20.field = core_competences
              20.noTrimWrap = || |
              30 = TEXT
              30.field = supervised_thesis
              30.noTrimWrap = || |
              40 = TEXT
              40.field = supervised_doctoral_thesis
              40.noTrimWrap = || |
              50 = TEXT
              50.field = miscellaneous
            }
          }

          url = TEXT
          url {
            typolink.parameter = {$plugin.tx_academicpersons.detailPid}
            typolink.additionalParams = &tx_academicpersons_detail[controller]=Profile&tx_academicpersons_detail[action]=detail&tx_academicpersons_detail[profile]={field:uid}&L={field:__solr_index_language}
            typolink.additionalParams.insertData = 1
            typolink.returnLast = url
          }
        }
      }
    }

The arguments of the link are those of the detail plugin, namespace
`tx_academicpersons_detail`. A site that imports the route enhancer
:file:`EXT:academic_persons/Configuration/Routes/Detail.yaml` gets the speaking
URL of the profile for this link, as for every other link to a profile.

There is no column `free_field` in any version of
:guilabel:`EXT:academic_persons`, so a mapping to it stays empty. The text
columns of the profile are the five above, and :sql:`title`, :sql:`first_name`,
:sql:`middle_name` and :sql:`last_name` hold the name. The publications,
projects and other entries of a profile are records of
:sql:`tx_academicpersons_domain_model_profile_information` and are not part of
this queue.

EXT:solr indexes the records whose folder lies inside the page tree of the site.
Profiles kept in a folder outside of it need the uid of that folder in
:typoscript:`additionalPageIds` of the queue.

..  _integration-solr-pages:

Program, project and partner pages
==================================

Programs, projects and partners are pages of their own page type, so EXT:solr
indexes them like any other page, by rendering them. Its queue `pages` covers
the page types 1 and 7 only. A queue per page type, copied from `pages`, keeps
them apart in the index. Each copy names its table with :typoscript:`type`:

..  code-block:: typoscript

    plugin.tx_solr.index.queue {
      programs < plugin.tx_solr.index.queue.pages
      programs {
        type = pages
        allowedPageTypes = 20
        additionalWhereClause = doktype = 20 AND no_search = 0
        fields.academicPageType_stringS = TEXT
        fields.academicPageType_stringS.value = program
      }

      projects < plugin.tx_solr.index.queue.pages
      projects {
        type = pages
        allowedPageTypes = 30
        additionalWhereClause = doktype = 30 AND no_search = 0
        fields.academicPageType_stringS = TEXT
        fields.academicPageType_stringS.value = project
      }

      partners < plugin.tx_solr.index.queue.pages
      partners {
        type = pages
        allowedPageTypes = 40
        additionalWhereClause = doktype = 40 AND no_search = 0
        fields.academicPageType_stringS = TEXT
        fields.academicPageType_stringS.value = partner
      }
    }

..  list-table::
    :header-rows: 1

    *   -   Page type
        -   Doktype
        -   Extension
    *   -   :guilabel:`Program`
        -   20
        -   :guilabel:`EXT:academic_programs`
    *   -   :guilabel:`Project`
        -   30
        -   :guilabel:`EXT:academic_projects`
    *   -   :guilabel:`Partner`
        -   40
        -   :guilabel:`EXT:academic_partners`

What makes this work in EXT:solr 13.1:

*   EXT:solr takes the table of a queue from :typoscript:`type`, and from the
    name of the queue when :typoscript:`type` is not set. The initializer of a
    copied `pages` queue reads :sql:`pages` either way, but the check that
    queues a page again after an editor changed it does not. Without
    :typoscript:`type = pages` a program page is indexed when the queue is
    initialized, a later change of the page never reaches the index, and
    moving the page removes it from the index.
*   EXT:solr checks a page against the :typoscript:`allowedPageTypes` of the
    queue it is added to, and against those of every enabled queue when no
    queue is named. The queue `pages` therefore keeps its own value.
*   The `fields` of a copied queue are applied to its pages only. The
    dynamic field `academicPageType_stringS` above gives a search a value to
    filter or facet by.
*   The Solr field `type` of a document cannot be set by a mapping: EXT:solr
    refuses it with the exception 1435441863. Every document of these queues
    has the type `pages`, and the field above is what tells them apart.
*   A scheduler task :guilabel:`Re-Index` that names one of these queues
    removes every document of the type `pages` of the site from the index
    before it queues the pages again, not the pages of that queue only.
