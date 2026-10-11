..  _important-study-plan-controls-are-visible:

==============================================
Important: The study plan controls are visible
==============================================

Description
===========

The plus, minus and close icons of the study plan content element were drawn
with a size of zero in the frontend. The templates render them inline with the
icon ViewHelper of :composer:`fgtclb/academic-base`, the shipped SVG files
carried a :html:`viewBox` only, and the core stylesheet that sizes the icon
wrapper is loaded in the backend alone. The close button of a module dialog
was therefore invisible, and the semester headers of the narrow accordion
layout showed no marker.

The icons carry a size of their own now, and the stylesheet of the site sizes
the icons of a study plan and gives the close button of a module dialog a
target of its own. The extension ships no stylesheet in 3.0, see
:ref:`breaking-study-plan-ships-no-stylesheet`. The stylesheet of the
development instances of the mono repository carries those rules as an
example. A click on the
backdrop of an open module dialog closes it as well, as the close button and
the :kbd:`Escape` key already did. A click inside the dialog, and a text
selection that is released over the backdrop, leave it open.

Closing a module dialog with :kbd:`Escape` closed it without stopping its
audio, which kept playing behind the page. The audio now stops and rewinds
however the dialog is closed.

Impact
======

Visitors see the close button of a module dialog and the plus and minus marker
of every semester on a narrow viewport. The column layout of a wide viewport
still shows no marker.

Affected Installations
======================

Every installation that renders the study plan content element with the
shipped script. On update, take the rules for the icons and the close button
into the stylesheet of the site package, from the example linked in
:ref:`breaking-study-plan-ships-no-stylesheet`.

A site package that sized the icons itself keeps working: the example scopes
its rules to :html:`.academic-study-plan .icon`, and a rule of the site package
with a higher specificity, or loaded later with the same one, still wins.

..  index:: Frontend, JavaScript, ext:academic_study_plan
