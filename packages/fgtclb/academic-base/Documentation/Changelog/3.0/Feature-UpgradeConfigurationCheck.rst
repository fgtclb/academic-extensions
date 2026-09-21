..  _feature-upgrade-configuration-check:

===========================================================
Feature: "academic:upgrade:check" finds stale configuration
===========================================================

Description
===========

The console command :bash:`academic:upgrade:check` of :php:`EXT:academic_base`
gained a second group of checks. It reads the stored configuration of the
installation and reports what no longer reaches the academic extensions, or
what reaches them twice. The group takes no argument and runs on every
invocation of the command:

..  code-block:: bash

    vendor/bin/typo3 academic:upgrade:check

..  code-block:: text

    Configuration

      ! static-template          sys_template:1
        The TypoScript record "Main" on page 1 includes the static template
        "EXT:academic_persons/Configuration/TypoScript/AcademicPersonsDefault", which holds no
        TypoScript in the installed version: TYPO3 skips it without a message. Select the static
        template of the installed version instead, or remove the value.

      i alias-set                site:main
        The site "main" depends on "fgtclb/academic-persons-default", which is an alias without
        payload kept for site configurations written before 3.0. Depend on
        "fgtclb/academic-persons" instead, or on the component sets the site actually needs.

    1 problem and 1 notice in the stored configuration.

Six findings are reported:

..  list-table::
    :header-rows: 1

    *   -   Finding
        -   Meaning
        -   Severity
    *   -   :bash:`static-template`
        -   A TypoScript record includes a static template of an academic
            extension that the installation does not have, or whose folder
            holds no TypoScript in the installed version.
        -   warning
    *   -   :bash:`tsconfig-import`
        -   A page, or a site's :file:`page.tsconfig`, imports or selects a
            page TSconfig file of an academic extension that is not there.
        -   warning
    *   -   :bash:`tsconfig-syntax`
        -   A page TSconfig includes an academic file with the
            :typoscript:`<INCLUDE_TYPOSCRIPT:` syntax, which TYPO3 v14 no
            longer reads.
        -   warning
    *   -   :bash:`alias-set`
        -   A site depends on :yaml:`fgtclb/academic-persons-default` or
            :yaml:`fgtclb/academic-study-plan-default`, the alias sets kept
            for site configurations written before 3.0.
        -   notice
    *   -   :bash:`set-and-static-template`
        -   A site depends on a set of an academic extension while a
            TypoScript record on its root page includes a static template of
            the same extension.
        -   warning
    *   -   :bash:`xclass`
        -   A class of an academic extension is replaced through
            :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']`.
        -   error when the replaced class is :php:`final` or no longer
            exists, warning otherwise

Each of these fails silently in TYPO3: a static template folder without
TypoScript, an :typoscript:`@import` that matches no file and a selected page
TSconfig file that is gone are all skipped without a message.

The command exits with :bash:`1` when at least one warning or error was found
in either group, with :bash:`2` on invalid input, and with :bash:`0` otherwise
- a notice alone does not fail a pipeline. It only reads: no record, file or
site configuration is changed.

..  _feature-upgrade-configuration-check-report:

In the status report
====================

With :php:`EXT:reports` installed the same findings are shown in
:guilabel:`System > Reports > Status report` under :guilabel:`Academic Base`,
one entry per finding, and a single :guilabel:`Nothing stale` entry when there
is nothing to report. :php:`EXT:reports` stays optional and is not a
requirement of :php:`EXT:academic_base`.

..  _feature-upgrade-configuration-check-argument:

The extension key became optional
=================================

:bash:`academic:upgrade:check` took an extension key as a required argument. It
is optional now, because the configuration group needs none:

..  code-block:: bash

    # the configuration of the installation
    vendor/bin/typo3 academic:upgrade:check

    # the same, plus the template overrides of one extension
    vendor/bin/typo3 academic:upgrade:check academic_persons \
        --override-path=EXT:my_site/Resources/Private/Extensions/AcademicPersons/

An extension key still has to be given together with :bash:`--override-path` or
:bash:`--site`, and those options still have to be given together with an
extension key. Every invocation that was valid before stays valid and keeps its
exit status, with the configuration findings added to its output.

Impact
======

Integrators get a report for the configuration an upgrade leaves behind.
Measured against the last released 2.x, :bash:`2.3.4`: the page TSconfig
folders were spelled :file:`Configuration/TsConfig/`,
:file:`Configuration/TSConfig/` and :file:`Configuration/TSconfig/` depending on
the extension and were unified to the last of the three, and the set folders
:file:`Sets/AcademicPersonsDefault/` and :file:`Sets/AcademicBaseCTypeGroup/`
became :file:`Sets/Default/` and :file:`Sets/CTypeGroup/`. Both are part of the
2.4 restructuring that 3.0 inherited - see
:file:`Breaking-SiteSetsAndStaticTemplatesRestructured.rst` in the 2.4 changelog
of :php:`EXT:academic_persons` - so an installation coming from 2.3.x meets all
of it at once, and every import written against those paths resolves to nothing
without TYPO3 saying so. The same holds for a static
template folder that a release stopped delivering, and for an XCLASS of a class
that 3.0 made :php:`final`.

Nothing is rewritten. The records, files and site configurations belong to the
project, and which of them to correct is a decision the report leaves to the
integrator.

..  index:: CLI, Backend, TSConfig, TypoScript, ext:academic_base
