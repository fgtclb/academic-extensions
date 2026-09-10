..  _breaking-programs-icons-use-the-shared-icon-set:

=======================================
Breaking: Icons use the shared icon set
=======================================

Description
===========

The icons of this extension came from four sources - a brand mark, Illustrator
exports, Bootstrap Icons and copies of TYPO3 core icons - and several of them
did not show what they stood for: the category type "Begin of program" was a
bicycle, and the drawings of "Standard period" (a brain) and "Type of
program" (a stopwatch) read as if they had been swapped. One identifier,
:php:`academic-programs`, registered from the extension icon, served the page
type and both content elements.

Every icon of this extension is now a Font Awesome Free solid icon, drawn in
`currentColor` and registered with
:php:`\FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider`,
and each concept has a glyph that fits it (ACE-591). Icons that mean the same in
every academic extension - degree, department, location - come from
:php:`EXT:academic_base`. The licence and origin of every file of this
extension are listed in :file:`Resources/Public/Icons/LICENSE-font-awesome.txt`.

The identifiers follow the scheme
`tx-<extension key without underscores>-<group>-<name>` and are renamed without
a deprecated alias:

..  list-table::
    :header-rows: 1

    *   - Old identifier
        - New identifier
        - Used for
    *   - :php:`academic-programs`
        - :php:`tx-academicprograms-doktype-program`
        - the academic program page type (doktype 20): page type select and page
          tree
    *   - :php:`academic-programs`
        - :php:`tx-academicprograms-plugin-programs`
        - the content elements `academicprograms_programlist` and
          `academicprograms_programdetails`: page module, record list and new
          content element wizard

Both new identifiers share the file
:file:`Resources/Public/Icons/plugin/programs.svg` (Font Awesome
`book-open-reader`), so a project can replace either of them on its own.
:file:`Resources/Public/Icons/Extension.svg` stays the extension icon and is no
longer registered as an icon identifier.

The category type identifiers `category_types.programs.<type>` are unchanged.
The files :file:`Configuration/CategoryTypes.yaml` registers them from are
replaced; paths are below :file:`Resources/Public/Icons/` of this extension
unless they name another one:

..  list-table::
    :header-rows: 1

    *   - Category type
        - Old file
        - New file
    *   - `admission_restriction`
        - :file:`CategoryTypes/AdmissionRestriction.svg`
        - :file:`category-type/admission-restriction.svg` (`lock`)
    *   - `application_period`
        - :file:`CategoryTypes/ApplicationPeriod.svg`
        - :file:`category-type/application-period.svg` (`hourglass-half`)
    *   - `begin_program`
        - :file:`CategoryTypes/BeginProgram.svg`
        - :file:`category-type/begin-program.svg` (`flag`)
    *   - `costs`
        - :file:`CategoryTypes/Costs.svg`
        - :file:`category-type/costs.svg` (`piggy-bank`)
    *   - `paying`
        - :file:`CategoryTypes/Paying.svg`
        - :file:`category-type/paying.svg` (`money-bill-1`)
    *   - `degree`
        - :file:`CategoryTypes/Degree.svg`
        - :file:`EXT:academic_base/Resources/Public/Icons/info/degree.svg`
          (`graduation-cap`)
    *   - `department`
        - :file:`CategoryTypes/Department.svg`
        - :file:`EXT:academic_base/Resources/Public/Icons/info/department.svg`
          (`building-columns`)
    *   - `standard_period`
        - :file:`CategoryTypes/StandardPeriod.svg`
        - :file:`category-type/standard-period.svg` (`clock`)
    *   - `location`
        - :file:`CategoryTypes/Location.svg`
        - :file:`EXT:academic_base/Resources/Public/Icons/info/location.svg`
          (`location-dot`)
    *   - `program_type`
        - :file:`CategoryTypes/ProgramType.svg`
        - :file:`category-type/program-type.svg` (`layer-group`)
    *   - `teaching_language`
        - :file:`CategoryTypes/TeachingLanguage.svg`
        - :file:`category-type/teaching-language.svg` (`language`)
    *   - `topic`
        - :file:`CategoryTypes/Topic.svg`
        - :file:`category-type/topic.svg` (`lightbulb`)

The whole folder :file:`Resources/Public/Icons/CategoryTypes/` is removed,
including :file:`JobProfile.svg`, :file:`PerformanceScope.svg` and
:file:`Prerequisites.svg`, which no configuration referenced. With the last
Bootstrap Icons drawing gone,
:file:`Resources/Public/Icons/LICENSE-bootstrap-icons.txt` is removed too.

The `groups` entry of :file:`Configuration/CategoryTypes.yaml` named
:file:`Resources/Public/Icons/CategoryGroups/Programs.svg`, a file that never
existed. It now names
:file:`Resources/Public/Icons/category-group/programs.svg`, which is shipped.
Category group icons are not read by :php:`EXT:category_types` yet, so that
icon is not registered and not shown anywhere until they are (ACE-364).

Impact
======

The identifier :php:`academic-programs` is no longer registered. Wherever a
project names it - a template calling
:html:`<core:icon identifier="academic-programs" />`, TCA, page TSconfig of its
own wizard entries - TYPO3 renders the :php:`default-not-found` placeholder.

Site or backend CSS that selects the icon by its identifier class,
``.icon-academic-programs``, no longer matches.

A project that replaced the icon by registering :php:`academic-programs` in its
own :file:`Configuration/Icons.php` registers an identifier nothing asks for any
more, so the extension's own drawing is shown again.

A project that referenced one of the removed files by path, for example to
reuse a category type drawing, gets a missing file.

The category type icons in the frontend
(:file:`Partials/Program/Categories.html`, :file:`Partials/Program/Item.html`)
and in the page module of the program page type keep their identifiers and
their inlined markup; only the drawings change.

Affected Installations
======================

Installations that name the identifier :php:`academic-programs`, select
``.icon-academic-programs`` in CSS, override that identifier, or reference a
file below :file:`Resources/Public/Icons/CategoryTypes/` of this extension.

Migration
=========

Replace :php:`academic-programs` with :php:`tx-academicprograms-doktype-program`
where the page type is meant, and with
:php:`tx-academicprograms-plugin-programs` where the content elements are
meant. Update CSS selectors on the identifier class the same way.

To keep a project specific drawing, register the new identifier in the
project's :file:`Configuration/Icons.php`:

..  code-block:: php

    return [
        'tx-academicprograms-plugin-programs' => [
            'provider' => \FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider::class,
            'source' => 'EXT:my_sitepackage/Resources/Public/Icons/programs.svg',
        ],
    ];

A drawing registered with that provider has to be drawn in `currentColor`
without colours of its own; use the core
:php:`\TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider` for a fixed colour
drawing.

Replace a path to a removed category type file with the new file from the
table above.

.. index:: Backend, Frontend, TCA, TSConfig, ext:academic_programs
