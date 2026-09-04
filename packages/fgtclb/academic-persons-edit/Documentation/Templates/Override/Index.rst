..  index:: Templates; Override
..  _templates-override:

Overriding templates
====================

EXT:academic_persons_edit is using Fluid as template engine.

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
    plugin.tx_academicpersonsedit {
        view {
            templateRootPath = EXT:mysitepackage/Resources/Private/Extensions/myextension/Templates/
            partialRootPath = EXT:mysitepackage/Resources/Private/Extensions/myextension/Partials/
            layoutRootPath = EXT:mysitepackage/Resources/Private/Extensions/myextension/Layouts/
        }
    }

..  index:: Templates; Icons
..  _templates-override-icons:

Replacing an icon
-----------------

The action icons of the profile editing frontend are addressed by identifier,
not by file. They are registered in :file:`Configuration/Icons.php` of
:file:`EXT:academic_persons_edit` as ``academic-persons-edit-add``,
``-back``, ``-clear``, ``-delete``, ``-edit``, ``-help``, ``-move-down``,
``-move-up``, ``-save``, ``-sort-handle``, ``-undo``, ``-upload-image`` and
``-view``.

To use different artwork, register the identifier again in the
:file:`Configuration/Icons.php` of the sitepackage with the own file - a later
registration wins, and no template has to be overridden:

..  code-block:: php
    :caption: EXT:mysitepackage/Configuration/Icons.php

    <?php

    use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;

    return [
        'academic-persons-edit-save' => [
            'provider' => CurrentColorSvgIconProvider::class,
            'source' => 'EXT:mysitepackage/Resources/Public/Icons/save.svg',
        ],
    ];

That provider inlines the file rather than rendering an :html:`<img>`, so the
glyph takes the colour of the button it sits in. A file registered with it
carries a ``viewBox``, draws its shapes in ``currentColor`` and has no ``id``
attribute - the markup is part of the document, possibly more than once.

The shipped files are `Bootstrap Icons <https://icons.getbootstrap.com/>`__;
their MIT licence ships beside them in
:file:`Resources/Public/Icons/LICENSE-bootstrap-icons.txt`.
