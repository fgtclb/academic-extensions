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
deliberately retained during the migration as a reference. Its Extbase
registration also remains temporarily compatible with existing content
records. InlineProfile does not call those controllers or render those views,
and its test fixture creates the InlineProfile CType directly.

Impact
======

Editors can create only new InlineProfile elements. Existing ProfileEditing
records can continue to render during the transition, but projects should move
them to InlineProfile. Once the inline implementation is complete, the isolated
legacy compatibility block and reference sources can be removed without
changing InlineProfile.

..  index:: Backend, CType, Extbase, Inline editing, Migration
