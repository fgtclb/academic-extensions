..  _important-extension-point-policy:

===================================================
Important: What a project may build on is now named
===================================================

Description
===========

Until now nothing said which classes of the academic extensions a project may
rely on. Projects subclassed controllers, replaced them with a copy, and
replaced repositories and services through an XCLASS - and each of those broke
on releases that did not mention the class at all.

The new chapter :ref:`developers-extension-points` of this manual states the
public API of every academic extension and of :guilabel:`category_types`: the
events and the types they hand to a listener, the interfaces, the services and
classes a project names in its configuration or its code, two controller
traits, the domain models, and the templates, settings, TypoScript and
TSconfig keys, label keys and :file:`CategoryTypes.yaml`. Everything listed
carries the :php:`@api` tag in its docblock, and a release that breaks or
deprecates any of it says so in a :guilabel:`Breaking` or a
:guilabel:`Deprecation` changelog entry.

Everything the chapter does not list is not public API, whether it is
:php:`final` or not, and may change in any release without a changelog entry.
That includes every repository and every plugin controller. The plugin
controllers are all :php:`final` in 3.0.

Subclassing or XCLASSing a class is unsupported, listed or not, with one
exception: a subclass of a domain model, which is how a project adds fields to
a model. The :ref:`configuration check
<upgrade-check-configuration>` of :bash:`academic:upgrade:check` therefore
reports an XCLASS of a domain model as a notice instead of a warning, and no
longer fails because of it. Every other XCLASS is reported as before.

Impact
======

Nothing changes for a visitor or an editor. An installation that extends a
domain model through an XCLASS sees a notice instead of a warning from the
upgrade check, and the command exits with :bash:`0` if nothing else is found.

A project that XCLASSes a class the chapter does not list keeps working as
before, but should move that code to the events the chapter names before the
next update. A subclass or an XCLASS of a plugin controller no longer loads in
3.0, see the :guilabel:`Breaking` entries of :guilabel:`academic_bite_jobs`,
:guilabel:`academic_partners`, :guilabel:`academic_programs` and
:guilabel:`academic_projects`.

..  index:: PHP-API, ext:academic_base
