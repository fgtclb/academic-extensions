..  _feature-upgrade-check-typoscript-records:

==========================================================
Feature: The upgrade check reads TypoScript records deeper
==========================================================

Description
===========

The configuration group of :bash:`academic:upgrade:check` reported what a
TypoScript record, a page or a site references **directly**. It now reaches one
reference further, with three findings:

..  list-table::
    :header-rows: 1

    *   -   Finding
        -   Meaning
        -   Severity
    *   -   :bash:`typoscript-import`
        -   The :guilabel:`Constants` or :guilabel:`Setup` field of a
            TypoScript record imports a TypoScript file of an academic
            extension that the installation does not resolve.
        -   warning
    *   -   :bash:`typoscript-syntax`
        -   The same field includes an academic file with the
            :typoscript:`<INCLUDE_TYPOSCRIPT:` syntax, which TYPO3 v14 no
            longer reads.
        -   warning
    *   -   :bash:`set-branch-cleared`
        -   A TypoScript record on the root page of a site that delivers
            TypoScript through site sets clears the :guilabel:`Constants` or
            the :guilabel:`Setup` branch, discarding what those sets
            contributed to it.
        -   warning

..  code-block:: text

    Configuration

      ! typoscript-import        sys_template:1
        The TypoScript record 1 ("Main"), field "Setup", imports
        "EXT:academic_jobs/Configuration/TypoScript/Default/setup.typoscript", which matches no file of
        the installed version. TYPO3 skips the import without a message. Correct the path, or depend on
        the site set of the extension instead of importing its files.

      ! set-branch-cleared       site:main
        The TypoScript record 1 ("Main") on the root page 1 of the site "main" clears Constants and
        Setup, and the site delivers TypoScript through site sets. TYPO3 reads the sets before the
        record, so the flag discards everything they contributed to both branches - which looks like
        an extension that ships no TypoScript rather than like a template record. The backend button
        "Create a root TypoScript record" writes both flags. Clear the flag, or carry the
        configuration in the record itself.

In addition, a page TSconfig reference that **resolves** is now followed: TYPO3
reads the imports inside the file it includes, so a page whose own import is
sound can still end up with configuration that does not resolve. Any resolving
file is read, not only one of an academic extension - the case this exists for
is a project file that is there and imports an academic path a release renamed -
and only academic references are reported. The finding names the page and the
file the line is in.

A value of :guilabel:`Include static Page TSconfig (from extensions)`
(:sql:`pages.tsconfig_includes`) is not expanded the way an
:typoscript:`@import` is: TYPO3 reads exactly one file for it, of any suffix, so
a selected folder delivers nothing. That is reported as well.

..  _feature-upgrade-check-typoscript-records-option:

An option that was accepted and ignored
=======================================

:bash:`--upstream-path` names the folder the :bash:`--override-path` folders are
compared with, and nothing else. Given without one it was accepted and silently
ignored; it is invalid input now, with a message that says what to give instead.

Impact
======

The first finding is the one an upgrade meets most often. A site package that
imports the TypoScript of an extension **by path** rather than depending on its
site set keeps working until a release moves the file - and then resolves to
nothing, without TYPO3 saying so. The 2.4 change
:file:`Breaking-SiteSetsAndStaticTemplatesRestructured.rst` of
:php:`EXT:academic_persons` moved exactly those files, and names that
consequence itself.

The second is an upgrade to TYPO3 v14: the :typoscript:`<INCLUDE_TYPOSCRIPT:`
syntax is gone there, and a TypoScript record is where it was most used.

The third is the trap that looks like something else. A
:guilabel:`clear` flag on a site driven by site sets throws the whole set
contribution away, and the frontend then looks as if the extension shipped no
TypoScript at all.

Nothing is rewritten. The records and site configurations belong to the
project, and which of the findings to correct is a decision the report leaves
to the integrator.

..  index:: CLI, Backend, TypoScript, TSConfig, ext:academic_base
