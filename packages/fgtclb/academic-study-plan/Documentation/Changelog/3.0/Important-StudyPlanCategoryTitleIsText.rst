..  _important-study-plan-category-title-is-text:

==========================================================
Important: A study plan category title is text, not markup
==========================================================

Description
===========

The script of the study plan content element builds the category filter by
cloning the one list item the template renders, substituting the category's
id, colour and title into the placeholders it carries. It did that on the
markup as a **string** and parsed the result with :js:`innerHTML`, so a
category title containing :html:`<` reached the page as markup rather than as
text - and a title is written by an editor in the backend.

The substitution walks the cloned element's attributes and text nodes now.
Neither can turn a title into markup, whatever it contains.

The colour is treated the same way, for a second reason: it is written into
:html:`style="--category-color: …"`, where a :html:`;` would start a
declaration of its own. Only the shapes a colour field can produce are passed
through - a hexadecimal value, a plain keyword, or an :css:`rgb()` /
:css:`rgba()` function. Anything else leaves the category without a colour,
which is what an empty value already meant.

Impact
======

A category whose title contains no :html:`<`, :html:`&` or quote renders
exactly as before. A title that does now renders as the text the editor typed,
on the filter button and in its :html:`aria-label`.

Affected Installations
======================

Every installation that renders the study plan content element with categories
on its modules. Nothing has to be done on update.

An installation that deliberately put markup in a category title to style the
filter button loses it, and should style :html:`.filter button` - or the
:html:`data-category-id` of that one category - instead.

.. index:: Frontend, JavaScript, ext:academic_study_plan
