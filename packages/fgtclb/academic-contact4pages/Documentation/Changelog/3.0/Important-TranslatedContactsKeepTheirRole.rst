..  _important-translated-contacts-keep-their-role:

=======================================================
Important: Translated contacts keep their contacts role
=======================================================

Description
===========

A translated page contact keeps the contacts role of its default language
record, the :guilabel:`Role` field of the contact is not translated. The list
of contacts on a contacts role contradicted that: it was synchronized into
every translation of the role. Saving a contacts role, in its default language
or as a translation and even without a change, wrote the uid of the role
translation into the role of every translated contact of that role and
renumbered their position in its list.

The list of contacts is now kept on the default language role only. It is not
offered on a translation of a contacts role any more, where it was not shown
before either, and saving a role leaves the translated contacts alone.

Impact
======

Two kinds of records can be left from before the update, and both point to a
translation of their role. This query lists them:

..  code-block:: sql

    SELECT contact.uid, contact.l10n_parent, contact.l10n_source, contact.role
    FROM tx_academiccontacts4pages_domain_model_contact AS contact
    JOIN tx_academiccontacts4pages_domain_model_role AS role ON role.uid = contact.role
    WHERE contact.deleted = 0 AND role.sys_language_uid > 0;

*   A translated contact whose role was rewritten. Saving its default language
    record, its :sql:`l10n_parent`, copies the role onto the translation again
    and appends the translation to the list of that role.
*   A duplicate created by localizing a contacts role whose contacts were
    translated already: a second translation for the same :sql:`l10n_parent`
    and language, copied from the translation in that language, so its
    :sql:`l10n_source` points to a translation of the same language rather
    than to the default language record. Delete it, saving repairs nothing
    here. A translation made from a translation in another language is not a
    duplicate and is not listed. This query lists the duplicates:

    ..  code-block:: sql

        SELECT duplicate.uid, duplicate.l10n_parent, duplicate.l10n_source
        FROM tx_academiccontacts4pages_domain_model_contact AS duplicate
        JOIN tx_academiccontacts4pages_domain_model_contact AS source ON source.uid = duplicate.l10n_source
        WHERE duplicate.deleted = 0 AND source.sys_language_uid > 0
            AND source.sys_language_uid = duplicate.sys_language_uid;

    The duplicates point to the translation of the role as well, so the first
    query lists them too. Delete them before saving the default language
    records that repair the others.

Localizing a contacts role, or copying it into a language, no longer localizes
its contacts either. Core still localizes every inline child of a localized
record, and no configuration prevents that, so the contacts it created are
removed again when the localization ends, and no contact is left behind. For a
contact that is translated already, core still reports that its localization
failed, while nothing is created. A plain copy of a contacts role keeps copying
its contacts.

Affected Installations
======================

Installations that translate page contacts and contacts roles.

..  index:: Backend, TCA, ext:academic_contacts4pages
