..  index:: Templates; Override
..  _templates-override:

====================
Overriding templates
====================

EXT:academic_projects is using Fluid as template engine.

This documentation won't bring you all information about Fluid but only the
most important things you need for using it. You can get
more information in the section :ref:`Fluid templates of the Sitepackage tutorial
<t3sitepackage:fluid-templates>`. A complete reference of Fluid ViewHelpers
provided by TYPO3 can be found in the  :ref:`ViewHelper Reference <t3viewhelper:start>`


..  index:: Templates; TypoScript

Change the templates using TypoScript constants
-----------------------------------------------

As any Extbase based extension, you can find the templates in the directory
:file:`Resources/Private/`.

If you want to change a template, copy the desired files to the directory
where you store the templates.

We suggest that you use a sitepackage extension. Learn how to
:ref:`Create a sitepackage extension <t3sitepackage:start>`.

..  code-block:: typoscript

    # TypoScript constants
    plugin.tx_academicprojects {
        view {
            templateRootPath = EXT:mysitepackage/Resources/Private/Extensions/myextension/Templates/
            partialRootPath = EXT:mysitepackage/Resources/Private/Extensions/myextension/Partials/
            layoutRootPath = EXT:mysitepackage/Resources/Private/Extensions/myextension/Layouts/
        }
    }

..  index:: Templates; Images

The image of a project
----------------------

The project list item and the project page template render their image through
the shared partial :file:`Academic/Image.html` of `EXT:academic_base`, whose
arguments and presets are documented in the `Templates` chapter of that
extension.

Two consequences for an override:

*   Overriding :file:`Project/Item.html` alone changes where the image sits,
    not how it is rendered. To change the markup, the breakpoints or the
    widths, place an :file:`Academic/Image.html` of your own in the partial
    root path above.
*   The partial root path of `EXT:academic_base` is registered below the
    constant above, so the copy wins. A view of your own that renders
    :file:`Project/Item.html` - a page object, for example - has to list that
    path itself, or the rendering fails on a partial it cannot resolve. Pick a
    key of your own rather than the one this extension uses, and list it under
    `paths` too if your page object is a :typoscript:`PAGEVIEW`, which reads no
    `partialRootPaths`:

    ..  code-block:: typoscript

        # TypoScript setup
        page.10 {
            partialRootPaths.-1700000001 = EXT:academic_base/Resources/Private/Partials/
            paths.-1700000001 = EXT:academic_base/Resources/Private/
        }
