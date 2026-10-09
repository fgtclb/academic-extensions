..  index:: ! Styling
..  _styling:

=======
Styling
=======

The contact list renders its markup with the class system of the academic
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
    *   -   Contact list
        -   `academiccontacts4pages_list`
        -   `academic-contacts4pages-list`

..  _styling-list:

The contact list
================

Every contact is rendered as the profile item of
:guilabel:`EXT:academic_persons`, inside a grid column of an item list:

..  code-block:: html

    <div class="academic-contacts4pages-list">
        <!-- the header of the content element, when the plugin renders it -->
        <div class="ace-content">
            <div class="ace-itemlist">
                <div class="row">
                    <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                        <div class="ace-item">
                            <p class="ace-role">Role of the contact</p>
                            <!-- the profile item of EXT:academic_persons -->
                            <article class="ace-item">…</article>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

*   With :guilabel:`Group by role`, every role renders an `ace-itemlist` of its
    own. Its `row` starts with the heading of the role, a :html:`<header
    class="ace-header">` around an `ace-title`, before the grid columns. The
    contacts without a role follow in a last `ace-itemlist` without a heading.
*   A grouped contact does not repeat its role in an `ace-role`.
*   The profile item inside is built up as described in the chapter
    `Styling of academic_persons
    <https://docs.typo3.org/p/fgtclb/academic-persons/main/en-us/Styling/Index.html>`__.
    The outer `ace-item` is the cell of the contact list, the inner
    :html:`<article class="ace-item">` the profile.

..  _styling-example:

An example
==========

Rules in the stylesheet of the site package, scoped by the outermost element:

..  code-block:: css

    .academic-contacts4pages-list .ace-role {
        margin-bottom: 0.25rem;
        font-weight: 600;
    }

    .academic-contacts4pages-list .ace-itemlist + .ace-itemlist {
        margin-top: 2rem;
    }

Every contact is rendered through the partial :file:`Contacts/Item.html`, so a
project that needs other markup for a contact overrides that one partial
rather than the list template.
