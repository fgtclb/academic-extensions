..  _important-inline-profile-is-sole-editing-target:

====================================================
Important: InlineProfile is the sole editing target
====================================================

Description
===========

``academicpersonsedit_inlineprofile`` is now the only profile-editing content
element exposed in the backend CType selector and new-content-element wizard.
New editing features, AJAX routes, Fluid templates and browser behavior must be
implemented exclusively in the InlineProfile component.

InlineProfile now owns both the assigned-profile list and the selected-profile
editor. Its default ``list`` action does not re-enable or call the retained
``ProfileController``. Only the list's public :guilabel:`View` link deliberately
targets the ``academic_persons`` Detail plugin.

The previous ProfileEditing controllers, templates, translations and tests are
temporarily retained as source references. They are no longer offered through
a site set, static TypoScript template or selectable page TSconfig.
InlineProfile does not call those controllers or render those views, and its
test fixture creates the InlineProfile CType directly.

Impact
======

Editors and integrators are offered only InlineProfile. Projects that still
carry ProfileEditing records must migrate them to InlineProfile before removing
their own manually retained legacy configuration.

InlineProfile owns the only shipped TypoScript component, component site set
and selectable page TSconfig. The stable aggregate set and static template now
deliver InlineProfile alone.

..  index:: Backend, CType, Extbase, Inline editing, Migration
