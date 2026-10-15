..  _integrator-study-plan:

==========
Study Plan
==========

..  image:: /Images/Icons/study-plan.svg
    :width: 48px
    :alt: Icon of the study plan content element

:CType:
    ``academic_study_plan``

:Set:
    `fgtclb/academic-study-plan-content-element`, also part of
    `fgtclb/academic-study-plan`, see :ref:`configuration-components`

:Templates:
    :file:`AcademicStudyPlan.html` and the partials of
    :file:`Partials/StudyPlan/`, see :ref:`templates`

The study plan shows the course of a study programme: its semesters side by
side as columns, and below each semester the modules taught in it, with their
credit points. It is the content element for the page of a study programme,
the program page of :guilabel:`academic_programs` for example, or for any page
that explains how a programme is structured.

A visitor sees:

*   **The semesters** as columns, each with its label, its credit points and
    its note. On a narrow viewport every semester is an accordion the visitor
    opens and closes.
*   **The modules** of a semester as a list, each with its label, its credit
    points and its note. A module with a description or an audio file opens a
    dialog with them.
*   **A filter by category** above the plan, one button for every category
    the modules of the plan carry. A button highlights the modules of its
    category. A plan whose modules carry no category shows no filter. The
    filter can start folded, see :ref:`collapsible-filter`. The colour of a
    category reaches the stylesheet of the site as the custom property
    ``--category-color`` of its button, and the colour of the highlight as
    ``--highlight-color`` of the plan. The extension ships no stylesheet, so
    the site decides where the colours show.
*   **The footer note** below the plan.

..  figure:: /Images/Screenshots/study-plan-overview.png
    :alt: Five semester columns with their modules and credit points, a row of category buttons above them and a note below.
    :class: with-border with-shadow

    A study plan with five semesters, the category filter above and the
    footer note below.

..  figure:: /Images/Screenshots/study-plan-narrow.png
    :alt: The study plan on a phone: the semesters stacked, the first one open with its modules, the others closed.
    :class: with-border with-shadow

    On a narrow viewport every semester is an accordion.

The filter, the accordion and the dialogs are driven by the script of the
extension, see :ref:`asset-switches`. The extension ships no stylesheet, the
site package styles the plan, see :ref:`styling`.

The header of the content element and the :guilabel:`Appearance`,
:guilabel:`Language` and :guilabel:`Access` tabs are those of every content
element. The fields of the study plan are on the :guilabel:`General` tab.

..  _integrator-study-plan-general:

Settings
========

The two fields are columns of the content element itself. There is no
TypoScript or site setting behind them, and nothing replaces an empty field.

..  confval:: Semesters
    :name: study-plan-semesters
    :type: records
    :Default: empty

    The column ``tx_academicstudyplan_semesters``. The semesters of the
    plan, in the order the visitor sees them from left to right. An editor
    creates them in the content element, sorts them by drag and drop and
    hides one with its :guilabel:`Visible` switch. A content element holds
    up to 99 semesters, a semester up to 99 modules. A study plan without a
    semester shows no columns, only its footer note.

    A semester has these fields:

    ..  list-table::
        :header-rows: 1
        :widths: 25 75

        *   -   Field
            -   What the visitor sees
        *   -   :guilabel:`Label`
            -   The heading of the column, ``Semester 1`` for example.
                Required.
        *   -   :guilabel:`Note`
            -   A short line below the heading, ``Practical semester`` for
                example. Not shown when empty.
        *   -   :guilabel:`Credit Points`
            -   The credit points of the semester, with up to two decimals,
                followed by the label :guilabel:`CP`. Not shown when it is 0.
                The plan does not add up the credit points of the modules, the
                editor enters the sum.
        *   -   :guilabel:`Modules`
            -   The modules of the semester, in the order of the list, see
                below.

    A module has these fields:

    ..  list-table::
        :header-rows: 1
        :widths: 25 75

        *   -   Field
            -   What the visitor sees
        *   -   :guilabel:`Label`
            -   The name of the module. Required.
        *   -   :guilabel:`Note`
            -   A short line with the name, in the list and in the dialog.
                Not shown when empty.
        *   -   :guilabel:`Credit Points`
            -   As for a semester, in the list and in the dialog.
        *   -   :guilabel:`Description`
            -   Plain text shown in the dialog of the module, line breaks
                kept. A module with neither a description nor an audio file
                opens no dialog.
        *   -   :guilabel:`Audio File`
            -   One audio file played in the dialog, an introduction to the
                module for example. MP3, WAV, OGG, M4A, AAC, FLAC and WebM
                files are accepted.
        *   -   :guilabel:`Categories`
            -   The study plan categories the module belongs to, mandatory or
                elective for example. They make up the filter and colour the
                highlight.

    ..  figure:: /Images/Screenshots/study-plan-module-dialog.png
        :alt: A dialog titled Programming I with the credit points, a close button, the description of the module and an audio player.
        :class: with-border with-shadow

        The dialog of a module with its credit points, description and audio
        file.

    The :guilabel:`Categories` select offers every study plan category of the
    installation, whatever folder it is stored in, sorted by label. A
    category is a record of its own with a :guilabel:`Label` and a
    :guilabel:`Colour`, and one set of categories usually serves every plan
    of a site, so keep them in one folder. The filter shows the categories in
    the order the modules of the plan first carry them.

    ..  figure:: /Images/Screenshots/study-plan-category-highlight.png
        :alt: The study plan with the Mandatory button pressed and the mandatory modules of the semesters tinted blue.
        :class: with-border with-shadow

        The filter button Mandatory highlights the modules of that category.

    **Translations.** The plan is built from the semesters and modules of
    the content element in the default language. Translate the content
    element in connected mode, with :guilabel:`Translate` in the page module,
    and its semesters and modules with it. A translated plan shows the
    translation of each semester and module where it exists, and the default
    language record where it does not. A semester or a module added only in
    the translation is not shown, and neither are the semesters of a content
    element copied into the language in free mode, without a link to the
    default language element. The credit points and the categories of a
    translation are those of the default language. A site language with the
    fallback type ``free`` shows the semesters and modules of the default
    language, untranslated.

..  confval:: Footer Note
    :name: study-plan-footer-note
    :type: rich text
    :Default: empty

    The column ``tx_academicstudyplan_footer_note``. Text shown below the
    plan, a hint that the plan is subject to change or a link to the module
    handbook for example. It is rendered with the rich text processing of the
    site, like the text of a text content element, and left out when empty. A
    translation of the content element carries its own footer note.
