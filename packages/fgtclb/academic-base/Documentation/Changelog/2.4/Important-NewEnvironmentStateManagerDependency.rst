.. _important-ace-254-academic-base:

==============================================================================
Important: New required dependency `fgtclb/environment-state-manager`
==============================================================================

Description
===========

:php:`EXT:academic_base` now depends on the standalone extension
`fgtclb/environment-state-manager` and declares it consistently in both
:file:`composer.json` (:php:`"fgtclb/environment-state-manager": "^1.0"`) and
:file:`ext_emconf.php` (:php:`'environment_state_manager' => '1.0.0-1.99.99'`).

The dependency is the counterpart of the deprecation
:ref:`deprecation-1783574951`: the environment, state-manager and
environment-builder subsystem that :php:`EXT:academic_base` shipped internally
was extracted into that extension, and the deprecated
:php:`\FGTCLB\AcademicBase\Environment` classes now delegate to it. Requiring
the extension therefore is mandatory for the deprecated subsystem to keep
resolving until it is removed in :php:`EXT:academic_base` 3.0.0.

Impact
======

Composer-managed installations pull `fgtclb/environment-state-manager` in
automatically when :php:`EXT:academic_base` is updated to 2.4; no action is
required.

Classic, non-composer installations (TER / extension manager) must install the
`environment_state_manager` extension in addition to :php:`EXT:academic_base`,
otherwise the extension cannot be activated.

Affected Installations
======================

Only non-composer installations updating to :php:`EXT:academic_base` 2.4 need
to install the additional extension manually. Composer-managed installations
are unaffected.

.. index:: PHP, ext:academic_base
