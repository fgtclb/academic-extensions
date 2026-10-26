..  _upgrade:

=======
Upgrade
=======

This chapter lists, for each upgrade path, the steps for
:guilabel:`academic_base` in the order to take them. Each step links
the changelog entry with the detail.

..  list-table::
    :header-rows: 1

    *   -   Installed version
        -   Target version
        -   Page
    *   -   2.3
        -   2.4
        -   :ref:`upgrade-from-2-3-to-2-4`

..  _upgrade-before-you-start:

Before you start
================

*   Make a copy of the database and of :file:`fileadmin/`, so the upgrade can
    be repeated or rolled back.
*   Run the upgrade on a staging copy first, and on the live installation once
    the staging copy works.
*   Upgrade all academic extensions of an installation together, in one
    deployment, to the same release.
*   :guilabel:`academic_base` requires no other academic extension.

..  _upgrade-outside-these-paths:

Installations outside these paths
---------------------------------

The upgrade chapters of the academic extensions start at 2.3. An installation
that starts elsewhere takes these steps first, then follows the page of its
path in the upgrade chapter of each extension:

TYPO3 v12
    Versions 2.3 and 2.4 run on TYPO3 12.4 from 12.4.22 on and on TYPO3 13.4.
    The path from 2.3 to 2.4 covers both. Update an older TYPO3 v12 to 12.4.22
    or later first.

A release older than 2.3
    Read the Breaking and Important entries of each minor version after the
    installed one, up to and including 2.3, in the changelog of each extension,
    and apply them in that order. Then follow the path from 2.3.

An unreleased revision
    An installation that requires ``dev-2``, ``2.x-dev`` or ``2.4.x-dev`` runs a
    revision between two releases. It carries the version of the release it
    leads to, ``2.4.0`` in :file:`ext_emconf.php`, and only part of its
    changes. :file:`composer.lock` names the installed commit. Follow the path
    from the last release before it, 2.3, and skip a step that does not apply.

A development constraint of an earlier version
    ``2.0.x-dev``, ``2.1.x-dev`` and ``2.3.x-dev`` were aliases of the branch
    ``main``, which is ``3.0.x-dev`` now, so Composer no longer resolves them.
    ``2.2.x-dev`` resolves to the branch ``2.2``, which receives no changes any
    more. Replace such a constraint, or ``2.x-dev``, with ``^2.4``. Which steps
    apply follows from the installed revision, as described above.

..  toctree::
    :hidden:
    :titlesonly:

    From23To24
