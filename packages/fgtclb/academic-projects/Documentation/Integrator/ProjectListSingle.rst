..  _integrator-project-list-single:

=================
Selected Projects
=================

..  image:: /Images/Icons/projects.svg
    :width: 48px
    :alt: Icon of the Selected Projects content element

:CType:
    ``academicprojects_projectlistsingle``

:Plugin:
    `AcademicProjects`, plugin `ProjectListSingle`, action
    :php:`ProjectController::listAction()`

:Set:
    `fgtclb/academic-projects-project-list-single`, included in
    `fgtclb/academic-projects`

:Templates:
    :file:`Templates/Project/List.html`, with the partials below
    :file:`Partials/Project/`, see :ref:`templates`

The :guilabel:`Selected Projects` element shows project pages an editor picks
one by one, with the cards and the form of the
:ref:`Projects <integrator-project-list>` element. It is the element for a
short list of projects that share no parent page: the projects of a research
group on its page, the projects a news article or a person page refers to, or
a hand picked selection on a landing page.

..  figure:: /Images/Screenshots/project-list-single-default.png
    :alt: The number of projects found above three project cards in a row, without a form
    :class: with-border with-shadow

    Three projects picked by hand, sorted by title.

Both elements are served by the same plugin action, with the same templates
and the same options. They differ in one field: here
:guilabel:`Selected projects to display` names the project pages themselves,
not the pages they sit in. A selection made in the default language finds the
translations of the projects on a translated page.

..  _integrator-project-list-single-settings:

Settings
========

The tab :guilabel:`Configuration` holds the options of the
:guilabel:`Projects` element, and below them the field that picks the
projects. The options work as they do there, so they are only listed here with
what differs for a selection.

..  confval:: Show active state options
    :name: project-list-single-hide-active-state
    :type: checkbox
    :Default: on

    Shows the select :guilabel:`Active state` in the form, as :confval:`Show
    active state options <project-list-hide-active-state>` describes. For a
    short selection it is usually switched off.

..  confval:: Active state
    :name: project-list-single-active-state
    :type: select
    :Default: All

    Leaves out the picked projects that are not in this state, see
    :confval:`Active state <project-list-active-state>`. A list of picked
    projects usually keeps :guilabel:`All`: with :guilabel:`Active`, a picked
    project disappears from the page once its end date has passed, without an
    editor noticing.

..  confval:: Show filter options
    :name: project-list-single-hide-filter
    :type: checkbox
    :Default: on

    Shows the category selects in the form, see :confval:`Show filter options
    <project-list-hide-filter>`. The selects enable the categories the picked
    projects carry, and offer the others disabled or leave them out, as the site
    settings of :ref:`configuration-list-filter` say.

..  confval:: Show sorting options
    :name: project-list-single-hide-sorting
    :type: checkbox
    :Default: on

    Shows the two sorting selects in the form, see
    :confval:`Show sorting options <project-list-hide-sorting>`.

..  confval:: Sorting
    :name: project-list-single-sorting
    :type: select
    :Default: Title A-Z

    The order of the picked projects, with the values of :confval:`Sorting
    <project-list-sorting>`. The order in which the editor picked the projects
    is not kept: the list is always sorted by this field. :guilabel:`Page
    sorting` sorts by the position of each page in the page tree, which says
    little for projects of different parent pages.

..  confval:: Categories
    :name: project-list-single-categories
    :type: category tree
    :Default: empty

    Leaves out the picked projects that do not carry every category selected
    here, see :confval:`Categories <project-list-categories>`. For a selection
    made by hand it is rarely needed.

..  confval:: Show hidden records
    :name: project-list-single-show-hidden-records
    :type: checkbox
    :Default: off

    Lists a picked project page that is hidden as well, see :confval:`Show
    hidden records <project-list-show-hidden-records>`. Without it, a picked
    project that is hidden later disappears from the list.

..  confval:: Show active state badge
    :name: project-list-single-show-active-state-badge
    :type: checkbox
    :Default: off

    Shows on every card whether the project is active or completed, see
    :ref:`configuration-active-state-badge`.

..  confval:: Selected projects to display
    :name: project-list-single-pages
    :type: page
    :Default: empty

    The project pages to show. A page of another page type is not shown,
    whatever the editor picks. The field offers up to 50 pages.

    Empty, the element is not limited to a selection: it shows every project
    page of the installation, on every site, like a :guilabel:`Projects`
    element without pages. Pick at least one project.
