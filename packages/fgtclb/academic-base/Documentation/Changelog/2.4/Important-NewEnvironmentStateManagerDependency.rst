.. _important-ace-254-academic-base:

=====================================================================
Important: New required dependency `fgtclb/environment-state-manager`
=====================================================================

Description
===========

EXT:academic_base now depends on the standalone extension
:composer:`fgtclb/environment-state-manager` and declares it in both
:file:`composer.json` (`"fgtclb/environment-state-manager": "^1.0"`) and
:file:`ext_emconf.php` (`'environment_state_manager' => '1.0.0-1.99.99'`).

The dependency is the counterpart of the deprecation
:ref:`deprecation-1783574951`: the environment, state-manager and
environment-builder subsystem that EXT:academic_base shipped internally was
extracted into that extension, and the deprecated
:php:`\FGTCLB\AcademicBase\Environment` classes now delegate to it. The
extension is therefore required for the deprecated subsystem to keep working
until it is removed in version 3.0.

Impact
======

Composer-managed installations pull :composer:`fgtclb/environment-state-manager`
in automatically when EXT:academic_base is updated to 2.4. No action is
required.

Classic, non-Composer installations (TER, extension manager) must install the
extension `environment_state_manager` in addition to EXT:academic_base.
Otherwise the extension cannot be activated.

Affected Installations
======================

Only non-Composer installations updating EXT:academic_base to 2.4 need to
install the additional extension manually. Composer-managed installations are
unaffected.

.. index:: PHP, ext:academic_base
