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
extensions.

The old identifier is removed without an alias:

..  csv-table::
    :header: "Old identifier", "New identifier"

    ":php:`bitejobs_list`", ":php:`tx-academicbitejobs-plugin-bite-jobs`"

The file :file:`Resources/Public/Icons/jobs_bite_icon.svg` is removed. The new
drawing is :file:`Resources/Public/Icons/plugin/bite-jobs.svg`; its origin and
licence are listed in :file:`Resources/Public/Icons/LICENSE-font-awesome.txt`.
:file:`Extension.svg` is unchanged.

Impact
======

TCA, page TSconfig or a template of a site package naming
:php:`bitejobs_list` renders the "icon not found" placeholder, and
:php:`bitejobs_list` registered again in a :file:`Configuration/Icons.php` of a
site package no longer replaces the icon of the content element. A reference to
:file:`jobs_bite_icon.svg` fails.

Affected Installations
======================

Installations that reference or override the identifier :php:`bitejobs_list`
or the file :file:`jobs_bite_icon.svg`.

Migration
=========

Use :php:`tx-academicbitejobs-plugin-bite-jobs` instead of
:php:`bitejobs_list`. To replace the icon, register
:php:`tx-academicbitejobs-plugin-bite-jobs` in the
:file:`Configuration/Icons.php` of the site package.

..  index:: Backend, TCA, TSConfig, ext:academic_bite_jobs
