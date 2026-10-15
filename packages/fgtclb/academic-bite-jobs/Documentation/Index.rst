..  _start:

==================
Academic Bite Jobs
==================

:Extension key:
    academic_bite_jobs

:Package name:
    fgtclb/academic-bite-jobs

:Version:
    |release|

:Language:
    en

:Author:
    FGTCLB

:License:
    This document is published under the
    `Creative Commons BY 4.0 <https://creativecommons.org/licenses/by/4.0/>`__
    license.

:Rendered:
    |today|

----

The TYPO3 extension Academic Jobs with b-ite displays jobs from the b-Ite
eRecruiting platform embedded in the TYPO3 website. All you need to use it
is the b-ite key, which can usually be read from existing b-Ite job
advertisements in the browser.

The job offers are displayed as tiles, lists and tables. The detailed display
of individual job advertisements is automatically linked to the b-ite platform.
The b-ite key is entered in the content element. A filter by a custom field of
b-ite, such as appointment procedures, academic staff, non-scientific staff or
training positions, is added with an event listener.

The content element also sets the display mode, the sorting and the maximum
number of job advertisements. The grouping is set in TypoScript.

----

..  card-grid::
    :columns: 1
    :columns-md: 2
    :gap: 4
    :class: pb-4
    :card-height: 100

    ..  card:: :ref:`Introduction <introduction>`

        What the extension does and the main concepts behind it.

    ..  card:: :ref:`Installation <installation>`

        Install :guilabel:`academic_bite_jobs` via Composer, the Extension
        Manager or a TER upload.

    ..  card:: :ref:`Upgrade <upgrade>`

        The steps to upgrade :guilabel:`academic_bite_jobs` to a newer
        version, in the order to take them.

    ..  card:: :ref:`For integrators <integrator>`

        The B-ITE job list and every option of its content element.

    ..  card:: :ref:`Configuration <configuration>`

        The sets, the static templates and the TypoScript of the extension,
        the grouping of the jobs among it.

    ..  card:: :ref:`Templates <templates>`

        Override and customise the frontend templates.

    ..  card:: :ref:`Styling <styling>`

        The classes of the job list and how a site package styles it.

    ..  card:: :ref:`For developers <developers>`

        Change the request to B-ITE, the postings it returns and the
        variables of the view with event listeners.

    ..  card:: :ref:`Known problems <known-problems>`

        Known issues and information about them.

    ..  card:: :ref:`Changelog <changelog-v3>`

        Learn about what has changed and which actions are required to
        upgrade.

..  toctree::
    :maxdepth: 2
    :titlesonly:
    :hidden:

    Introduction/Index
    Installation/Index
    Upgrade/Index
    Integrator/Index
    Configuration/Index
    Templates/Index
    Styling/Index
    Developers/Index
    KnownProblems/Index
    Changelog/Changelog-3
    Changelog/Changelog-2

..  Meta Menu

..  toctree::
    :hidden:

    Sitemap
