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
                        <p class="ace-role">Role of the contact</p>
                        <!-- the profile item of EXT:academic_persons -->
                        <article class="ace-item">…</article>
                    </div>
                </div>
            </div>
        </div>
    </div>

*   With :guilabel:`Group by role`, every role renders an `ace-itemlist` of its
    own. Its `row` starts with the heading of the role, a :html:`<header
    class="ace-header">` around an `ace-title`, before the grid columns. The
    contacts without a role follow in a last `ace-itemlist` without a heading.
    A grouped contact does not repeat its role in an `ace-role`.
*   The profile item is built up as described in the chapter
    `Styling of academic_persons
    <https://docs.typo3.org/p/fgtclb/academic-persons/main/en-us/Styling/Index.html>`__.
    The grid column is the cell of the contact list and the
    :html:`<article class="ace-item">` is the profile. In the list that is not
    grouped, the role is a :html:`<p class="ace-role">` in that column, in
    front of the article.

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

The development instances style the contacts with the partial
`_academic-persons-list.scss
<https://github.com/fgtclb/academic-extensions/blob/main/packages-dev/dev-site/Resources/Private/Scss/frontend/_academic-persons-list.scss>`__,
which styles the profile lists of `academic_persons` as well, since a contact
renders the same item markup, together with the shared rules of
`_academic-base.scss
<https://github.com/fgtclb/academic-extensions/blob/main/packages-dev/dev-site/Resources/Private/Scss/frontend/_academic-base.scss>`__,
both of the site package `academics_dev_site`, a complete example to copy
from.

Every contact is rendered through the partial :file:`Contacts/Item.html`, so a
project that needs other markup for a contact overrides that one partial
rather than the list template.
