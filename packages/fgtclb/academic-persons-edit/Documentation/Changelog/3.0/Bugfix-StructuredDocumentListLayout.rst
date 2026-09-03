..  _bugfix-structured-document-list-layout:

=====================================================
Bugfix: Structured document lists are easier to scan
=====================================================

Description
===========

Structured document sections now use shared desktop column headings, compact
flat records, separating borders and alternating row backgrounds. Date columns
remain narrow while title and position columns use the remaining space. The
existing entry actions and their ordering are unchanged.

Small viewports continue to show field labels inside each record. Empty
sections render their translated placeholder as a compact status line below the
section heading, so consecutive empty sections no longer create large gaps.
The column heading and empty state update without reloading after records are
created or deleted.

Impact
======

Structured profile information follows a consistent table-like layout without
losing responsive labels or the existing inline-edit controls.

..  index:: Frontend, Fluid, Layout, ext:academic_persons_edit
