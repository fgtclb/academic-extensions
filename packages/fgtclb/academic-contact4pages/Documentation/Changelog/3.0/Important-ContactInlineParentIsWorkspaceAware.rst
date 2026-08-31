.. _important-contact-inline-parent-is-workspace-aware:

===========================================================
Important: The contact inline parent is now workspace aware
===========================================================

Description
===========

:sql:`tx_academiccontacts4pages_domain_model_contact` has always declared
:php:`'versioningWS' => true`, while the table it is attached to as an inline
child — :sql:`tx_academicpersons_domain_model_contract`, extended in
:file:`Configuration/TCA/Overrides/tx_academicpersons_domain_model_contract.php`
- did not.

That is the direction TYPO3 does not repair.
:php:`TcaMigration::addWorkspaceAwarenessToInlineChildren()` only ever adds the
flag to a *child* whose parent already carries it, and TYPO3 v13 ships no such
migration at all, so a workspace aware child below a live-only parent went
unreported on both supported core versions. The TYPO3 documentation calls the
combination unsupported.

:php:`academic_persons` 3.0 declares all of its record tables workspace aware,
which resolves it: parent and child now agree.

Impact
======

Nothing changes in this extension itself. Its contact records were already
versionable and keep their :sql:`t3ver_*` columns.

What changes is that the parent record can now be versioned as well, so a
contact and the contract it belongs to are drafted and published together
rather than the contact alone being versionable below a record that was not.

The parent table gains four columns and an index in the process. **Run the
database analyzer once after updating** — see *Breaking: Person records are
workspace aware* in the 3.0 changelog of :php:`academic_persons`, which also
documents the SQLite defect that affects the command line path.

..  note::

    The :php:`contract`, :php:`email_address`, :php:`phone_number` and
    :php:`physical_address` columns of
    :sql:`tx_academiccontacts4pages_domain_model_contact` are :php:`select`
    fields with an :php:`itemsProcFunc` and no :php:`foreign_table`, so they
    store bare uids that TYPO3 does not know to be relations.
    :php:`DataHandler` cannot remap them when a record is published out of a
    workspace. Making the tables versionable does not change that, and it is
    tracked separately.

Affected Installations
======================

Every installation of this extension, because it depends on
:php:`academic_persons` and the schema of
:sql:`tx_academicpersons_domain_model_contract` changes there.

.. index:: Database, TCA, ext:academic_contacts4pages
