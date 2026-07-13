..  _breaking-removed-internal-environment-state-manager-subsystem:

================================================================
Breaking: Removed internal environment / state-manager subsystem
================================================================

Description
===========

The internal environment, state-manager and environment-builder subsystem
that :php:`EXT:academic_base` shipped under the namespaces
:php:`\FGTCLB\AcademicBase\Environment`,
:php:`\FGTCLB\AcademicBase\Core12\Environment` and
:php:`\FGTCLB\AcademicBase\Core13\Environment` has been removed. Use the
standalone `fgtclb/environment-state-manager` extension
(namespace :php:`\FGTCLB\EnvironmentStateManager`) instead.

This is the removal step of the change deprecated in academic_base 2.4.0,
see :ref:`the deprecation notice <deprecation-1783574951>`.

Together with the classes the backward-compatibility class alias for
:php:`\FGTCLB\AcademicBase\Environment\StateBuildContext` (registered onto
:php:`\FGTCLB\EnvironmentStateManager\StateBuildContext` via
:file:`Migrations/Code/ClassAliasMap.php` and its IDE stub
:file:`Migrations/Code/LegacyClassesForIde.php`) has been removed, as well as
the `typo3/class-alias-loader` requirement and its
:php:`extra.typo3/class-alias-loader` composer configuration that registered
the alias.

Impact
======

Code referencing any of the removed classes, interfaces or traits - including
the deprecated :php:`\FGTCLB\AcademicBase\Environment\StateBuildContext`
alias - no longer resolves and triggers a fatal error.

Affected installations
======================

Installations referencing the removed internal environment / state-manager
classes or the deprecated :php:`StateBuildContext` alias directly. Within the
academic extensions this concerned only :php:`EXT:academic_persons` and
:php:`EXT:academic_programs`, which were already switched to
`fgtclb/environment-state-manager`.

Migration
=========

Require `fgtclb/environment-state-manager` and replace the namespace prefix
:php:`\FGTCLB\AcademicBase\Environment` with
:php:`\FGTCLB\EnvironmentStateManager` (respectively
:php:`\FGTCLB\AcademicBase\Core12\Environment` /
:php:`\FGTCLB\AcademicBase\Core13\Environment` with
:php:`\FGTCLB\EnvironmentStateManager\Core12` /
:php:`\FGTCLB\EnvironmentStateManager\Core13`). Class names, method
signatures and behaviour are compatible.

..  code-block:: php

    // Before
    use FGTCLB\AcademicBase\Environment\StateManagerInterface;
    use FGTCLB\AcademicBase\Environment\StateBuildContext;

    // After
    use FGTCLB\EnvironmentStateManager\StateManagerInterface;
    use FGTCLB\EnvironmentStateManager\StateBuildContext;
