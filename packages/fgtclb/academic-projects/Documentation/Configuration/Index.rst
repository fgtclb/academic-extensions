:navigation-title: Configuration

..  _configuration:

=============
Configuration
=============

This extension ships its frontend TypoScript and its backend page TSconfig in
two forms: as TYPO3 **site sets**, and as classic **static templates** plus
**page TSconfig files** that are selected on a page. Both forms read the very
same files, so they configure an installation identically.

Pick one of them per site and stay with it — see
:ref:`Do not combine both <one-mechanism-per-site>` for what happens otherwise.

..  _configuration-components:

What the sets contain
=====================

This extension ships two content elements, so it ships two component sets
and one aggregate set that depends on all of them.

Both content elements are driven by one Extbase plugin, so they share one
TypoScript block, :typoscript:`plugin.tx_academicprojects`. That block is
shipped once, in :file:`Configuration/TypoScript/`, and every component includes
it. Which component sets a site names therefore decides which content elements
the backend offers, not how much TypoScript is loaded.

..  list-table::
    :header-rows: 1

    *   -   Set
        -   Delivers
    *   -   `fgtclb/academic-projects-project-list`
        -   The :guilabel:`Projects` content element.
    *   -   `fgtclb/academic-projects-project-list-single`
        -   The :guilabel:`Projects (selected)` content element.
    *   -   `fgtclb/academic-projects`
        -   Everything above. This is the set to use unless you deliberately
            want a subset, and it is the name this extension published before
            the sets were cut per component — a site configuration that depends
            on it needs no change.

Every content element set depends on `fgtclb/academic-base-ctype-group`, the set
of :guilabel:`EXT:academic_base` that labels the content element group all
academic extensions sort their elements into.

..  _configuration-hidden-by-default:

The content elements are hidden by default
==========================================

:guilabel:`EXT:academic_projects` hides both of its content elements for the
whole installation and brings them back per component. Whichever of the two
mechanisms below you use, it is what makes an element selectable in the backend
again — without one of them the content element is not offered, and existing
records keep rendering.

..  warning::

    This changed in version 2.4. Before it, both elements were selectable on
    every page of every installation. Read
    :ref:`Breaking: Site sets and static templates have been restructured
    <breaking-site-sets-and-static-templates-restructured>` before upgrading:
    opening an existing record on a page that does not include the page TSconfig
    of its component can rewrite the type of that record.

What the sets do not control
============================

The page type :guilabel:`Academic project` (doktype 30) and its backend layout
:guilabel:`AcademicProject` are **not** part of any set, and enabling or not
enabling a set never changes them.

That is deliberate, not an oversight. Both are values stored on :sql:`pages`
records: a page carries `doktype = 30` and `backend_layout = pagets__AcademicProject`
long before any site configuration is read. Were they delivered by an opt-in
set, every page tree on a site that does not use that set would show
:guilabel:`[ MISSING LABEL ]` for the layout, the layout could not be picked for
a new page, and the page type would disappear from the page tree wizard.

They are therefore registered installation-wide — the page type in TCA
(:file:`Configuration/TCA/Overrides/pages.php`), the backend layout in the
always-included :file:`Configuration/page.tsconfig` — and stay available on every
site of the installation.

What a set does deliver for that page type is its **frontend rendering**: the
:typoscript:`page` object that picks the Fluid template of the page type is part
of the shared TypoScript block, so a site that includes no set of this extension
renders such a page with whatever its own site package defines.

..  _project-page-content:

The content of a project page
=============================

A project page renders the content elements of its main column
(:typoscript:`colPos = 0`) below the project data, in their manual order and in
the language of the page. Any set of this extension, or the static template of
the shared block, delivers that, and no other set is needed for it.

The content is the variable :typoscript:`projectContent` of the page object,
defined inside the condition on the project page type, so it exists on project
pages only. It is a :typoscript:`CONTENT` object that renders the records
through the :typoscript:`tt_content` object of the site, and it works for a
:typoscript:`FLUIDTEMPLATE` and a :typoscript:`PAGEVIEW` page object alike.

To render another column, or to slide the content from the parent pages, change
the variable inside the same condition:

..  code-block:: typoscript
    :caption: EXT:my_sitepackage/Configuration/TypoScript/setup.typoscript

    [page && traverse(page, "doktype") == 30]
      page.10.variables.projectContent {
        select.where = {#colPos}=1
      }
    [END]

A page template of your own renders it as
:html:`{projectContent -> f:format.raw()}`.

..  versionchanged:: 3.0

    Up to 2.x the page template rendered the global object
    :typoscript:`styles.content.getContent`, which only the set
    `fgtclb/academic-projects-content-load` defined for the whole site. The set
    and its static template are removed, see
    :ref:`breaking-projects-content-load-set-removed`.

..  note::

    The page type renders the short description and the funders of a project,
    and the project list its short description, through
    :html:`<f:format.html>` and therefore through the site's
    :typoscript:`lib.parseFunc_RTE`. TYPO3 defines that path for every site, so
    it needs no configuration; a site that refines it changes how links, allowed
    tags and paragraphs of these fields are rendered, exactly as for every other
    rich text field.

..  _project-page-layout:

The layout of a project page
============================

A project page renders inside the page layout of the site package, the way the
other pages of the site do: the page template declares a layout and fills its
section :html:`Main`. The layout is :file:`Default` unless a setting names
another one, which is what :composer:`bk2k/bootstrap-package` and most site
packages provide.

..  list-table::
    :header-rows: 1

    *   -   Site setting / constant
        -   Default
        -   Meaning
    *   -   :typoscript:`plugin.tx_academicprojects.page.layout`
        -   `Default`
        -   The Fluid layout of the site package the project page renders its
            section :html:`Main` into. An empty value is read as `Default`.

It is a site setting of the aggregate set `fgtclb/academic-projects`, and a
constant of the same name for a site on the static templates. A site that
depends on a component set alone gets the default, but the site settings editor
does not offer the setting there. Depend on the aggregate set to configure it.

The page template reaches it as the variable :typoscript:`projectPageLayout` of
the page object, so it works on a :typoscript:`FLUIDTEMPLATE` and a
:typoscript:`PAGEVIEW` page object alike.

A site package without a layout :file:`Default` gets the fallback layout of this
extension, which renders the section :html:`Main` and nothing else: the page
renders without the header, navigation and footer of the site, as it did up to
2.x, rather than failing. A layout :file:`Default` of the site package wins over
it. The fallback exists for :file:`Default` only: a layout the setting names
has to exist in the site package, or the project page fails as any page with a
missing Fluid layout does.

..  _project-page-partials:

The parts of a project page
---------------------------

The section :html:`Main` renders five partials, each of which can be replaced
on its own:

..  list-table::
    :header-rows: 1

    *   -   Partial
        -   Renders
    *   -   :file:`Project/Page/Header.html`
        -   The project title, or the title of the page without one, the
            subtitle of the page and the short description, in one element with
            the class `academic-projects-detail__header`.
    *   -   :file:`Project/Page/Media.html`
        -   The first image of the page, through the shared image partial of
            :guilabel:`EXT:academic_base`.
    *   -   :file:`Project/Page/Categories.html`
        -   The categories assigned to the page, grouped by category type.
    *   -   :file:`Project/Page/Facts.html`
        -   The runtime, the budget and the funders of the project.
    *   -   :file:`Project/Page/Content.html`
        -   The content elements, the variable :typoscript:`projectContent`
            described above.

Every partial receives all variables of the page: :html:`{project}`,
:html:`{images}`, :html:`{projectContent}`, :html:`{pageRecord}` and those of the
site package's page object. :html:`{pageRecord}` is the record of the page on
both page object types, :html:`{data}` of a :typoscript:`FLUIDTEMPLATE` page
object and :html:`{page.pageRecord}` of a :typoscript:`PAGEVIEW` one. The
subtitle is its field :html:`{pageRecord.subtitle}`.

The templates and partials of the page type are registered at the key `50` of
the page object. Register a directory of your own with a higher key, and its
files win:

..  code-block:: typoscript
    :caption: EXT:my_sitepackage/Configuration/TypoScript/setup.typoscript

    # FLUIDTEMPLATE: a directory holding Project/Page/Header.html
    page.10.partialRootPaths.75 = EXT:my_sitepackage/Resources/Private/Partials/

    # PAGEVIEW: a directory holding Partials/Project/Page/Header.html
    page.10.paths.75 = EXT:my_sitepackage/Resources/Private/

A site package that registers its own paths above `50` needs no line at all -
a :typoscript:`PAGEVIEW` site package at :typoscript:`paths.100`, for example:
a :file:`Partials/Project/Page/Header.html` of its own wins already. An override of
the whole :file:`Pages/AcademicProject.html` keeps working the same way, and
renders without a layout, as before.

..  versionchanged:: 3.0

    Up to 2.x the page template declared no layout and rendered every part
    inline, and its paths used the key `100`. See
    :ref:`breaking-project-page-renders-inside-the-site-layout`.

..  _site-set:

Include the site set
====================

Add the set to the :file:`config.yaml` of the site that should offer the content
elements:

..  code-block:: diff
    :caption: config/sites/my-site/config.yaml (diff)

     base: 'https://example.com/'
     rootPageId: 1
    +dependencies:
    +  - fgtclb/academic-projects

See also `TYPO3 Explained, Using a site set as dependency in a site
<https://docs.typo3.org/permalink/t3coreapi:site-sets-usage>`__.

..  _static-templates:

Include static templates
========================

For an installation that still configures its frontend through
:sql:`sys_template` records, the same files are registered as static templates
and as selectable page TSconfig files.

..  tip::

    On TYPO3 v13 and v14 we recommend the site set — and if you use it, do not
    press the backend button :guilabel:`Create a root TypoScript record` on that
    site. The :sql:`sys_template` record it creates carries the flag
    :guilabel:`Clear` for constants and setup, and that flag discards everything
    the site sets contributed. An installation that is already in that state
    gets its configuration back by selecting the static templates below in that
    very record.

..  _static-typoscript:

Include static TypoScript
-------------------------

Edit the :sql:`sys_template` record of the site root and add the entry to
:guilabel:`Include static (from extensions)`:

..  list-table::
    :header-rows: 1

    *   -   Entry
        -   Delivers
    *   -   :guilabel:`Academic Projects: Projects (academic_projects)`
        -   The TypoScript of the :guilabel:`Projects` content element.
    *   -   :guilabel:`Academic Projects: Projects (selected) (academic_projects)`
        -   The same for :guilabel:`Projects (selected)`.
    *   -   :guilabel:`Academic Projects: All components (academic_projects)`
        -   Every component this extension ships, in one entry.
    *   -   :guilabel:`Academic Projects: Shared plugin settings and page
            rendering (academic_projects)`
        -   The shared :typoscript:`plugin.tx_academicprojects` block and the
            :typoscript:`page` object of the page type, on their own. This is
            the entry an installation stored before the configuration was cut
            per component, and it keeps working — but it does not make any
            content element selectable, which the page TSconfig below does.

..  _static-pagetsconfig:

Include static page TSconfig
----------------------------

Edit the page record of the site root, tab :guilabel:`Resources`, field
:guilabel:`Page TSconfig`, and add the entry:

..  list-table::
    :header-rows: 1

    *   -   Entry
        -   Delivers
    *   -   :guilabel:`Academic Projects: Projects (academic_projects)`
        -   Makes the :guilabel:`Projects` content element selectable, and
            configures its entry in the new content element wizard.
    *   -   :guilabel:`Academic Projects: Projects (selected) (academic_projects)`
        -   The same for :guilabel:`Projects (selected)`.
    *   -   :guilabel:`Academic Projects: All components (academic_projects)`
        -   Every component this extension ships, in one entry.

The setting is inherited by every page below the one it is set on.

..  _configuration-list-filter:

The category filters
====================

The filter form of the :guilabel:`Projects` and the :guilabel:`Projects
(selected)` content elements offers one select per category type of the group
`projects`. Three settings change which of them it offers and how, for the whole
site:

..  list-table::
    :header-rows: 1

    *   -   Site setting and constant
        -   Default
        -   Meaning
    *   -   :typoscript:`plugin.tx_academicprojects.filter.categoryTypes`
        -   empty
        -   The category types to offer, in this order, as a comma separated
            list of type identifiers, for example
            `project_department,competence_field`. Empty offers every type
            that has a category, in the order of the category types of the
            group.
    *   -   :typoscript:`plugin.tx_academicprojects.filter.visibleCount`
        -   0
        -   How many filters the form shows right away. The others follow in a
            :guilabel:`More filters` section the visitor opens, which is open
            already while one of its filters has a value. 0 shows every filter.
    *   -   :typoscript:`plugin.tx_academicprojects.filter.hideDisabledOptions`
        -   0
        -   Leaves out a category no listed project carries, instead of offering
            it as a disabled option. A selected category is always offered. A
            filter whose categories are all left out still renders, with its
            "All" option only.

..  code-block:: yaml
    :caption: config/sites/my-site/settings.yaml

    plugin:
      tx_academicprojects:
        filter:
          categoryTypes: 'project_department,competence_field'
          visibleCount: 1
          hideDisabledOptions: true

The settings are site settings of the aggregate set `fgtclb/academic-projects`
and constants of the same names for a site on static templates. A site that
depends on one of the component sets alone gets the defaults, but the site
settings editor does not offer the settings there — both content elements read
them, and a set declares settings only for itself.

A type is offered only when at least one category of that type exists, and an
identifier that is no type of the group is ignored. The settings decide what
the form offers, not what the list accepts: a link that filters by a category
of a type the form does not offer still filters the list.

The "All" option of a filter
----------------------------

The first option of each filter, the one that selects no category, reads the
label :xml:`sys_category.projects.allOptions.<type>` of this extension, and
falls back to :xml:`sys_category.projects.allOptions` ("All options") where a
type has none. The extension ships no label per type; a site adds them in
TypoScript, for every content element of the extension or for one plugin:

..  code-block:: typoscript
    :caption: EXT:my_sitepackage/Configuration/TypoScript/setup.typoscript

    plugin.tx_academicprojects._LOCAL_LANG {
      default.sys_category.projects.allOptions.competence_field = All competence fields
      de.sys_category.projects.allOptions.competence_field = Alle Kompetenzfelder
    }

    # Only in the Projects content element:
    plugin.tx_academicprojects_projectlist._LOCAL_LANG.default.sys_category.projects.allOptions.competence_field = All competence fields

A language file override works as well, as for any label of this extension:
:php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']` on TYPO3 v13,
:php:`$GLOBALS['TYPO3_CONF_VARS']['LANG']['resourceOverrides']` on TYPO3 v14,
each pointing from
:file:`EXT:academic_projects/Resources/Private/Language/locallang.xlf` to a file
of the site package.

Templates
---------

The partial :file:`Project/DemandCategories.html` renders the selects from the
variable :html:`{filterTypes}`: :html:`{filterTypes.visible}` and
:html:`{filterTypes.more}` hold the identifiers of the offered types, in their
order, before and behind :guilabel:`More filters`. Where that variable does not
reach the partial — a project controller that overrides :php:`listAction()`, or
a template that renders the partial with arguments of its own instead of
:html:`{_all}` — it offers every type with a category, as before, and the filter
types and the visible count have no effect there. To use them, let the
overriding action call the parent action, and pass :html:`filterTypes` on in a
template that renders the partial.

..  versionadded:: 2.4

    Up to 2.3 the form offered every type with a category, all of them right
    away and with one "All" label, and anything else needed an override of the
    partial.

..  _configuration-active-state:

The state of a project
======================

A project is active while it has no end date or its end date is still ahead,
and completed once its end date has passed. The :guilabel:`Active state`
filter of the project lists selects by that rule, and a template reads the same
state from every project as :html:`{project.activeState}`: `active` or
`completed`, never empty.

..  _configuration-active-state-badge:

The state on the project cards
------------------------------

The option :guilabel:`Show active state badge` of the :guilabel:`Projects` and
the :guilabel:`Projects (selected)` content elements shows the state on every
project card, with the labels :xml:`activeState.active` and
:xml:`activeState.completed` of this extension. It is off by default, and a
content element saved before the option existed stays without the badge.

The partial :file:`Project/Item.html` renders it above the title:

..  code-block:: html

    <p class="mb-2">
        <span class="badge text-bg-secondary academic-projects-item__state academic-projects-item__state--completed">Completed</span>
    </p>

An active project gets :html:`text-bg-success` instead. The modifier class
names the state in every language, so a site styles the two states by it. A
site package with a :file:`Project/Item.html` of its own shows the badge only
once it takes over that block.

A project whose end date column was never written, a page created before the
extension was installed or by an import that left the column out, has no end
date and is active. The :guilabel:`Active` filter does not list it yet, and
saving the page in the backend does not change that: the column keeps its
empty value as long as no end date is entered.

..  _configuration-crop-variants:

The crop variants of the project page media
===========================================

The image cropper of the media of a project page offers three crop variants. A
template requests one by its name, through the `cropVariant` argument of
:html:`<f:image>` or of the image partial of :guilabel:`EXT:academic_base`:

..  list-table::
    :header-rows: 1

    *   -   Name
        -   Aspect ratio
    *   -   `default`
        -   Free, 16:9, 3:2, 4:3 and 1:1
    *   -   `landscape`
        -   16:9
    *   -   `portrait`
        -   3:4

`default` is the variant TYPO3 offers when a file field configures none, with
the same ratios, and the templates of this extension render it. A crop an editor
stored before the update is stored under that name and keeps its meaning.

The variants belong to the project page type. The media of a standard page keeps
what TYPO3 offers.

An image stores a crop for the new variants once an editor opens it in the
backend form and saves the record. Until then a template that requests
`landscape` or `portrait` renders the image uncropped. Where the image already
has a crop for `default`, the cropper starts `landscape` from that crop, fitted
into its ratio, and `portrait` from the whole image, fitted and centred; an
image without a crop starts both from the whole image.

A site that does not want a variant disables it in TCA, on this field only. The
extension configures the variants in its own TCA overrides, so the site package
has to depend on academic_projects for its line to load later:

..  code-block:: php
    :caption: Configuration/TCA/Overrides of the site package

    $GLOBALS['TCA']['pages']['types'][30]['columnsOverrides']['media']['config']['overrideChildTca']['columns']['crop']['config']['cropVariants']['portrait']['disabled'] = true;

Page TSconfig is not the way to do that.
`TCEFORM.sys_file_reference.crop.config.cropVariants` reaches every image below
the page it is set on, and on an image field that configures no variants of its
own it leaves the cropper with no variant at all, not even `default`.

A project that defines crop variants of its own for the project page media does
so at the same path. A variant it sets by name replaces the one of the same name
and leaves the others; assigning the whole array replaces all of them.

Crop variants a project configures on the media field of every page, or on
`sys_file_reference` for every image, are merged with these on the project page:
the values of this extension win key by key, and a ratio the project adds to a
variant of the same name stays. A project that restricted `default` to a fixed
ratio that way therefore finds all the ratios of the TYPO3 default offered on
project pages again, and the free ratio preselected on an image without a crop.

..  _configuration-content-element-header:

The header of the content elements
==================================

The header and the subheader an editor enters on a :guilabel:`Projects` or
:guilabel:`Projects (selected)` content element are rendered by the content
element layout of the site, as for any other content element. The layouts of
:guilabel:`EXT:fluid_styled_content` and of the bootstrap package do that, and
the plugins render no header of their own.

A site whose content element layout renders no header, because its element
templates render it instead, lets the plugins render it:

..  code-block:: typoscript
    :caption: TypoScript constants

    plugin.tx_academicprojects.renderContentElementHeader = 1

On a site that uses the site set, that is the site setting :guilabel:`Project
lists | Render the content element header` of `fgtclb/academic-projects`. The
templates then render the header partial of :guilabel:`EXT:fluid_styled_content`
above their output, for every header layout except :guilabel:`Hidden`. Do not
switch it on where the layout renders the header: the header then appears twice.

The extension does not require :guilabel:`EXT:fluid_styled_content`. It adds the
partial path of that extension below every other one, so a site package that
ships a :file:`Header/All.html` of its own renders that one instead, and a site
without :guilabel:`EXT:fluid_styled_content` provides the partial that way.

For the header layout :guilabel:`Default`, the partial takes the heading level
from :typoscript:`plugin.tx_academicprojects.settings.defaultHeaderType`, which
is mapped from the constant :typoscript:`styles.content.defaultHeaderType` of
:guilabel:`EXT:fluid_styled_content`. A site that does not include the
TypoScript of :guilabel:`EXT:fluid_styled_content` sets the setting itself;
without it, such a header renders as an empty :html:`<header>` element.

..  _one-mechanism-per-site:

Do not combine both
===================

A site that uses the site set **and** the static template reads the shipped
files twice. The site set is applied before the :sql:`sys_template` record, so
the second read happens after the site settings and after
:file:`config/sites/<site>/constants.typoscript` — and it resets every constant
the extension ships a default for back to that default. For this extension that
is the :typoscript:`plugin.tx_academicprojects` constants block: the three Fluid
root paths, the settings of :ref:`the category filters
<configuration-list-filter>` and the :ref:`content element header
<configuration-content-element-header>` switch.

Nothing else is damaged: the :guilabel:`Constants` and :guilabel:`Setup` fields
of the :sql:`sys_template` record, the page TSconfig of a page and the page
TSconfig files selected on a page are all applied afterwards and still win. Use
one mechanism per site and the question does not arise.

..  _configuration-integration:

Search, permissions and the wizard
==================================

The `Integration chapter of academic_base
<https://docs.typo3.org/p/fgtclb/academic-base/main/en-us/Integration/Index.html>`__
covers what an installation runs beside the academic extensions: an index
queue for project pages with EXT:solr, the tables, fields and content types
an editor group needs as a preset for b13/permission-sets, and how to move,
rename or order the academic content elements in the new content element
wizard.

..  toctree::
   :maxdepth: 5
   :titlesonly:

   General/Index
   Labels/Index
