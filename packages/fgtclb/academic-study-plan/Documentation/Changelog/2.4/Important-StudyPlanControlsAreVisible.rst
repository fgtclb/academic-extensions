..  _important-study-plan-controls-are-visible:

==============================================
Important: The study plan controls are visible
==============================================

Description
===========

The plus, minus and close icons of the study plan content element were drawn
with a size of zero in the frontend. The template renders them inline with the
core icon ViewHelper, the shipped SVG files carry a :html:`viewBox` only, and
the core stylesheet that sizes the icon wrapper is loaded in the backend alone.
The close button of a module dialog was therefore invisible, and the semester
headers of the narrow accordion layout showed no marker.

The stylesheet of the content element now sizes the icons of a study plan, and
gives the close button of a module dialog a target of its own. A click on the
backdrop of an open module dialog closes it as well, as the close button and
the :kbd:`Escape` key already did. A click inside the dialog, and a text
selection that is released over the backdrop, leave it open.

Impact
======

Visitors see the close button of a module dialog and the plus and minus marker
of every semester on a narrow viewport. The column layout of a wide viewport
still shows no marker.

Affected Installations
======================

Every installation that renders the study plan content element with the
shipped stylesheet. Nothing has to be done on update.

A site package that sized the icons itself keeps working: the new rules are
scoped to :html:`.academic-study-plan .icon`, and a rule of the site package
with a higher specificity, or loaded later with the same one, still wins.

.. index:: Frontend, JavaScript, ext:academic_study_plan
