..  _breaking-bite-jobs-plugin-icon-renamed:

=======================================================
Breaking: The content element icon has a new identifier
=======================================================

Description
===========

The icon of the content element :typoscript:`academicbitejobs_list` is replaced
(ACE-588).

It was a drawing in fixed colours, registered with the core provider and
therefore delivered as an :html:`<img>` tag, which keeps the colours of its file
on the dark cards of a dark backend colour scheme. The new icon is a Font
Awesome Free suitcase, drawn in `currentColor` and registered with
:php:`\FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider`,
which inlines it, so it follows the backend colour scheme. Its identifier
follows the scheme
:php:`tx-<extension key without underscores>-<group>-<name>` of the academic
extensions. The content element type and the new content element wizard name
the same identifier.

The old identifier is removed without an alias:

..  csv-table::
    :header: "2.x identifier", "3.0 identifier", "Registry"

    ":php:`bitejobs_list`", ":php:`tx-academicbitejobs-plugin-bite-jobs`", ":file:`Configuration/Icons.php`"

The file :file:`Resources/Public/Icons/jobs_bite_icon.svg` is removed. The new
drawing is :file:`Resources/Public/Icons/plugin/bite-jobs.svg`, its origin and
licence are listed in :file:`Resources/Public/Icons/LICENSE-font-awesome.txt`.
:file:`Extension.svg` is unchanged. The extension renders no icon in the
frontend, so it registers nothing in a :file:`Configuration/FrontendIcons.php`.

Impact
======

TCA, page TSconfig or a template of a site package naming
:php:`bitejobs_list` renders the "icon not found" placeholder, and
:php:`bitejobs_list` registered again in a :file:`Configuration/Icons.php` of a
site package no longer replaces the icon of the content element. A reference to
:file:`jobs_bite_icon.svg` fails.

The behaviour is the same on TYPO3 v13 and v14.

Affected Installations
======================

Installations that reference or override the identifier :php:`bitejobs_list`
or the file :file:`jobs_bite_icon.svg`.

Migration
=========

Use :php:`tx-academicbitejobs-plugin-bite-jobs` instead of
:php:`bitejobs_list` in TCA, page TSconfig and templates. To replace the icon,
register :php:`tx-academicbitejobs-plugin-bite-jobs` in the
:file:`Configuration/Icons.php` of a site package that depends on
:guilabel:`academic_bite_jobs`:

..  code-block:: php
    :caption: EXT:my_sitepackage/Configuration/Icons.php

    <?php

    declare(strict_types=1);

    use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

    return [
        'tx-academicbitejobs-plugin-bite-jobs' => [
            'provider' => SvgIconProvider::class,
            'source' => 'EXT:my_sitepackage/Resources/Public/Icons/jobs.svg',
        ],
    ];

..  index:: Backend, TCA, TSConfig, ext:academic_bite_jobs
