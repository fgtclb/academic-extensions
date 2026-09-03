
..  _bugfix-profile-editing-public-view-link:

====================================================
Bugfix: Resolve the ProfileEditing public View target
====================================================

The assigned-profile overview now targets the same public ``Detail`` plugin as
the Academic Persons list views. ProfileEditing copies
``plugin.tx_academicpersons.detailPid`` into its Extbase settings and generates
the link with the ``tx_academicpersons_detail`` namespace. It therefore no
longer sends ListAndDetail arguments back to the current editing page.
