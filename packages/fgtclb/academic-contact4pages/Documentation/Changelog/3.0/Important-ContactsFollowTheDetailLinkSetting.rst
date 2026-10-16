..  _important-contacts4pages-contacts-follow-the-detail-link-setting:

===========================================================================
Important: Contacts follow the detail link setting of the persons extension
===========================================================================

Description
===========

`EXT:academic_persons` 3.0 adds the setting
:typoscript:`plugin.tx_academicpersons.detailLink`, a site setting of its set
and a constant of its static templates, for a site without profile detail
pages. It takes the values `link`, the default, and `none`, which shows the
names of the profiles as text. The contacts of a page are rendered through the
:file:`Profile/Item` partial of that extension, and the setup of this
extension now maps the setting into its own plugin settings, as it maps
:typoscript:`plugin.tx_academicpersons.detailPid`. With `none`, the
contact list content element shows the names of its contacts without a link
as well.

Impact
======

Nothing changes while the setting keeps its default `link`. A site that sets
it to `none` loses the links of its contact lists too. The content element
has no option of its own for it. A site that wants the names of its contacts
linked nevertheless sets the plugin setting of this extension in its
TypoScript setup:

..  code-block:: typoscript
    :caption: TypoScript setup

    plugin.tx_academiccontacts4pages.settings.detailLink = link

See :ref:`integrator-contacts-list` for what the content element shows.

..  index:: Frontend, TypoScript, ext:academic_contacts4pages, ext:academic_persons
