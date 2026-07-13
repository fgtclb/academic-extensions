..  _breaking-removed-environment-state-manager-dependency:

======================================================
Breaking: Removed environment-state-manager dependency
======================================================

Description
===========

The hard dependency on `fgtclb/environment-state-manager` has been removed
from :php:`EXT:academic_base`. It was previously a runtime :php:`require`,
so the package was pulled in transitively for every extension and project
depending on :php:`EXT:academic_base`.

This is the removal step of the change deprecated in academic_base 2.4.0,
see :ref:`the deprecation notice <deprecation-1783574951>`.

Impact
======

Projects and extensions that use `fgtclb/environment-state-manager` - for
example through the :php:`\FGTCLB\EnvironmentStateManager` classes - must
now require the package directly instead of relying on it being provided
transitively by :php:`EXT:academic_base`:

..  code-block:: bash

    composer require fgtclb/environment-state-manager

Affected installations
======================

Installations that used `fgtclb/environment-state-manager` only because
:php:`EXT:academic_base` pulled it in. Within the academic extensions only
:php:`EXT:academic_persons` and :php:`EXT:academic_programs` use the package
and already require it directly, so they are unaffected.

Migration
=========

Add `fgtclb/environment-state-manager` to the :php:`require` (or
:php:`require-dev`) section of every extension or project that uses it
directly.
