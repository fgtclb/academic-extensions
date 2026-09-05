..  _breaking-removed-obsolete-profile-creation-configuration:

========================================================
Breaking: Removed obsolete profile creation configuration
========================================================

Description
===========

The unused extension configuration options
``profile.autoCreateProfiles`` and ``profile.createProfileForUserGroups`` have
been removed from :file:`ext_conf_template.txt`. Profile creation is owned by
:guilabel:`EXT:academic_persons`; both options already had no effect in
:guilabel:`EXT:academic_persons_edit`.

Impact
======

Stored values for these options are ignored as before and are no longer shown
in the extension configuration. ``profile.allowedLanguages`` remains in this
extension because ProfileEditing uses it for translation synchronization.

Migration
=========

Configure automatic profile creation in :guilabel:`EXT:academic_persons`.

..  index:: Configuration, Extension configuration, Profile
