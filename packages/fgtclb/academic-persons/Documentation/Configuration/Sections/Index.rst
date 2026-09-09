..  index:: Configuration; Sections
..  _configuration-sections:

================
Profile sections
================

:file:`Configuration/AcademicPersons/Settings.yaml` describes a profile in four
top-level maps. It ships with :guilabel:`academic_persons`, which owns the
records and their TCA, and it is the one file the backend record editor, the
public detail view and the editing frontend of `EXT:academic_persons_edit
<https://extensions.typo3.org/extension/academic_persons_edit>`__ read.

..  list-table::
    :header-rows: 1

    *   -   Map
        -   Describes
    *   -   :yaml:`profile`
        -   The public detail layout (:yaml:`structure` and :yaml:`details`),
            and every directly editable profile property with its section,
            control and validators.
    *   -   :yaml:`special`
        -   The components of the editing frontend that are not one property:
            the composed display name, the image and the synchronisation
            switch.
    *   -   :yaml:`contracts`
        -   The contract fields, and the address, email and phone sections a
            contract owns.
    *   -   :yaml:`documentSections`
        -   The sortable lists attached to a profile: the seven timeline entry
            types and the contracts.

The order of every map and list is preserved and is what the editing frontend
renders. The :ref:`validator flags <configuration-validations>` are documented
on their own page.

..  attention::
    The syntax of this file is still considered experimental and may change in
    a future release.

..  _configuration-sections-profile:

The profile map
===============

Two keys describe the public layout, everything else is a field:

..  code-block:: yaml

    profile:
      structure:
        left:
          - menuSections
        right:
          - headline
          - position
          - profileImage
          - contact
          - subline
          - profileEntries
          - menuSectionsDatas
      details:
        headline:
          - title
          - firstName
          - middleName
          - lastName
        subline: 'LLL:EXT:academic_persons/Resources/Private/Language/locallang.xlf:detail.subline'
      gender:
        section: information
        fieldType: select
        renderType: select
        validators:
          - required
      firstName:
        section: information
        fieldType: input
        renderType: text
        validators:
          - readonly
          - disabled
        helptext: 'LLL:EXT:academic_persons/Resources/Private/Language/locallang.xlf:helptext.firstName'
      miscellaneous:
        section: aboutme
        fieldType: textarea
        renderType: ckeditor
        characterLimit: 1000
        validators:
          - html

:yaml:`structure`
    The layout columns and the ordered elements in each column. The shipped
    detail template renders ``left`` as the desktop navigation and ``right`` as
    the main content; on mobile, the ``left`` elements are inserted again
    directly before ``subline``.

:yaml:`details`
    Per element, the ordered profile properties, the relation map, the label
    reference or the special renderer it renders. Supported elements are
    :yaml:`menuSections`, :yaml:`headline`, :yaml:`position`,
    :yaml:`profileImage`, :yaml:`contact`, :yaml:`subline`,
    :yaml:`profileEntries` and :yaml:`menuSectionsDatas`; an unknown element
    renders nothing. :yaml:`position` and :yaml:`contact` take the special
    renderer :yaml:`special: datasFromContracts`. :yaml:`menuSections` lists
    stable navigation identifiers and :yaml:`menuSectionsDatas` maps each of
    them to the profile relation it shows.

..  _configuration-sections-profile-rendering:

How the layout is rendered
--------------------------

The shipped :file:`Resources/Private/Templates/Profile/Detail.html` receives
the two keys as ``publicProfile`` and dispatches every identifier of
:yaml:`structure` to a partial of the same name below
:file:`Resources/Private/Partials/Profile/PublicProfile/`:

..  list-table::
    :header-rows: 1

    *   -   Element
        -   :yaml:`details` entry
        -   Renders
    *   -   :yaml:`menuSections`
        -   Ordered navigation identifiers
        -   One link per identifier whose relation has records
    *   -   :yaml:`headline`
        -   Ordered profile properties
        -   The non-empty ones as the parts of the heading
    *   -   :yaml:`position`
        -   :yaml:`special: datasFromContracts`
        -   The position of every contract
    *   -   :yaml:`profileImage`
        -   Ordered image properties
        -   Every non-empty one, as a figure
    *   -   :yaml:`contact`
        -   :yaml:`special: datasFromContracts`
        -   Email addresses, phone numbers, postal addresses and location with
            room of every contract
    *   -   :yaml:`subline`
        -   A label reference
        -   The translated heading, and the point before which the
            :yaml:`left` elements are repeated below the large breakpoint
    *   -   :yaml:`profileEntries`
        -   Ordered rich text properties
        -   The non-empty ones as fold-out entries
    *   -   :yaml:`links`
        -   Ordered link properties
        -   The non-empty ones, each with its companion title property
            (:yaml:`website` with ``websiteTitle``) as the link text
    *   -   :yaml:`menuSectionsDatas`
        -   Navigation identifier to relation map
        -   One timeline section per identifier whose relation has records

Overriding a partial changes how an element renders, overriding
:yaml:`profile` changes what renders and where. The dates of a timeline entry
are printed as a single date, a range, or an open range prefixed with a "since"
or "till" label. How much of each of them a visitor sees is the
:ref:`date configuration <configuration-sections-dates>` of the entry's own
section, and the parts that are shown are formatted for the locale of the
matched site language.

Below the large breakpoint the elements of the :yaml:`left` column are
rendered a second time, directly before the :yaml:`subline` element of the
:yaml:`right` column. A layout without :yaml:`subline` in :yaml:`right`
therefore has no mobile navigation.

The view is a content element and renders no ``<main>``, no ``<aside>`` and no
``<h1>`` - a page may carry two profile plugins, and those belong to the page
template. Its headings start at ``<h2>`` for the headline, with the block
headings one level below it.

The template loads the stylesheet
:file:`Resources/Public/Css/frontend/profile-detail.css` and the module
``@fgtclb/academic-persons/frontend/profile.js`` through the asset collector.
The module toggles the fold-out entries, keeps the sticky navigation below a
page header with the id ``page-header`` and, when the site loads Bootstrap,
marks the section in view through its ScrollSpy. The icons of the contact rows
and the fold-out entries are the identifiers ``academic-persons-envelope``,
``academic-persons-phone``, ``academic-persons-address``,
``academic-persons-room``, ``academic-persons-detail-plus`` and
``academic-persons-detail-minus`` of :file:`Configuration/Icons.php`; a site
package re-registers an identifier to replace the glyph. They are `Bootstrap
Icons <https://icons.getbootstrap.com/>`__, and their MIT licence ships beside
them in :file:`Resources/Public/Icons/LICENSE-bootstrap-icons.txt`.

The colours of the view are custom properties declared on the
``.academic-persons-detail`` root element - ``--academic-persons-detail-text``,
``--academic-persons-detail-border`` and the two accents. Redeclaring them on
that class in the site's own stylesheet is how the view is themed; the shipped
stylesheet touches nothing outside that element.

..  note::
    The navigation of the :yaml:`left` column is sticky. A theme that wraps its
    content sections in ``overflow: hidden`` clips it, and the extension
    deliberately does not override that from its own stylesheet. Lift it in the
    site's stylesheet on the wrapper that has it, for example:

    ..  code-block:: css

        body:has(.academic-persons-detail) .my-theme-section {
            overflow: unset;
        }

..  _configuration-sections-detail-override:

What an override of the detail template loses
---------------------------------------------

Until 2.4 :file:`Templates/Profile/Detail.html` was the view: it rendered the
image, the contact data and every timeline section itself. Since 3.0.0 it is a
dispatcher over the :yaml:`structure` map, and the eleven partials below
:file:`Partials/Profile/PublicProfile/` are what renders.

**A project that overrides the template keeps rendering its own copy.** Nothing
looks broken, and two things are silently gone:

*   **The configurable layout.** :yaml:`profile.structure` and
    :yaml:`profile.details` are handed to the template as ``publicProfile`` and
    are read by the new partials only, so changing them has no effect at all
    while the old template renders.
*   **Three partials the detail view no longer renders.**
    :file:`Partials/Profile/Header.html` and
    :file:`Partials/Profile/SectionHeader.html` are still shipped and still
    rendered - by the list and card views, and by the profile editing view of
    :guilabel:`academic_persons_edit` - so an override of one of them made for
    the *detail* view no longer reaches it.
    :file:`Partials/Profile/DataHeader.html` had the detail view as its only
    caller and is **deleted**: a project template that still renders
    ``Profile/DataHeader`` fails at render time rather than rendering nothing.
    :ref:`breaking-public-profile-detail-partials` has the migration.

Adopt the new template instead, and move the project's changes into the partial
of the element they belong to: they are one file per element, and overriding one
of them is what :ref:`configuration-sections-profile-rendering` describes.

..  _configuration-sections-fields:

The fields
----------

Every other key is a field, and fields share one shape across
:yaml:`profile`, :yaml:`contracts.fields` and
:yaml:`contracts.contactSections.<section>.fields`:

..  list-table::
    :header-rows: 1

    *   -   Key
        -   Meaning
    *   -   :yaml:`section`
        -   Profile fields only. Fields with the same section are grouped and
            rendered together, in file order; the first field of a section
            decides where the section appears.
    *   -   :yaml:`propertyName`
        -   The domain and form data property, when it differs from the key.
            Optional.
    *   -   :yaml:`fieldName`
        -   The database column, when it differs from the underscored property
            name. Optional.
    *   -   :yaml:`fieldType`
        -   :yaml:`input`, :yaml:`select`, :yaml:`textarea` or :yaml:`check`.
            Describes the frontend control; **the TCA column keeps the type its
            TCA file declares**.
    *   -   :yaml:`renderType`
        -   The renderer of the editing frontend: :yaml:`text`, :yaml:`select`,
            :yaml:`checkbox`, :yaml:`email`, :yaml:`phone`, :yaml:`date`,
            :yaml:`combinedLink` or :yaml:`ckeditor`.
    *   -   :yaml:`validators`
        -   The :ref:`flag list <configuration-validations-flags>`.
    *   -   :yaml:`characterLimit`
        -   Rich text fields (:yaml:`renderType: ckeditor`) only: the maximum
            number of readable characters. Checked on the server, never copied
            into the TCA.
    *   -   :yaml:`date`
        -   Date fields (:yaml:`renderType: date`) only: how much of a date the
            editor is asked for and how much of it a visitor sees. See
            :ref:`configuration-sections-dates`.
    *   -   :yaml:`helptext`
        -   A label reference or literal text rendered next to the control.
    *   -   :yaml:`autocomplete`
        -   Contract and contact fields only: an HTML ``autocomplete`` token.
    *   -   :yaml:`options`
        -   Contract selects only: :yaml:`organisationalUnits`,
            :yaml:`functionTypes` or :yaml:`locations`.

A field is dropped silently when it has no section (profile fields), no
:yaml:`fieldType` or no :yaml:`renderType`. Removing a field from the file
removes it from the editing frontend and from the validation; it never removes
a column or stored data.

..  _configuration-sections-special:

The special map
===============

..  code-block:: yaml

    special:
      title:
        type: special
        renderType: title
        fields:
          - title
          - firstName
          - middleName
          - lastName
      image:
        type: special
        renderType: cropper
      skipSync:
        type: special
        fieldType: check
        renderType: checkbox

:yaml:`title` composes the display name from the listed profile properties,
:yaml:`image` is the profile image and :yaml:`skipSync` the switch that keeps
a profile out of the synchronisation from its frontend user. A special entry
with a :yaml:`fieldType` and without composed :yaml:`fields` addresses one
profile column directly and takes part in the profile validation; the other
two do not.

..  _configuration-sections-contracts:

The contracts map
=================

..  code-block:: yaml

    contracts:
      label: 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_profile.columns.contracts.label'
      type: contracts
      fieldName: contracts
      rowFields:
        - position
      actions:
        - view
        - down
        - up
        - delete
        - edit
      fields:
        position:
          fieldType: input
          renderType: text
          validators:
            - required
        organisationalUnit:
          fieldType: select
          renderType: select
          options: organisationalUnits
        validFrom:
          fieldType: input
          renderType: date
          validators:
            - required
            - date
      contactSections:
        emailAddresses:
          fields:
            emailAddress:
              propertyName: email
              fieldName: email
              fieldType: input
              renderType: email
              autocomplete: email
              validators:
                - required
                - email
            emailAddressType:
              propertyName: type
              fieldName: type
              fieldType: select
              renderType: select

:yaml:`fields` are the contract fields in editor order. The three contact
sections - :yaml:`physicalAddresses`, :yaml:`emailAddresses` and
:yaml:`phoneNumbers` - each carry their own :yaml:`fields` map. Their keys
are unique across the file, which is why :yaml:`emailAddress` names the
``email`` property and column and each ``<section>Type`` key names the
``type`` property of its own record.

:yaml:`label`, :yaml:`type`, :yaml:`fieldName`, :yaml:`rowFields` and
:yaml:`actions` complete the :yaml:`contracts` entry of the document sections
below.

..  _configuration-sections-documents:

The document sections
=====================

..  code-block:: yaml

    documentSections:
      contracts:
        type: contracts
      publications:
        label: 'LLL:EXT:academic_persons/Resources/Private/Language/locallang_tca.xlf:tx_academicpersons_domain_model_profile.columns.publications.label'
        type: publication
        fieldName: publications
        rowFields:
          - date
          - title
        actions:
          - view
          - down
          - up
          - delete
          - edit
        validators:
          title:
            - required
          link:
            - url
          from:
            - date
          to:
            - date
          date:
            - required
            - date
          description:
            editor:
              limit: 500
              type: ckeditor
        dates:
          date:
            display:
              year: true
              month: false
              day: false

Each key is a stable section identifier; the map order is the display order.

..  list-table::
    :header-rows: 1

    *   -   Key
        -   Meaning
    *   -   :yaml:`label`
        -   The label reference of the section heading.
    *   -   :yaml:`type`
        -   The record type of the rows, i.e. the ``type`` of the profile
            information records. :yaml:`contracts` is the reserved value for
            the contract section, which takes everything it does not declare
            from the top-level :yaml:`contracts` map.
    *   -   :yaml:`fieldName`
        -   The profile relation the rows hang off.
    *   -   :yaml:`readonly`
        -   :yaml:`true` disables creation and every mutating action; the
            section still offers ``view``.
    *   -   :yaml:`rowFields`
        -   The values shown in a compact row, in order. Timeline entries
            support ``from``, ``to``, ``date``, ``title`` and ``description``;
            contracts support ``from``, ``to`` and ``position``.
    *   -   :yaml:`actions`
        -   The actions offered per row, in order: ``view``, ``down``, ``up``,
            ``delete`` and ``edit``. An action not listed is not available.
            Listing both ``up`` and ``down`` also enables drag sorting.
    *   -   :yaml:`validators`
        -   A map from field to flag list, or to a map with :yaml:`validators`,
            individual ``<flag>: true`` entries and an :yaml:`editor` block.
            :yaml:`editor.type: ckeditor` implies the ``html`` flag and takes a
            readable-text :yaml:`limit`; :yaml:`editor.type: textarea` implies
            ``textarea``. The contract section validates against
            :yaml:`contracts.fields` instead.
    *   -   :yaml:`dates`
        -   A map from field to its :ref:`date configuration
            <configuration-sections-dates>`. It sits next to :yaml:`validators`
            rather than inside it, and a field that only this map names is
            known to the section all the same.
    *   -   :yaml:`helptext`
        -   A map from field to label reference.

..  warning::
    :yaml:`type` and :yaml:`fieldName` describe the editing frontend. The seven
    profile relations, and the record type each of them selects, are declared
    by the TCA of the profile table since 3.0.0 and are **not** generated from
    this file any more. Renaming either of them for one of the seven shipped
    sections therefore leaves a backend inline column that stores one record
    type and a frontend editor that writes another - the records created in one
    context are invisible in the other. A section of an own record type needs
    its own column in a TCA override of the profile table; the loop over the
    seven relations in
    :file:`Configuration/TCA/tx_academicpersons_domain_model_profile.php` is the
    template for it.

The validators of a timeline section address the record type of that section
only: a required title of publications does not make the title of a lecture
required, neither in the editing frontend nor in the backend, where the flags
land in the ``columnsOverrides`` of that record type. The field keys ``from``,
``to`` and ``description`` are aliases of the ``dateStart``, ``dateEnd`` and
``bodytext`` properties (columns ``date_start``, ``date_end`` and
``bodytext``); ``date`` addresses the ``date`` property and column. The
:yaml:`validators`, :yaml:`dates` and :yaml:`helptext` maps are all keyed by
the alias, not by the property.

Unknown row fields and actions are discarded, as are duplicates; both lists
are matched without regard to case.

..  _configuration-sections-dates:

Date fields
===========

A date field is refined by a :yaml:`date` block: how much of a date its editor
is asked for, how the parts nobody was asked for are completed, and how much of
the result a visitor is shown. Everything in it is optional and every
unreadable value falls back to its own default, so a typo narrows nothing and
publishes nothing by accident. A date field without the block asks for a
complete date and shows all of it.

**A non-empty block makes the field a date on its own.** The :yaml:`date`
validator flag next to it says the same thing and stays worth writing, but the
block does not wait for it: configuring how much of a date is published, or how
much of it is asked for, states that the field is a date more plainly than the
flag does, and a block that took effect only when the flag was remembered as
well would be a trap rather than a shorthand.

A :yaml:`profile` field or a :yaml:`contracts.fields` field carries the block on
the field entry itself, next to its scalar :yaml:`helptext`:

..  code-block:: yaml

    contracts:
      fields:
        validFrom:
          fieldType: input
          renderType: date
          validators:
            - required
            - date
          date:
            display:
              year: true
              month: true
              day: true

A document section names its fields in maps rather than in one entry per field,
so it keeps the same blocks in a :yaml:`dates` map keyed by field identifier,
next to :yaml:`validators` and :yaml:`helptext` - and *next to* is meant
literally. :yaml:`dates` is not a refinement of :yaml:`validators`: a field
that only :yaml:`dates` names is known to the section all the same and carries
an empty flag list, the independence :yaml:`helptext` has always had, where a
field never needed a :yaml:`validators` entry to be given a helptext either.
:yaml:`validators` decides the order of the fields; anything only :yaml:`dates`
knows follows it.

..  code-block:: yaml

    documentSections:
      publications:
        rowFields:
          - date
          - title
        validators:
          date:
            - required
            - date
        dates:
          date:
            display:
              year: true
              month: false
              day: false
            input:
              granularity: date
              completeMonth: first
              completeDay: first

..  _configuration-sections-dates-display:

What a visitor is shown
-----------------------

The three switches of :yaml:`display` are independent and each default to
:yaml:`true`. The parts that are switched on are written the way the locale of
the **matched site language** writes exactly those parts - not by cutting a
longer format short - so the same entry reads differently on a German and on an
English page without any per-language configuration, and the settings of the
visitor's browser do not change it. For the 14th of March 2019:

..  code-block:: text

    year, month, day    de-DE  14.03.2019      en-US  Mar 14, 2019
    year, month         de-DE  März 2019       en-US  Mar 2019
    year                de-DE  2019            en-US  2019
    month, day          de-DE  14. März        en-US  Mar 14

A complete date keeps the notation TYPO3's ``MEDIUMDATE`` produces, which is
what the contract rows and the editor already rendered. A month standing on its
own is spelled out, a month next to another part abbreviated. An empty date
renders nothing at all - no placeholder, no zero and no current date - and so
does a field with all three switches off: it is stored and edited, and never
shown.

The public profile needs no format in a template for this. The ViewHelper
:php:`\FGTCLB\AcademicPersons\ViewHelpers\Format\ProfileInformationDateViewHelper`
resolves the section from the record type and the field from the property name:

..  code-block:: html

    <html xmlns:ap="http://typo3.org/ns/FGTCLB/AcademicPersons/ViewHelpers">
        <ap:format.profileInformationDate date="{item.dateStart}" type="{item.type}" field="dateStart"/>
    </html>

..  _configuration-sections-dates-input:

What the editor is asked for
----------------------------

:yaml:`input.granularity` chooses the control the editing frontend renders, and
the control decides what a browser offers and what it submits:

..  code-block:: text

    date    <input type="date">     submits  2019-03-14
    month   <input type="month">    submits  2019-03
    year    <input type="number">   submits  2019

:yaml:`date` is the default. A year is a number control carrying
``min="1000"``, ``max="9999"`` and ``step="1"``, because no browser has a year
input. The editing frontend enforces those two bounds on the server as well and
refuses a year outside them rather than clamping it; the other two
granularities have no bounds.

The lower bound is the lowest four-digit year: a year is exchanged as ``Y`` and
read back strictly, and a number control cannot emit the leading zeros a year
below 1000 needs. A record that reaches further back is configured for the
:yaml:`date` granularity, whose control writes a full ``0800-01-01``.

..  note::
    Desktop Firefox has never implemented :html:`<input type="month">` and
    degrades it to a plain text field. The value it submits is the same
    ``2019-03`` and the server validates it either way, so the form stays
    usable there - it simply has no calendar.

:yaml:`input.completeMonth` and :yaml:`input.completeDay` fill the parts the
editor was not asked for, and both default to :yaml:`first`. They are the four
rules an integrator can name, on their two axes: a :yaml:`first` or
:yaml:`last` month of the year, and a :yaml:`first` or :yaml:`last` day of the
month. A last day is the last day of the *resulting* month, so a last day of a
last month of 2019 is the 31st of December and a last day of February 2020 is
the 29th. A part the editor did give is never completed over.

Where a field publishes less than it asks for, the editing frontend tells the
editor so at the field. The `profile editing chapter
<https://docs.typo3.org/p/fgtclb/academic-persons-edit/main/en-us/ProfileEditing/Index.html>`__
of :guilabel:`academic_persons_edit` describes the control and the hint.

..  _configuration-sections-dates-shipped:

What is shipped
---------------

All three dates of all seven timeline sections declare :yaml:`year: true` with
:yaml:`month` and :yaml:`day` switched off, and no :yaml:`input` block. The
editor therefore enters a complete date and a visitor is shown the year alone,
which is exactly what the integer years of 2.x showed. Switch :yaml:`month` and
:yaml:`day` on to publish them, or add an :yaml:`input` block to ask for less
than a complete date.

The two contract dates :yaml:`validFrom` and :yaml:`validTo` carry no
:yaml:`date` block, so they ask for a complete date and publish all of it.

..  _configuration-sections-override:

Overriding the file
===================

The file is collected from **all installed extensions**: every package that
contains :file:`Configuration/AcademicPersons/Settings.yaml` contributes, and
the package loaded last wins. The files are merged on the **top level only** -
a site package that defines :yaml:`profile` replaces the shipped
:yaml:`profile` map completely, layout and fields alike, and the maps it does
not mention stay as shipped. There is no deep merge and no syntax for changing
a single flag of a single field.

To change the shipped configuration:

#.  Add :file:`Configuration/AcademicPersons/Settings.yaml` to your site
    package.
#.  Make the site package **depend on** :guilabel:`academic_persons` in its
    :file:`composer.json` or :file:`ext_emconf.php`, so that it is loaded after
    it.
#.  Copy the complete map you want to change from
    :file:`EXT:academic_persons/Configuration/AcademicPersons/Settings.yaml`
    and edit the copy.
#.  Flush the TYPO3 caches. The normalised graph is cached in the core cache.

There is no TypoScript and no site set equivalent for these settings.

..  note::
    The backend record editor reads the same maps. Unlocking a field for the
    editing frontend, or requiring one, changes the backend form of that record
    the same way.
