..  _breaking-removed-profile-editing-configuration-offer:

=================================================================
Breaking: ProfileEditing compatibility configuration is removed
=================================================================

Description
===========

InlineProfile is now the only profile-editing component offered by the
extension. The site set
``fgtclb/academic-persons-edit-profile-editing`` and the selectable static
TypoScript and page-TSconfig entries named
:guilabel:`Academic Persons Edit: Profile editing compatibility` have been
removed.

The stable aggregate site set ``fgtclb/academic-persons-edit`` and the static
template :guilabel:`Academic Persons Edit: All components` now deliver only the
InlineProfile component.

Impact
======

Site configurations that still list the removed compatibility set must remove
that dependency. Existing ``academicpersonsedit_profileediting`` content
records no longer receive legacy TypoScript through the aggregate
configuration.

Migration
=========

Replace old ProfileEditing content records with
``academicpersonsedit_inlineprofile`` and use one of these site sets:

* ``fgtclb/academic-persons-edit``
* ``fgtclb/academic-persons-edit-inline-profile``

For classic TypoScript installations, select either
:guilabel:`Academic Persons Edit: All components` or
:guilabel:`Academic Persons Edit: Inline profile editing` and remove the old
compatibility entry from page records and root templates.

..  index:: Backend, Site set, TypoScript, Inline editing, Migration
