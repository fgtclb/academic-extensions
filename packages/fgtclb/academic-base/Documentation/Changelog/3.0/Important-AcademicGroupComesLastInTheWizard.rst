..  _important-1790839252:

======================================================================
Important: The academic group comes last in the content element wizard
======================================================================

Description
===========

The page TSconfig of :guilabel:`EXT:academic_base` placed the group
:guilabel:`Academic` of the new content element wizard after the group
:guilabel:`Special elements` of TYPO3, with
:typoscript:`mod.wizards.newContentElement.wizardItems.academic.after = special`.

A single `before` or `after` of any group makes TYPO3 order every group by
these settings instead of by their TCA registration. The groups named in a
setting come first, and the other groups follow in the alphabetical order of
their identifiers. So the wizard opened on :guilabel:`Special elements`,
followed by :guilabel:`Academic` and :guilabel:`Typical page content`, on every
installation with this extension.

The page TSconfig now only labels the group. The groups follow the order of
their TCA registration again, which starts with
:guilabel:`Typical page content` and places :guilabel:`Academic` after the
groups of TYPO3.

Impact
======

The wizard opens on :guilabel:`Typical page content`, and the academic content
elements are found in the group after the groups of TYPO3. Nothing else
changes: the same elements are offered, with the same labels.

Affected Installations
======================

Every installation without positions of its own for the groups of the wizard.
An installation that positions groups itself, or in an extension, gets an
order from those settings alone now.

A site that wants the previous placement back sets it in its page TSconfig:

..  code-block:: typoscript

    mod.wizards.newContentElement.wizardItems.academic.after = special

See :ref:`integration-wizard-position` for the other placements.

..  index:: Backend, TSConfig, ext:academic_base
