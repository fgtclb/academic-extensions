..  _integrator-job-list-bite:

==============
Job List b-ite
==============

..  image:: /Images/Icons/bite-jobs.svg
    :width: 48px
    :alt: Icon of the Job List b-ite content element

:CType:
    ``academicbitejobs_list``

:Plugin:
    `AcademicBiteJobs`, plugin `List`, action
    :php:`BiteJobsController::listAction()`

:Set:
    `fgtclb/academic-bite-jobs-list`, or the aggregate set
    `fgtclb/academic-bite-jobs`

:Templates:
    :file:`Templates/BiteJobs/List.html`, with the partials
    :file:`BiteJobs/Header.html` and :file:`BiteJobs/View/List.html`,
    :file:`BiteJobs/View/Card.html` or :file:`BiteJobs/View/Table.html`, see
    :ref:`templates`

The job list shows the postings of one job listing on the B-ITE eRecruiting
platform. Each posting shows its title, linked to the posting on B-ITE, and the
day its application period ends. The postings are not records of the TYPO3
installation: the content element asks the search API of B-ITE for them each
time the page is rendered, and an editor maintains them in B-ITE.

It is the content element for an institution that publishes its vacancies in
B-ITE and wants them on its website, without entering them twice. To show only
part of a job listing, a group of positions for example, a project narrows the
request with an event listener, see :ref:`developers`.

The list is cached with its page, and a cached page does not ask B-ITE again.
A new posting therefore appears when the cache entry of the page ends, so set
the field :guilabel:`Cache Lifetime` of the page to how current the list has
to be.

When B-ITE answers with no postings, or the request fails, the list shows the
text "There are currently no job vacancies." instead. A failed request is
logged as an error.

..  figure:: /Images/Screenshots/job-list-bite-empty.png
    :alt: The text "There are currently no job vacancies." in place of the list
    :class: with-border with-shadow

    The B-ITE job list when B-ITE answers with no postings.

The content element works on its own. It has no detail page in TYPO3, the
titles link to B-ITE. Its header is rendered by the content element layout of
the site, see :ref:`configuration-general-content-element-header`.

..  _integrator-job-list-bite-settings:

Settings
========

..  confval:: Job listing key
    :name: job-list-bite-job-listing-key
    :type: text
    :Default: empty

    The key of the job listing in B-ITE whose postings the list shows. The
    key is sent with every request and decides which postings B-ITE answers
    with. It is usually found in the address or the source of an existing
    B-ITE job advertisement of the institution.

    Empty, the request names no job listing and the list shows its empty
    state. The key is read from the content element only, a TypoScript value
    does not replace it.

..  _configuration-general-view:

..  confval:: Select view
    :name: job-list-bite-view
    :type: select
    :Default: List

    How the postings are shown. :guilabel:`List` and :guilabel:`Card` render
    the same content in a list of articles, which differ by their class only,
    :html:`ace-list-view` and :html:`ace-card-view`. The extension ships no
    stylesheet, so the stylesheet of the site decides how the two look, see
    :ref:`styling-list`. :guilabel:`Table` renders a table with the title and
    the end of the application period as columns, and opens a posting in a new
    window.

    Use the table for a long listing that visitors scan, and the list or the
    cards for a few postings on a page with other content.

    The stored values are `List`, `Card` and `Table`. Content elements saved
    with version 2.0 store `ListView`, `CardView` or `TableView`. They render
    the corresponding view, and a content element with an empty or unknown
    value renders the list view. Templates always receive the normalised value
    in :html:`{settings.jobs.view}`.

    The upgrade wizard `academicBiteJobs_listViewFlexFormUpgradeWizard`
    rewrites the stored values of existing content elements, so their backend
    form shows the view the frontend renders.

..  confval:: Sort by
    :name: job-list-bite-sort-by
    :type: select
    :Default: Ends on

    The field B-ITE sorts the postings by: :guilabel:`Ends on`, the end of the
    application period, or :guilabel:`Title`. The value is sent with the
    request, B-ITE sorts.

    Sort by the end of the application period for a list that puts the most
    urgent postings first, by title for a long list that visitors search by
    name.

..  confval:: Sort direction
    :name: job-list-bite-sorting-direction
    :type: select
    :Default: Ascending

    :guilabel:`Ascending` or :guilabel:`Descending`, sent with the request
    together with :confval:`Sort by <job-list-bite-sort-by>`. Ascending by the
    end of the application period lists the postings that end first at the top.

..  confval:: Maximum number of job offers
    :name: job-list-bite-limit
    :type: number
    :Default: empty

    The most postings the list shows. Empty or `0` shows every posting B-ITE
    answers with.

    The limit is applied after the event listeners handled the postings, so a
    listener that removes postings still leaves the list as many as the limit
    allows. Use it for a teaser of the latest vacancies on a start page.
