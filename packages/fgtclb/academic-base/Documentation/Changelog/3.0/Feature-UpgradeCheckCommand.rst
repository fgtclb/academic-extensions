..  _feature-upgrade-check-command:

======================================================
Feature: "academic:upgrade:check" finds dead overrides
======================================================

Description
===========

:php:`EXT:academic_base` ships the console command
:bash:`academic:upgrade:check`. It compares the Fluid files below one or more
project override folders with the files the installed academic extension ships,
and reports every file that no longer matches one.

..  code-block:: bash

    vendor/bin/typo3 academic:upgrade:check academic_persons_edit \
        --override-path=EXT:my_site/Resources/Private/Extensions/AcademicPersonsEdit/

..  code-block:: text

    EXT:my_site/Resources/Private/Extensions/AcademicPersonsEdit/
      compared with EXT:academic_persons_edit/Resources/Private/
      x missing-upstream  Templates/Profile/Show.html
      = identical         Partials/Profile/Image.html
      ! case-mismatch     Pages/Academicprogram.html -> Pages/AcademicProgram.html

    2 problems and 1 notice in 1 override folder.

Three findings are reported:

..  list-table::
    :header-rows: 1

    *   -   Finding
        -   Meaning
        -   Exit status
    *   -   :bash:`missing-upstream`
        -   The extension ships no file at that relative path, not even one
            differing in case. Fluid never resolves the override.
        -   problem
    *   -   :bash:`case-mismatch`
        -   The extension ships the file under a name that differs only in
            case. Such an override resolves on a case insensitive file system
            and is dead on the Linux server next to it.
        -   problem
    *   -   :bash:`identical`
        -   The override is a byte identical copy. It renders, and it freezes
            the markup of that one file at the version it was taken from.
        -   notice

A changed copy of a file the extension still ships is not reported: it is a
deliberate override that works.

The command exits with :bash:`1` when at least one problem is reported, with
:bash:`2` on invalid input, and with :bash:`0` otherwise - a notice alone does
not fail a pipeline. It only reads; it changes no file.

..  _feature-upgrade-check-command-options:

Naming the folders
==================

..  confval:: extension
    :name: upgrade-check-extension
    :type: string
    :required: true

    The extension key of the academic extension the overrides belong to, for
    example :bash:`academic_persons_edit`.

..  confval:: --override-path
    :name: upgrade-check-override-path
    :type: string

    A folder holding the project overrides, as an :bash:`EXT:` path or an
    absolute path. May be given more than once. It is compared with
    :file:`EXT:<extension>/Resources/Private/`.

..  confval:: --upstream-path
    :name: upgrade-check-upstream-path
    :type: string

    The folder the override folders are compared with, when the override folder
    mirrors a single one rather than :file:`Resources/Private/`:

    ..  code-block:: bash

        vendor/bin/typo3 academic:upgrade:check academic_persons \
            --override-path=EXT:my_site/Resources/Private/Partials/Academic/ \
            --upstream-path=EXT:academic_persons/Resources/Private/Partials/

..  confval:: --site
    :name: upgrade-check-site
    :type: string

    Take the override folders from the TypoScript of that site instead of from
    the command line, by its site identifier. It may be combined with
    :bash:`--override-path`.

..  _feature-upgrade-check-command-site:

Taking the folders from a site
==============================

With :bash:`--site` the command builds a frontend environment for the site's
root page and reads the view root paths the site's TypoScript configures for the
extension's plugins - :typoscript:`plugin.tx_academicpersons.view.templateRootPaths`,
:typoscript:`partialRootPaths` and :typoscript:`layoutRootPaths`. Each folder is
compared with the upstream folder of its own kind, so :bash:`--upstream-path` is
not needed there.

..  code-block:: bash

    vendor/bin/typo3 academic:upgrade:check academic_persons --site=main

A root path is only treated as a project override when it lies outside the
checked extension, outside every extension it depends on and outside the TYPO3
system extensions. The partial root paths of the academic plugins name the
shared partials of :php:`EXT:academic_base`, and those of
:php:`EXT:academic_persons_edit` name the partials of
:php:`EXT:fluid_styled_content`; neither is a project override, and reporting
them would bury the findings that matter.

The command exits with :bash:`2` and names the site when the site does not
exist, when its frontend environment cannot be built, when its TypoScript
configures no view root path for the extension at all, or when a root path names
a folder that does not exist - the last one together with the TypoScript path
that named it.

For the :typoscript:`page.10` root paths of a site the command is deliberately
not used: they are shared with the site's theme, so every theme template would
be reported as :bash:`missing-upstream`. Point :bash:`--override-path` at the
page template folder instead.

Impact
======

Integrators get a check they can run before and after an upgrade of the academic
extensions, and in continuous integration. Nothing in TYPO3 reports a template
override that no longer resolves, so before this command such an override was
found by noticing that the frontend no longer looks the way the project
configured it.

:php:`EXT:academic_base` requires `fgtclb/environment-state-manager` for the
:bash:`--site` mode and declares it in :file:`composer.json` and in
:file:`ext_emconf.php`. That is the dependency it already carries since 2.4.0;
it keeps it, now for this command rather than for the internal environment
subsystem that 3.0 removed. Composer managed installations pull the package in
automatically; classic installations have to have the
:php:`environment_state_manager` extension installed as before.

..  index:: CLI, ext:academic_base
