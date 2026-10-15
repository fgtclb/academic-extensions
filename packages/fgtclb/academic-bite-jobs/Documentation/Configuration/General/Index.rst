..  index:: Configuration
..  _configuration-general:

=====================
General configuration
=====================

The view of the job list
========================

The view, :guilabel:`List`, :guilabel:`Card` or :guilabel:`Table`, is an option
of each content element, see :confval:`Select view <job-list-bite-view>`.

..  _configuration-general-group-by:

Grouping the jobs
=================

The job list renders the jobs ungrouped. To group them, name a field of the job
postings in TypoScript:

..  code-block:: typoscript
    :caption: TypoScript setup

    plugin.tx_academicbitejobs.settings.jobs.groupBy = department

The list then renders one group per value of that field, headed by the value,
and every job title one heading level below the group heading. The field has to
be part of the job postings, either as the B-ITE API answers them for the job
listing key of the content element, or written into them by an event listener,
see :ref:`developers-example-grouping`. It is not offered in the plugin
settings. An empty value, or `none`, switches the grouping off again.

A content element saved before version 2.1 may still store a grouping setting of
its own, which wins over the TypoScript. The upgrade wizard
`academicBiteJobs_listViewFlexFormUpgradeWizard` removes it.

..  _configuration-general-content-element-header:

The header of the content element
=================================

The header and the subheader an editor enters on a :guilabel:`Job List b-ite`
content element are rendered by the content element layout of the site, as for
any other content element. The layouts of :guilabel:`EXT:fluid_styled_content`
and of the bootstrap package do that, and the plugin renders no header of its
own.

A site whose content element layout renders no header, because its element
templates render it instead, lets the plugin render it:

..  code-block:: typoscript
    :caption: TypoScript constants

    plugin.tx_academicbitejobs.renderContentElementHeader = 1

The template then renders the header partial of
:guilabel:`EXT:fluid_styled_content` above the jobs, so the switch needs that
extension installed; this extension does not require it. Do not switch it on
where the layout renders the header: the header then appears twice.

For the header layout :guilabel:`Default`, the partial takes the heading level
from :typoscript:`plugin.tx_academicbitejobs.settings.defaultHeaderType`, which
is mapped from the constant :typoscript:`styles.content.defaultHeaderType` of
:guilabel:`EXT:fluid_styled_content`. A site that does not include the
TypoScript of :guilabel:`EXT:fluid_styled_content` sets the setting itself;
without it, such a header renders as an empty :html:`<header>` element.
