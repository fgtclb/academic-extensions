..  index:: ! Styling
..  _styling:

=======
Styling
=======

The job list renders its markup with the class system of the academic
extensions and ships no styling for it. The system, the classes of Bootstrap it
keeps and how a site package styles the markup are described once, in the
chapter `Styling of academic_base
<https://docs.typo3.org/p/fgtclb/academic-base/main/en-us/Styling/Index.html>`__.
This chapter shows how the markup of this extension is built up.

..  _styling-outermost-element:

The outermost element
=====================

..  list-table::
    :header-rows: 1
    :widths: 30 35 35

    *   -   Plugin
        -   Content element type
        -   Outermost element
    *   -   Job list
        -   `academicbitejobs_list`
        -   `academic-bite-jobs-list`

..  _styling-list:

The job list
============

The list renders the view the content element selects. The card view and the
list view render the same markup and differ only in the class of the item list:

..  code-block:: html

    <div class="academic-bite-jobs-list">
        <!-- the header of the content element, when the plugin renders it -->
        <div class="ace-itemlist ace-card-view">     <!-- or ace-list-view -->
            <article class="ace-item">
                <div class="ace-item-content">
                    <h2 class="ace-title"><a href="…">Job title</a></h2>
                    <ul class="ace-list">
                        <li class="ace-list-item">
                            <b class="ace-label">Ends on:</b>
                            <span class="ace-value">31.12.2026</span>
                        </li>
                    </ul>
                </div>
            </article>
        </div>
    </div>

The table view renders one row per job:

..  code-block:: html

    <table class="ace-table">
        <thead class="ace-head">
            <tr class="ace-item">
                <th class="ace-label">Title</th>
                <th class="ace-label">Ends on</th>
            </tr>
        </thead>
        <tbody class="ace-body">
            <tr class="ace-item">
                <td class="ace-value"><a class="ace-link" href="…">Job title</a></td>
                <td class="ace-value">31.12.2026</td>
            </tr>
        </tbody>
    </table>

*   The level of the heading of a job follows the header layout of the content
    element. With a header layout from 1 to 4 it is one level lower when the
    jobs are grouped or the content element has a subheader, two levels lower
    when both apply, and never lower than :html:`<h6>`. With the default
    layout, an :html:`<h2>`, only the grouping makes it an :html:`<h3>`.
*   Grouped jobs render one heading `ace-title` per group, followed by the
    item list or the table of the group.
*   A list without jobs renders the message in a :html:`<span class="ace-empty">`.
*   The head and body of the table are `ace-head` and `ace-body`. The table
    view of :guilabel:`EXT:academic_persons` names them `ace-table-header` and
    `ace-table-body`, the profile list of
    :guilabel:`EXT:academic_persons_edit` `ace-header` and `ace-content`.

..  _styling-example:

An example
==========

Rules in the stylesheet of the site package, scoped by the outermost element:

..  code-block:: css

    .academic-bite-jobs-list .ace-card-view {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(18rem, 1fr));
        gap: 1.5rem;
    }

    .academic-bite-jobs-list .ace-list {
        list-style: none;
        padding-left: 0;
    }

To change the markup itself, override the partials as described in
:ref:`templates`.
