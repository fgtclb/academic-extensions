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

Closing a module dialog with :kbd:`Escape` closed it without stopping its
audio, which kept playing behind the page. The audio now stops and rewinds
however the dialog is closed.

Version 2.3 asked the icon registry for the identifiers `plus`, `minus` and
`close-button`. Neither TYPO3 nor this extension registers them, so the study
plan rendered the placeholder of a missing icon in their place. The three icons
are now shipped with this extension and registered as
`academic-study-plan-plus`, `academic-study-plan-minus` and
`academic-study-plan-close`. The class of the icon markup follows the
identifier, so the stylesheet of the content element now toggles
:html:`.icon-academic-study-plan-plus` and
:html:`.icon-academic-study-plan-minus` instead of :html:`.icon-plus` and
:html:`.icon-minus`.

The button that opens a module dialog has no visible text. Its visually hidden
text now ends in *Show module details: <module>*, in German *Moduldetails
anzeigen*. The label was missing, so a screen reader announced a bare colon
followed by the module title.

Impact
======

Visitors see the close button of a module dialog and the plus and minus marker
of every semester on a narrow viewport, drawn by the icons of this extension
instead of the placeholder of a missing icon. The column layout of a wide
viewport still shows no marker.

Affected Installations
======================

Every installation that renders the study plan content element with the
shipped stylesheet and script. Nothing has to be done on update.

A site package that sized the icons itself keeps working: the new rules are
scoped to :html:`.academic-study-plan .icon`, and a rule of the site package
with a higher specificity, or loaded later with the same one, still wins.

A site package that registered icons named `plus`, `minus` or `close-button`
for this element, or styles the classes :html:`.icon-plus` and
:html:`.icon-minus` in it, moves to the new identifiers and classes.

.. index:: Frontend, JavaScript, ext:academic_study_plan
