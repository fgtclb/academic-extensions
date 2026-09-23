..  _feature-upgrade-check-unavailable-sets:

============================================================
Feature: The upgrade check reports sets TYPO3 cannot provide
============================================================

Description
===========

The configuration group of :bash:`academic:upgrade:check` reports a site that
depends on a set of an academic extension TYPO3 cannot provide:

..  list-table::
    :header-rows: 1

    *   -   Finding
        -   Meaning
        -   Severity
    *   -   :bash:`unavailable-set`
        -   A site depends on a set of an academic extension that is not
            installed, that a release removed or that is invalid, or on a set
            of its own - a site package set, typically - that depends on one,
            directly or further down.
        -   error

..  code-block:: text

    Configuration

      x unavailable-set          site:main
        The site "main" depends on "acme/site-package", which needs
        "fgtclb/academic-programs-content-load" - directly or through another set. TYPO3 cannot
        provide "fgtclb/academic-programs-content-load": no active extension ships a valid set of
        that name. TYPO3 answers every page of the site with HTTP 500 ("depends on unavailable sets")
        until the dependency is removed or the set is available again. 3.0 removed it: program pages
        render the content of their main column without it. Remove the dependency from the site
        configuration and from every set of the site package.

For a set a release removed, the message says what replaced it. So far that is
`fgtclb/academic-programs-content-load`, which 3.0 removed - see
:file:`Breaking-ContentLoadSetRemoved.rst` in the 3.0 changelog of
:php:`EXT:academic_programs`.

Impact
======

Unlike most findings of the group, this one is not silent in TYPO3: the site
answers every frontend request with HTTP 500. It is reported anyway, and as an
error, because the command runs before an upgraded installation goes live - the
site only fails once it does.

An alias set whose extension is not installed is reported as
:bash:`unavailable-set` rather than as :bash:`alias-set`: it does not deliver.

A set of another vendor that misses a set of another vendor is not reported;
the backend module :guilabel:`Sites` (:guilabel:`Sites > Setup` on TYPO3 v14)
lists every invalid set.

..  index:: CLI, Backend, ext:academic_base
