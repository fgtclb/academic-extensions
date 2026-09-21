..  index:: ! Upgrade check
..  _upgrade-check:

=============
Upgrade check
=============

An upgrade of the academic extensions does not tell an installation what it
broke. A project override of a Fluid template the extension no longer ships
simply stops taking part - Fluid renders the extension's own file and says
nothing. A TypoScript record that includes a static template folder that no
longer holds TypoScript delivers nothing, and says nothing. A page TSconfig
import of a file that was renamed is skipped, and says nothing. The
configuration stays in the database, looks maintained and does nothing.

This extension ships the console command :bash:`academic:upgrade:check` for
exactly that. Run it before an upgrade of the academic extensions to see what
the project carries, and after one to see what the upgrade made dead.

It runs two groups of checks:

..  list-table::
    :header-rows: 1

    *   -   Group
        -   Reads
        -   Runs
    *   -   :ref:`Configuration <upgrade-check-configuration>`
        -   The TypoScript records, the page TSconfig, the site configurations
            and the XCLASS registry of the installation.
        -   Always. It takes no argument.
    *   -   :ref:`Template overrides <upgrade-check-findings>`
        -   The Fluid files below the override folders of one extension.
        -   When an extension key is named.

..  _upgrade-check-usage:

Running it
==========

Without an argument the command checks the configuration of the installation:

..  code-block:: bash

    vendor/bin/typo3 academic:upgrade:check

..  code-block:: text

    Configuration

      ! static-template          sys_template:1
        The TypoScript record "Main" on page 1 includes the static template
        "EXT:academic_persons/Configuration/TypoScript/AcademicPersonsDefault", which holds no
        TypoScript in the installed version: TYPO3 skips it without a message. Select the static
        template of the installed version instead, or remove the value.

    1 problem and 0 notices in the stored configuration.

Naming an extension key adds the template override group for that extension:

..  code-block:: bash

    vendor/bin/typo3 academic:upgrade:check academic_persons_edit \
        --override-path=EXT:my_site/Resources/Private/Extensions/AcademicPersonsEdit/

..  code-block:: text

    Template overrides

    EXT:my_site/Resources/Private/Extensions/AcademicPersonsEdit/
      compared with EXT:academic_persons_edit/Resources/Private/
      x missing-upstream  Templates/Profile/Show.html
      = identical         Partials/Profile/Image.html
      ! case-mismatch     Pages/Academicprogram.html -> Pages/AcademicProgram.html

    2 problems and 1 notice in 1 override folder.

The first argument is the extension key of the academic extension whose
templates the folder overrides. When it is given, at least one
:bash:`--override-path` or a :bash:`--site` has to be given with it. The
configuration group runs in either case, so a pipeline that checks one
extension's overrides cannot miss the configuration findings.

An override folder is named as an :bash:`EXT:` path, or as an absolute path
**inside the project root**: TYPO3 refuses any other absolute path, so a second
checkout next to the project cannot be checked - the command says so rather than
claiming the folder does not exist.

..  _upgrade-check-configuration:

What it reports about the configuration
=======================================

The configuration group reads the stored configuration of the installation and
reports what no longer reaches the academic extensions, or what reaches them
twice. Every finding names where the configuration is stored - a TypoScript
record, a page, a site or a class - so it can be opened in the backend right
away.

..  list-table::
    :header-rows: 1

    *   -   Finding
        -   Meaning
        -   What to do
    *   -   :bash:`static-template`
        -   A TypoScript record includes a static template of an academic
            extension that the installation does not have any more, or whose
            folder holds no TypoScript in the installed version. TYPO3 skips
            the value without a message.
        -   Open the record, select the static template of the installed
            version in :guilabel:`Include static (from extensions)`, and
            remove the dead value.
    *   -   :bash:`tsconfig-import`
        -   A page, or the :file:`page.tsconfig` of a site, imports or selects
            a page TSconfig file of an academic extension that is not there.
            Measured against the last released 2.x, :bash:`2.3.4`: the page
            TSconfig folders were unified to
            :file:`Configuration/TSconfig/` - they were spelled
            :file:`TsConfig`, :file:`TSConfig` and :file:`TSconfig` depending
            on the extension - and the set folders
            :file:`Sets/AcademicPersonsDefault/` and
            :file:`Sets/AcademicBaseCTypeGroup/` became :file:`Sets/Default/`
            and :file:`Sets/CTypeGroup/`. Both are part of the 2.4
            restructuring that 3.0 inherited.
        -   Correct the path, or remove the import when the extension
            delivers the same TSconfig by itself today.
    *   -   :bash:`tsconfig-syntax`
        -   A page TSconfig includes an academic file with the
            :typoscript:`<INCLUDE_TYPOSCRIPT:` syntax. TYPO3 v13 deprecated it
            and TYPO3 v14 removed it - there the line is ignored without a
            message, whether or not the file it names exists.
        -   Write it as :typoscript:`@import 'EXT:...'`.
    *   -   :bash:`alias-set`
        -   A site depends on :yaml:`fgtclb/academic-persons-default` or
            :yaml:`fgtclb/academic-study-plan-default`. Both are aliases
            without payload, kept so that site configurations written before
            3.0 keep working, and both go away in 4.0.
        -   Depend on :yaml:`fgtclb/academic-persons` or
            :yaml:`fgtclb/academic-study-plan` instead, or on the component
            sets the site actually needs.
    *   -   :bash:`set-and-static-template`
        -   A site depends on a set of an academic extension **and** a
            TypoScript record on its root page includes a static template of
            the same extension. Both are parsed, the set first, so the record
            can reset a constant the site settings had set.
        -   Use one mechanism per site: see the :guilabel:`Configuration`
            chapter of the extension in question.
    *   -   :bash:`xclass`
        -   A class of an academic extension is replaced through
            :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']`. It is an
            error when the replaced class is :php:`final` - a subclass of it
            is a fatal error - and when the installed version does not ship
            the class any more.
        -   Achieve the change through an event listener, a service
            decoration or a replacement service. None of the academic
            extensions is an API for subclassing.

Only the academic extensions are looked at: an :bash:`EXT:` path below
:bash:`academic_*` or :bash:`category_types`, a set of one of those extensions,
a class below :php:`FGTCLB\Academic...` or :php:`FGTCLB\CategoryTypes\`. A
project's own static template, TSconfig import or XCLASS of a core class is
none of this command's business.

A hidden TypoScript record is not reported: it delivers nothing to anybody, so
there is nothing about it to fix. The page TSconfig of a hidden page **is**
reported, because TYPO3 reads it all the same.

..  _upgrade-check-report:

In the backend
==============

With :php:`EXT:reports` installed the same findings are shown in
:guilabel:`System > Reports > Status report`, under :guilabel:`Academic Base`,
with one entry per finding and a single :guilabel:`Nothing stale` entry when
there is nothing to report. :php:`EXT:reports` stays optional - without it the
command is the only place the findings appear, and everything else works
unchanged.

..  _upgrade-check-findings:

What it reports for template overrides
======================================

..  list-table::
    :header-rows: 1

    *   -   Finding
        -   Meaning
        -   What to do
    *   -   :bash:`missing-upstream`
        -   The extension ships no file at that relative path, not even one
            that differs in case. Fluid never resolves the override.
        -   Delete the file, or port its change to the template that replaced
            it.
    *   -   :bash:`case-mismatch`
        -   The extension ships the file under a name that differs only in
            case, so the override resolves on a case insensitive file system -
            a macOS development machine - and is dead on the Linux server.
        -   Rename the file to the upstream spelling the finding names.
    *   -   :bash:`identical`
        -   The override is a byte identical copy of the file it overrides. It
            renders, and it freezes the markup of that one file at the version
            it was copied from: the next release changes nothing about it.
        -   Delete it unless the freeze is intended.

A changed copy of a file the extension still ships is not reported - that is a
deliberate override, and it works.

Only :file:`*.html` files are compared. XLIFF files are overridden through
:php:`locallangXMLOverride`, not through a view root path, and are none of this
command's business.

A file named :file:`Name.fluid.html` against an upstream :file:`Name.html` is
reported as :bash:`missing-upstream`. That is deliberate: Fluid resolves the
:file:`.fluid.html` form on TYPO3 v14 and not on TYPO3 v13, so such an override
is dead on one of the two supported versions.

..  _upgrade-check-exit-status:

The exit status
===============

..  list-table::
    :header-rows: 1

    *   -   Status
        -   Meaning
    *   -   :bash:`0`
        -   No problem was found. Notices alone do not fail the run.
    *   -   :bash:`1`
        -   At least one problem: a configuration warning or error, or a
            :bash:`missing-upstream` or :bash:`case-mismatch` override.
    *   -   :bash:`2`
        -   Invalid input: the extension is not active, a folder does not
            exist or lies outside the project root, an extension key was
            named without a folder or a site, a folder or a site was named
            without an extension key, or the site could not be read.

A run that has both invalid input and findings exits with :bash:`2`: it did not
check everything it was asked to, so its report is not the whole answer. The
findings it did produce are printed all the same.

The command only reads. It never changes a file, so it is safe to run in a
pipeline against a production checkout:

..  code-block:: yaml
    :caption: .gitlab-ci.yml

    academic-upgrade-check:
      script:
        - vendor/bin/typo3 academic:upgrade:check academic_persons
            --override-path=EXT:my_site/Resources/Private/Extensions/AcademicPersons/
        - vendor/bin/typo3 academic:upgrade:check academic_persons_edit
            --override-path=EXT:my_site/Resources/Private/Extensions/AcademicPersonsEdit/

The configuration group runs with each of those two invocations, so it does not
need a line of its own.

..  _upgrade-check-upstream-path:

An override folder that mirrors a single folder
===============================================

An override folder is compared with :file:`EXT:<extension>/Resources/Private/`
by default, which is the layout most projects use: the override folder holds
:file:`Templates/`, :file:`Partials/` and :file:`Layouts/` just like the
extension does.

A folder that mirrors one of them instead needs :bash:`--upstream-path`:

..  code-block:: bash

    vendor/bin/typo3 academic:upgrade:check academic_persons \
        --override-path=EXT:my_site/Resources/Private/Partials/Academic/ \
        --upstream-path=EXT:academic_persons/Resources/Private/Partials/

Without it every file in that folder is compared with a path that does not
exist below :file:`Resources/Private/`, and the whole folder is reported as
:bash:`missing-upstream`.

..  _upgrade-check-site:

Letting a site name the folders
===============================

Reading the view root paths of every plugin of every site by hand is the step
this command exists to remove. With :bash:`--site` and a site identifier it
reads them itself:

..  code-block:: bash

    vendor/bin/typo3 academic:upgrade:check academic_persons --site=main

It builds a frontend environment for the site's root page and takes
:typoscript:`plugin.tx_academicpersons.view.templateRootPaths`,
:typoscript:`partialRootPaths` and :typoscript:`layoutRootPaths` from the
rendered TypoScript. Every folder is compared with the upstream folder of its
own kind, so :bash:`--upstream-path` is not needed here. The option may be
combined with :bash:`--override-path`.

Each reported folder names the TypoScript path it came from:

..  code-block:: text

    EXT:my_site/Resources/Private/Extensions/AcademicPersons/Partials/ (plugin.tx_academicpersons.view.partialRootPaths.1)
      compared with EXT:academic_persons/Resources/Private/Partials/
      x missing-upstream  Profile/Address.html

Not every root path is a project override. The partial root paths of the
academic plugins name the shared partials of :php:`EXT:academic_base`, and those
of :php:`EXT:academic_persons_edit` name the partials of
:php:`EXT:fluid_styled_content`. A root path is treated as a project override
only when it lies outside the checked extension, outside every package that
extension requires and outside the TYPO3 system extensions.

A root path naming a folder that is not there is reported with the TypoScript
path that named it and skipped; the remaining root paths of the site are still
checked, and the run ends with :bash:`2`.

The :typoscript:`page.10` root paths of a site are deliberately not read: a site
shares them with its theme, so every theme template would be reported as
:bash:`missing-upstream`. Check page template overrides by naming their folder
with :bash:`--override-path`.

..  note::

    The :bash:`--site` mode needs the extension
    :php:`environment_state_manager`, which :php:`EXT:academic_base` requires.
    A Composer managed installation has it; a classic installation has to have
    it installed.

..  _upgrade-check-not-checked:

What it does not check
======================

*   A site for :php:`EXT:academic_base`, :php:`EXT:academic_persons_sync` or
    :php:`EXT:category_types`. Nine of the twelve extensions ship view root
    paths; these three ship none, so :bash:`--site` always answers "configures
    no view root path" for them. Check their overrides - the shared image
    partial of :php:`EXT:academic_base`, for instance - by naming the folder
    with :bash:`--override-path`.
*   :typoscript:`plugin.tx_<extension>_<plugin>.view` - only the extension-wide
    :typoscript:`plugin.tx_<extension>.view` is read. The academic extensions
    configure their root paths extension-wide; a project that adds its folder
    to a single plugin instead has to name that folder with
    :bash:`--override-path`.
*   Static templates, TSconfig imports and XCLASSes of a project's own
    extensions or of the TYPO3 core. The configuration group is about what the
    academic extensions no longer deliver.
*   The :guilabel:`Constants` and :guilabel:`Setup` fields of a TypoScript
    record. An :typoscript:`@import` of a TypoScript file that is gone is
    dropped there just as silently, and a site package that imported one of the
    removed files by path is a real case - it is simply not part of this check
    yet. Only :guilabel:`Include static (from extensions)` is read.
*   The content of a page TSconfig file that *does* resolve. TYPO3 follows the
    imports inside it; this check does not.
*   A :typoscript:`clear` flag of a TypoScript record on a site that is driven
    by site sets, which throws the whole set contribution away.
*   User TSconfig. No academic extension ships any.
*   Whether a page TSconfig import that *does* resolve still delivers what it
    used to. The check answers "does TYPO3 read this", not "does it still mean
    the same".
*   Whether an upstream file *changed* since the project copied it. The command
    has no record of the version a copy was taken from, so :bash:`identical` is
    the only statement it can make about a copy that still exists upstream.
*   The settings file of :php:`EXT:academic_persons`. Its own command
    :bash:`academic:persons:migrate-settings` reports and migrates that.
