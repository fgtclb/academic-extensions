..  index:: Integration; Permission sets
..  _integration-permission-sets:

===============
Permission sets
===============

`b13/permission-sets <https://github.com/b13/permission-sets>`__ keeps the
permissions of backend user groups in YAML files, so they are deployed with the
project instead of being clicked together per installation. This page lists
what an editor group needs for each academic extension: its tables, the fields
it adds to tables of TYPO3, its content types and its page types.

..  note::

    Written for b13/permission-sets 1.1.0 and checked against its
    source. The lists below are taken from the TCA of this release of the
    academic extensions. This repository does not install the package, so the
    files are not covered by a test.

..  contents::
    :local:
    :depth: 1

..  _integration-permission-sets-format:

What a permission set grants
============================

A permission set is a YAML file in :file:`Configuration/PermissionSets/` of an
extension, or in :file:`config/permission-sets/` of the project. It is selected
in the field :guilabel:`Permission Sets` of a backend user group, and adds to
what the group grants by itself:

..  list-table::
    :header-rows: 1
    :widths: 40 60

    *   -   In the file
        -   Adds to the backend user group
    *   -   :yaml:`resources.<table>.permissions: ['read', 'write']`
        -   :guilabel:`Tables (listing)` and :guilabel:`Table permissions`
    *   -   :yaml:`resources.<table>.fields: '*'`
        -   Every field of the table that TCA marks as :php:`exclude`, as
            :guilabel:`Allowed fields`
    *   -   :yaml:`resources.<table>.fields: [...]`
        -   The fields listed, as :guilabel:`Allowed fields`
    *   -   :yaml:`resources.pages.types: [...]`
        -   :guilabel:`Allowed page types`
    *   -   :yaml:`resources.tt_content.types: [...]`
        -   The content types, as :guilabel:`Explicitly allow field values`

A field that is not an :php:`exclude` field is editable for every group that may
edit its table, so the lists below name the :php:`exclude` fields only. The
fields of the tables of an extension are granted with `'*'`, which follows the
TCA of the installed version.

The examples grant what an extension adds. The set the project uses for its
editors in general has to grant the rest, which the academic extensions rely
on as well:

*   The tables :sql:`pages` and :sql:`tt_content` themselves, and the
    database mounts.
*   The field :sql:`categories` of :sql:`pages` and the table
    :sql:`sys_category`. Programs, projects and partners are filtered by the
    categories of their pages, and TYPO3 makes every field of the type
    `category` an :php:`exclude` field.
*   The table :sql:`sys_file_reference`, for profile images and the media of
    program, project and partner pages.

..  _integration-permission-sets-persons:

academic_persons
================

The field of :sql:`fe_users` links a frontend user to the profiles it may edit
with :guilabel:`EXT:academic_persons_edit`.

..  code-block:: yaml

    label: 'Academic persons'
    resources:
      fe_users:
        fields:
          - tx_academicpersons_profiles
      tt_content:
        types:
          - academicpersons_list
          - academicpersons_listanddetail
          - academicpersons_detail
          - academicpersons_card
          - academicpersons_selectedprofiles
          - academicpersons_selectedcontracts
      tx_academicpersons_domain_model_profile:
        permissions: ['read', 'write']
        fields: '*'
      tx_academicpersons_domain_model_contract:
        permissions: ['read', 'write']
        fields: '*'
      tx_academicpersons_domain_model_address:
        permissions: ['read', 'write']
        fields: '*'
      tx_academicpersons_domain_model_email:
        permissions: ['read', 'write']
        fields: '*'
      tx_academicpersons_domain_model_phone_number:
        permissions: ['read', 'write']
        fields: '*'
      tx_academicpersons_domain_model_location:
        permissions: ['read', 'write']
        fields: '*'
      tx_academicpersons_domain_model_organisational_unit:
        permissions: ['read', 'write']
        fields: '*'
      tx_academicpersons_domain_model_function_type:
        permissions: ['read', 'write']
        fields: '*'
      tx_academicpersons_domain_model_profile_information:
        permissions: ['read', 'write']
        fields: '*'

..  _integration-permission-sets-persons-edit:

academic_persons_edit
=====================

..  code-block:: yaml

    label: 'Academic persons: frontend editing'
    resources:
      tt_content:
        types:
          - academicpersonsedit_profileediting

..  _integration-permission-sets-contacts4pages:

academic_contacts4pages
=======================

The extension adds a field to :sql:`pages` and one to the contracts of
:guilabel:`EXT:academic_persons`.

..  code-block:: yaml

    label: 'Academic contacts for pages'
    resources:
      pages:
        fields:
          - tx_academiccontacts4pages_contacts
      tt_content:
        types:
          - academiccontacts4pages_list
      tx_academicpersons_domain_model_contract:
        fields:
          - tx_academiccontacts4pages_contacts
      tx_academiccontacts4pages_domain_model_contact:
        permissions: ['read', 'write']
        fields: '*'
      tx_academiccontacts4pages_domain_model_role:
        permissions: ['read', 'write']
        fields: '*'

..  _integration-permission-sets-jobs:

academic_jobs
=============

..  code-block:: yaml

    label: 'Academic jobs'
    resources:
      tt_content:
        types:
          - academicjobs_newjobform
          - academicjobs_list
          - academicjobs_detail
      tx_academicjobs_domain_model_job:
        permissions: ['read', 'write']
        fields: '*'

..  _integration-permission-sets-bite-jobs:

academic_bite_jobs
==================

..  code-block:: yaml

    label: 'Academic B-ITE jobs'
    resources:
      tt_content:
        types:
          - academicbitejobs_list

..  _integration-permission-sets-partners:

academic_partners
=================

A partner is a page of the page type 40. The other fields the extension
defines on :sql:`pages` are not :php:`exclude` fields.

..  code-block:: yaml

    label: 'Academic partners'
    resources:
      pages:
        types: [40]
        fields:
          - link
          - tx_academicpartners_partnerships
      tt_content:
        types:
          - academicpartners_list
          - academicpartners_map
          - academicpartners_partnershipslist
          - academicpartners_partnershipsteaser
      tx_academicpartners_domain_model_partnership:
        permissions: ['read', 'write']
        fields: '*'
      tx_academicpartners_domain_model_role:
        permissions: ['read', 'write']
        fields: '*'

..  _integration-permission-sets-programs:

academic_programs
=================

A program is a page of the page type 20, and none of its fields on
:sql:`pages` is an :php:`exclude` field.

..  code-block:: yaml

    label: 'Academic programs'
    resources:
      pages:
        types: [20]
      tt_content:
        types:
          - academicprograms_programlist
          - academicprograms_programdetails
          - academicprograms_programfinder

..  _integration-permission-sets-projects:

academic_projects
=================

A project is a page of the page type 30.

..  code-block:: yaml

    label: 'Academic projects'
    resources:
      pages:
        types: [30]
        fields:
          - tx_academicprojects_project_title
          - tx_academicprojects_short_description
          - tx_academicprojects_start_date
          - tx_academicprojects_end_date
          - tx_academicprojects_budget
          - tx_academicprojects_funders
      tt_content:
        types:
          - academicprojects_projectlist
          - academicprojects_projectlistsingle

..  _integration-permission-sets-study-plan:

academic_study_plan
===================

The two fields the extension adds to :sql:`tt_content` are not :php:`exclude`
fields.

..  code-block:: yaml

    label: 'Academic study plan'
    resources:
      tt_content:
        types:
          - academic_study_plan
      tx_academicstudyplan_domain_model_semester:
        permissions: ['read', 'write']
        fields: '*'
      tx_academicstudyplan_domain_model_module:
        permissions: ['read', 'write']
        fields: '*'
      tx_academicstudyplan_domain_model_category:
        permissions: ['read', 'write']
        fields: '*'

..  _integration-permission-sets-others:

The other extensions
====================

:guilabel:`EXT:academic_base`, :guilabel:`EXT:academic_persons_sync` and
:guilabel:`EXT:category_types` add no table, no content type and no
:php:`exclude` field. The field :sql:`type`, which
:guilabel:`EXT:category_types` adds to :sql:`sys_category`, is editable for
every group that may edit categories.
