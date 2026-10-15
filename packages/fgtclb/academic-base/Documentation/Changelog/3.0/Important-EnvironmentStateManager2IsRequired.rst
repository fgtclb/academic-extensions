..  _important-environment-state-manager-2:

===========================================================
Important: `fgtclb/environment-state-manager` 2 is required
===========================================================

Description
===========

The `3.x` version line of `EXT:academic_base` requires version 2 of
:composer:`fgtclb/environment-state-manager`. It is declared as ``^2.0.1`` in
:file:`composer.json` and as ``2.0.1-2.99.99`` in :file:`ext_emconf.php`.
Version 2.4 required 1.x, see :ref:`important-ace-254-academic-base`.

Version 2 of that extension supports TYPO3 13.4 and 14.3. It removed the
namespace :php:`\FGTCLB\EnvironmentStateManager\Core12`, which held the
implementation for TYPO3 v12, and added
:php:`\FGTCLB\EnvironmentStateManager\Core14`. Its public API, the interfaces
:php:`EnvironmentBuilderFactoryInterface`, :php:`EnvironmentBuilderInterface`,
:php:`StateManagerInterface` and :php:`StateInterface`, is unchanged.

Impact
======

Composer updates the package to 2.x together with `EXT:academic_base`. A
project that requires 1.x in its own :file:`composer.json`, as the upgrade to
2.4 asked of code that uses its classes, stops that update: Composer finds no
version that satisfies both requirements and installs nothing.

A classic installation cannot activate `EXT:academic_base` 3.0 while
:guilabel:`environment_state_manager` 1.x is installed.

Code that names a class of the :php:`Core12` namespace raises a fatal error.

Affected Installations
======================

Every installation that updates to 3.0. A change is needed where a project
requires the package itself, or names a class of the :php:`Core12` namespace.

Migration
=========

#.  Raise the requirement in the project's own :file:`composer.json` to
    ``^2.0.1``, in the same :bash:`composer require -W` command that updates
    the academic extensions, and the dependency in an :file:`ext_emconf.php`
    of the project to ``2.0.1-2.99.99``. A classic installation updates
    :guilabel:`environment_state_manager` to 2.x before
    :guilabel:`academic_base`.
#.  Replace a class of the :php:`Core12` namespace with the interface of the
    public API it implements. Dependency injection resolves the interface to
    the implementation of the running TYPO3 version. Where the code needs a
    version specific class, it names the one of :php:`Core13` or
    :php:`Core14`.

..  index:: PHP-API, NotScanned, ext:academic_base
