..  index:: Templates; Override
..  _templates-override:

====================
Overriding templates
====================

EXT:academic_jobs is using Fluid as template engine.

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
    plugin.tx_academicjobs {
        view {
            templateRootPath = EXT:mysitepackage/Resources/Private/Extensions/myextension/Templates/
            partialRootPath = EXT:mysitepackage/Resources/Private/Extensions/myextension/Partials/
            layoutRootPath = EXT:mysitepackage/Resources/Private/Extensions/myextension/Layouts/
        }
    }

..  index:: Templates; Icons
..  _templates-override-icons:

Icons
-----

The glyph in front of a job property in the list, the detail view and the
contact block is chosen by the partial :file:`Job/PropertyIcon.html`. It maps
the name of the property to one of the shared :php:`tx-academicbase-info-*`
icons of EXT:academic_base; a property it does not map renders no icon. The
partials :file:`Job/Information.html`, :file:`Job/Item.html` and
:file:`Job/Contact.html` render it:

..  code-block:: html

    <f:render partial="Job/PropertyIcon" arguments="{property: item}" />

Override :file:`Job/PropertyIcon.html` to change the glyph of a property of the
job views only. To replace a shared glyph everywhere it appears, register its
identifier again in the :file:`Configuration/Icons.php` of your site package.

The icons are inlined and drawn in `currentColor`: they take the colour of the
surrounding text and are as large as its font, so they need no CSS of their
own.

The content element icon and the job record icon share one Font Awesome Free
drawing, licensed under CC BY 4.0. Its origin is listed in
:file:`Resources/Public/Icons/LICENSE-font-awesome.txt`, see
:ref:`third-party-icons`.
