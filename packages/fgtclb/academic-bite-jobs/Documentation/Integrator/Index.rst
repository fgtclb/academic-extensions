..  _integrator:

===============
For integrators
===============

This extension ships one content element: a list of the job postings of a job
listing on the B-ITE eRecruiting platform. An integrator adds it to a site with
the set or the static templates, see :ref:`configuration`, and sets up each
content element in its tab :guilabel:`Configuration`. The page below explains
every option of that tab, what the visitor sees and when to choose which
value.

The options of the content element do not meet TypoScript the way the options
of other plugins do. The request to B-ITE, the sorting and the limit are read
from the stored options of the content element alone, so a TypoScript value of
the same name below :typoscript:`plugin.tx_academicbitejobs.settings.jobs` has
no effect on them. Only the view is read from the plugin settings, where a
stored value replaces the TypoScript one, an empty one included, because this
extension names no key in :typoscript:`ignoreFlexFormSettingsIfEmpty`. The
grouping of the jobs is the one setting that exists in TypoScript only, see
:ref:`configuration-general-group-by`.

..  list-table::
    :header-rows: 1
    :widths: 10 25 25 40

    *   -   Icon
        -   Content element
        -   CType
        -   Shows
    *   -   ..  image:: /Images/Icons/bite-jobs.svg
                :width: 32px
                :alt: Icon of the Job List b-ite content element
        -   :ref:`Job List b-ite <integrator-job-list-bite>`
        -   ``academicbitejobs_list``
        -   The postings of one B-ITE job listing, as a list, as cards or as a
            table, each linked to its posting on B-ITE.

..  toctree::
    :hidden:
    :titlesonly:

    JobListBite
