..  _integrator-project-list:

========
Projects
========

..  image:: /Images/Icons/projects.svg
    :width: 48px
    :alt: Icon of the Projects content element

:CType:
    ``academicprojects_projectlist``

:Plugin:
    `AcademicProjects`, plugin `ProjectList`, action
    :php:`ProjectController::listAction()`

:Set:
    `fgtclb/academic-projects-project-list`, included in
    `fgtclb/academic-projects`

:Templates:
    :file:`Templates/Project/List.html`, with the partials below
    :file:`Partials/Project/`, see :ref:`templates`

The :guilabel:`Projects` element shows the project pages below the pages an
editor chooses as cards: the project title, or the page title without one,
linked to the project page, the short description, the categories of the
project grouped by category type, and its first image. Above the cards the
visitor finds a sorting, one select per category type and a select for the
state of a project, and each change reloads the list with the new selection.
A list that finds nothing says "No projects found."

It is the element for the overview page of the projects of a faculty, an
institute or the whole institution. With the form hidden and the state preset
to :guilabel:`Active`, it shows the running projects on a landing page. To show
a few projects picked by hand, on the page of a research group for example,
use :ref:`Selected Projects <integrator-project-list-single>`, which has the
same options.

..  figure:: /Images/Screenshots/project-list-default.png
    :alt: A form with sorting, category and active state selects above the number of projects found and a grid of project cards with title, state, short description and categories
    :class: with-border with-shadow

    The Projects element with its sorting, category and state selects and
    the project cards.

A project is a page of the page type :guilabel:`Academic project`. The list
finds these pages, not records in a folder, and links every card to the page.

The element renders the template :file:`Project/List.html` with the partials
below :file:`Project/`, among them :file:`SortingAndFilters.html` for the form
and :file:`Item.html` for a card. :ref:`styling-list` and :ref:`styling-item`
show the markup, and :ref:`templates` describes how to override the templates.
The list is rendered on every request rather than from the page cache, so the
selection of the visitor takes effect at once. A selection is part of the URL
and can be bookmarked or shared, see :ref:`feature-1790226101`, and
:ref:`configuration-route-enhancers` turns it into a readable path.

Related chapters:

*   :ref:`configuration-list-filter` describes the site settings that decide
    which category filters the form offers.
*   :ref:`configuration-active-filters` describes the active filter tags, the
    reset link and the number of projects found.
*   :ref:`configuration-content-element-header` is for a site whose content
    element layout renders no header.

..  _integrator-project-list-configuration:

Settings
========

The tab :guilabel:`Configuration` of the content element holds the options of
the list and, below them, the field that chooses the pages.

..  confval:: Show active state options
    :name: project-list-hide-active-state
    :type: checkbox
    :Default: on

    Shows the select :guilabel:`Active state`, with the options
    :guilabel:`All`, :guilabel:`Active` and :guilabel:`Completed`, in the form
    of the list. Switched off, the visitor cannot choose the state, and the list
    shows the state :confval:`Active state <project-list-active-state>` presets.

    The option stores the inverse, `settings.hideActiveState`, which is why it
    reads "show" while the stored value hides. A template override checks
    :html:`{settings.hideActiveState}`.

    The state is preset only for a request without a selection of the visitor.
    A visitor who changes the sorting or a category submits no state while the
    select is hidden, and gets the list in the state :guilabel:`All`. Hide the
    sorting and the category filter as well when a preset state must hold, as
    a list of running projects usually wants.

..  figure:: /Images/Screenshots/project-list-active-only.png
    :alt: The number of projects found above a grid of project cards, without any form
    :class: with-border with-shadow

    A Projects element preset to the running projects, with the whole form
    hidden. The element of the development instance shows hidden records, so
    the first card is the hidden project "Quantum curriculum study".

..  confval:: Active state
    :name: project-list-active-state
    :type: select
    :Default: All

    The state the list shows until the visitor picks another one:
    :guilabel:`All` projects, the :guilabel:`Active` ones or the
    :guilabel:`Completed` ones. :ref:`configuration-active-state` describes
    which projects count as which. An empty or unknown value counts as
    :guilabel:`All`.

..  figure:: /Images/Screenshots/project-list-filtered.png
    :alt: The Active state select set to Completed, a tag "Completed", a "Reset all filters" link, the number of projects found and the completed projects
    :class: with-border with-shadow

    The list in the state Completed, with the tag that removes the state,
    the reset link and the number of projects found.

..  confval:: Show filter options
    :name: project-list-hide-filter
    :type: checkbox
    :Default: on

    Shows one select per category type in the form, as the site settings of
    :ref:`configuration-list-filter` decide. Switched off, the visitor cannot
    filter by category, and the list shows what :confval:`Categories
    <project-list-categories>` presets. The active filter tags of the categories
    are left out with the selects.

    The option stores the inverse, `settings.hideFilter`. The categories are
    preset only for a request without a selection of the visitor, so a visitor
    who changes the sorting or the state while the category selects are hidden
    gets the list without the preset categories.

..  confval:: Show sorting options
    :name: project-list-hide-sorting
    :type: checkbox
    :Default: on

    Shows the two sorting selects, :guilabel:`Sorting field` and
    :guilabel:`Sorting direction`, in the form. Switched off, the list is
    sorted as :confval:`Sorting <project-list-sorting>` says. With the sorting,
    the category filter and the state select all switched off, the element
    renders no form.

    The option stores the inverse, `settings.hideSorting`. The sorting is
    preset only for a request without a selection of the visitor, so a visitor
    who changes a category or the state while the sorting selects are hidden
    gets the list sorted by title, A to Z, not by the sorting chosen here.

..  confval:: Sorting
    :name: project-list-sorting
    :type: select
    :Default: Title A-Z

    The order of the list until the visitor picks another one:

    *   :guilabel:`Title A-Z`, :guilabel:`Title Z-A`: by the title of the page.
        The cards show the project title where there is one, so a list sorted
        by title is out of order where the two differ.
    *   :guilabel:`Last updated (latest last)`,
        :guilabel:`Last updated (latest first)`: by the date in the field
        :guilabel:`Last Update` of the page properties, which an editor sets
        by hand.
    *   :guilabel:`Page sorting (Result may be unexpected)`,
        :guilabel:`Page sorting, reversed (Result may be unexpected)`: by the
        position of each page among its sibling pages. That is the order of the
        page tree only while all project pages share one parent page.
    *   :guilabel:`Budget (low to high)`, :guilabel:`Budget (high to low)`: by
        the project budget.
    *   :guilabel:`Start date (oldest first)`,
        :guilabel:`Start date (newest first)`: by the project start.

..  confval:: Categories
    :name: project-list-categories
    :type: category tree
    :Default: empty

    Categories of the group `projects` the list is filtered by until the
    visitor selects categories of their own. A project has to carry every
    category selected here, so two categories of the same type find only the
    projects that carry both. A project that carries only a subcategory of a
    selected category is not found. Select at most one category per category
    type unless that is meant.

    The tree shows every category of the installation that belongs to a type
    of the group `projects`. A selection the visitor submits replaces the
    categories chosen here, and the active filter tags show them as tags the
    visitor can remove.

..  confval:: Show hidden records
    :name: project-list-show-hidden-records
    :type: checkbox
    :Default: off

    Lists hidden project pages as well, to every visitor, whatever the preview
    settings of the backend user say. Only the hidden state of a page is
    ignored: a start or end date, an access restriction to frontend user groups
    and a deleted page still keep a project out of the list. Use it for a list
    on an access restricted page that previews projects before they go online.

..  confval:: Show active state badge
    :name: project-list-show-active-state-badge
    :type: checkbox
    :Default: off

    Shows on every card whether the project is active or completed, see
    :ref:`configuration-active-state-badge`.

..  confval:: Selected pages for startingpoints
    :name: project-list-pages
    :type: page
    :Default: empty

    The pages the project pages sit in. The list shows the project pages that
    are direct subpages of one of the chosen pages. Project pages further down
    the page tree are not found, so choose every page or folder that holds
    projects. The form offers no :guilabel:`Recursive` field for this element.

    Empty, the list shows every project page of the installation, on every
    site. Only pages of the type :guilabel:`Academic project` are listed,
    whatever else lies below the chosen pages.

..  _configuration-active-state:

The state of a project
======================

A project is active while it has no end date or its end date is still ahead,
and completed once its end date has passed. The :guilabel:`Active state`
filter of the project lists selects by that rule, and a template reads the same
state from every project as :html:`{project.activeState}`: `active` or
`completed`, never empty.

A project page whose end date was never stored has no end date either, and is
active. That is a page created in the page tree, a page switched to the project
page type, an imported page or one from before the extension was installed,
as long as no editor has entered an end date. The :guilabel:`Active` filter
lists it, and the :guilabel:`Completed` filter does not.

..  _configuration-active-state-badge:

The state on the project cards
------------------------------

The option :guilabel:`Show active state badge` of the :guilabel:`Projects` and
the :guilabel:`Selected Projects` content elements shows the state on every
project card, with the labels :xml:`activeState.active` and
:xml:`activeState.completed` of this extension. It is off by default, and a
content element saved before the option existed stays without the badge.

The partial :file:`Project/Item.html` renders it below the title:

..  code-block:: html

    <p class="ace-state completed">Completed</p>

..  figure:: /Images/Screenshots/project-list-state-badge.png
    :alt: A project card showing the title, the state "Active", the short description and the categories
    :class: with-border with-shadow

    A project card with its state below the title.

An active project gets :html:`active` instead. That class names the state in
every language, so a site styles the two states by it. A
site package with a :file:`Project/Item.html` of its own shows the badge only
once it takes over that block.
