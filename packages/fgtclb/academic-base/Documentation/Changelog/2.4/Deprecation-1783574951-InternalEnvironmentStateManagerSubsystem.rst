.. _deprecation-1783574951:

==================================================================================
Deprecation: Internal environment / state-manager subsystem moved to own extension
==================================================================================

Description
===========

The environment, state-manager and environment-builder subsystem that
:php:`EXT:academic_base` shipped internally under the namespaces
:php:`\FGTCLB\AcademicBase\Environment`,
:php:`\FGTCLB\AcademicBase\Core12\Environment` and
:php:`\FGTCLB\AcademicBase\Core13\Environment` has been extracted into the
dedicated, standalone public extension `fgtclb/environment-state-manager`
(namespace :php:`\FGTCLB\EnvironmentStateManager`), which additionally provides
backend environment handling.

The internal classes were always flagged :php:`@internal` and were never part of
the public API of :php:`EXT:academic_base`. They are now **deprecated** and will
be removed with :php:`EXT:academic_base` 3.0.0. Use the
:php:`\FGTCLB\EnvironmentStateManager` classes instead.

The deprecated classes, interfaces and traits stay in place and keep working
until 3.0.0, so their TYPO3 core-version aware dependency injection continues to
resolve to the correct implementation:

*  :php:`\FGTCLB\AcademicBase\Environment\EnvironmentBuilderInterface`
*  :php:`\FGTCLB\AcademicBase\Environment\EnvironmentBuilderFactory`
*  :php:`\FGTCLB\AcademicBase\Environment\EnvironmentBuilderFactoryInterface`
*  :php:`\FGTCLB\AcademicBase\Environment\StateInterface`
*  :php:`\FGTCLB\AcademicBase\Environment\StateManagerInterface`
*  :php:`\FGTCLB\AcademicBase\Environment\StateManagerExecuteMethodTrait`
*  :php:`\FGTCLB\AcademicBase\Environment\StateManagerRootStateInterfaceHelperMethodsTrait`
*  :php:`\FGTCLB\AcademicBase\Environment\Event\StateApplyEvent`
*  :php:`\FGTCLB\AcademicBase\Environment\Event\StateBackupEvent`
*  :php:`\FGTCLB\AcademicBase\Environment\Exception\NoTypo3VersionCompatibleEnvironmentBuilderFound`
*  :php:`\FGTCLB\AcademicBase\Environment\Exception\SiteConfigCouldNotBeDetermined`
*  :php:`\FGTCLB\AcademicBase\Core12\Environment\*` and
   :php:`\FGTCLB\AcademicBase\Core13\Environment\*`
   (:php:`ExtendedStateInterface`, :php:`FrontendEnvironmentBuilder`,
   :php:`StateManager`, :php:`State`)

Special case: :php:`StateBuildContext`
======================================

:php:`\FGTCLB\AcademicBase\Environment\StateBuildContext` is a plain value object
that is carried **by type** through the public PSR-14 event
:php:`\FGTCLB\AcademicPersons\Service\Event\ModifyProfileCommandEnvironmentStateBuildContextForFrontendUserEvent`.
Shipping a second, distinct class with the same purpose would break that event's
type contract for existing listeners. Therefore this class - and only this class -
has been **removed** from :php:`EXT:academic_base` and re-registered as a
deprecated **class alias** onto
:php:`\FGTCLB\EnvironmentStateManager\StateBuildContext` via the
`typo3/class-alias-loader` composer plugin
(:file:`Migrations/Code/ClassAliasMap.php`). The old name therefore keeps
resolving to the new class - they are the same class at runtime - until the alias
is removed in 3.0.0.

Impact
======

Referencing any of the deprecated class names keeps working: the remaining
classes are still shipped, and :php:`StateBuildContext` resolves through the class
alias. Because the classes were :php:`@internal`, no runtime deprecation is
triggered. Everything is removed in :php:`EXT:academic_base` 3.0.0.

Note that :php:`\FGTCLB\EnvironmentStateManager\StateBuildContext` gained optional
constructor arguments (backend user and workspace) and that the base
:php:`StateInterface` moved its TypoScriptFrontendController accessors to the
core-version specific :php:`Core*\ExtendedStateInterface`, because the
TypoScriptFrontendController is deprecated in TYPO3 v13 and removed in v14.

Affected installations
======================

Only installations that reference the internal environment / state-manager
classes directly are affected. Within the academic extensions this concerned only
:php:`EXT:academic_persons` and :php:`EXT:academic_programs`, which have already
been switched to `fgtclb/environment-state-manager`.

Migration
=========

Require the new extension and replace the namespace prefix
:php:`\FGTCLB\AcademicBase\Environment` with :php:`\FGTCLB\EnvironmentStateManager`
(respectively :php:`\FGTCLB\AcademicBase\Core12\Environment` /
:php:`\FGTCLB\AcademicBase\Core13\Environment` with
:php:`\FGTCLB\EnvironmentStateManager\Core12` /
:php:`\FGTCLB\EnvironmentStateManager\Core13`). Class names, method signatures and
behaviour are compatible.

..  code-block:: bash

    composer require fgtclb/environment-state-manager

..  code-block:: php

    // Before
    use FGTCLB\AcademicBase\Environment\StateManagerInterface;
    use FGTCLB\AcademicBase\Environment\StateBuildContext;

    // After
    use FGTCLB\EnvironmentStateManager\StateManagerInterface;
    use FGTCLB\EnvironmentStateManager\StateBuildContext;
