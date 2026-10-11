.. _deprecation-1783574951:

==================================================================================
Deprecation: Internal environment / state-manager subsystem moved to own extension
==================================================================================

Description
===========

The environment, state-manager and environment-builder subsystem that
EXT:academic_base shipped internally under the namespaces
:php:`\FGTCLB\AcademicBase\Environment`,
:php:`\FGTCLB\AcademicBase\Core12\Environment` and
:php:`\FGTCLB\AcademicBase\Core13\Environment` has been extracted into the
standalone public extension `fgtclb/environment-state-manager` (namespace
:php:`\FGTCLB\EnvironmentStateManager`), which additionally provides backend
environment handling.

The internal classes were always flagged :php:`@internal` and were never part of
the public API of EXT:academic_base. They are now **deprecated** and will
be removed with EXT:academic_base 3.0.0. Use the
:php:`\FGTCLB\EnvironmentStateManager` classes instead.

The deprecated classes, interfaces and traits stay in place and keep working
until then, so their TYPO3 core-version aware dependency injection continues to
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
--------------------------------------

:php:`\FGTCLB\AcademicBase\Environment\StateBuildContext` is a plain value object
that is carried **by type** through the public PSR-14 event
:php:`\FGTCLB\AcademicPersons\Service\Event\ModifyProfileCommandEnvironmentStateBuildContextForFrontendUserEvent`.
Shipping a second, distinct class with the same purpose would break that event's
type contract for existing listeners. This one class has therefore been
**removed** from EXT:academic_base and registered as a deprecated
**class alias** of :php:`\FGTCLB\EnvironmentStateManager\StateBuildContext`
through the `typo3/class-alias-loader` composer plugin
(:file:`Migrations/Code/ClassAliasMap.php`). The old name keeps resolving to the
new class, they are the same class at runtime, until the alias is removed in
3.0.0.

Impact
======

Referencing any of the deprecated class names keeps working: the remaining
classes are still shipped, and :php:`StateBuildContext` resolves through the class
alias. Because the classes were :php:`@internal`, no runtime deprecation is
triggered.

The new classes differ from the deprecated ones in two places:

*   :php:`\FGTCLB\EnvironmentStateManager\StateBuildContext` gained two optional
    constructor arguments, :php:`$backendUserId` and :php:`$workspaceId`.
*   :php:`\FGTCLB\EnvironmentStateManager\StateInterface` no longer declares
    :php:`withTypoScriptFrontendController()` and
    :php:`typoScriptFrontendController()`. They moved to the core-version
    specific :php:`ExtendedStateInterface` of the namespaces
    :php:`\FGTCLB\EnvironmentStateManager\Core12` and
    :php:`\FGTCLB\EnvironmentStateManager\Core13`, because the
    TypoScriptFrontendController is deprecated in TYPO3 v13. The interface also
    gained :php:`withLanguageService()` and :php:`languageService()`.

Affected Installations
======================

Only installations whose own code references the internal environment /
state-manager classes directly. The academic extensions themselves use
`fgtclb/environment-state-manager` already.

Migration
=========

Require the new extension and replace the namespace prefix
:php:`\FGTCLB\AcademicBase\Environment` with :php:`\FGTCLB\EnvironmentStateManager`.
For the core-version specific classes replace
:php:`\FGTCLB\AcademicBase\Core12\Environment` with
:php:`\FGTCLB\EnvironmentStateManager\Core12`, and
:php:`\FGTCLB\AcademicBase\Core13\Environment` with
:php:`\FGTCLB\EnvironmentStateManager\Core13`. The class names stay the same.
Code that calls the TypoScriptFrontendController accessors on a
:php:`StateInterface` has to type against the :php:`ExtendedStateInterface` of
the running core version instead, see Impact.

..  code-block:: bash

    composer require fgtclb/environment-state-manager

..  code-block:: php

    // Before
    use FGTCLB\AcademicBase\Environment\StateManagerInterface;
    use FGTCLB\AcademicBase\Environment\StateBuildContext;

    // After
    use FGTCLB\EnvironmentStateManager\StateManagerInterface;
    use FGTCLB\EnvironmentStateManager\StateBuildContext;

..  index:: PHP-API, ext:academic_base
