..  index:: ! Upgrade check
..  _upgrade-check:

=============
Upgrade check
=============

A project that overrides a Fluid template of an academic extension is told
nothing when the extension stops shipping that file. Fluid resolves the first
file it finds below the view root paths, and when it finds none of the project's
it renders the extension's own - silently. The override stays in the repository,
looks maintained and does nothing.

This extension ships the console command :bash:`academic:upgrade:check` for
exactly that. Run it before an upgrade of the academic extensions to see what
the project carries, and after one to see what the upgrade made dead.

..  _upgrade-check-usage:

Running it
==========

..  code-block:: bash

    vendor/bin/typo3 academic:upgrade:check academic_persons_edit \
        --override-path=EXT:my_site/Resources/Private/Extensions/AcademicPersonsEdit/

..  code-block:: text

    EXT:my_site/Resources/Private/Extensions/AcademicPersonsEdit/
      compared with EXT:academic_persons_edit/Resources/Private/
      x missing-upstream  Templates/Profile/Show.html
      = identical         Partials/Profile/Image.html
      ! case-mismatch     Pages/Academicprogram.html -> Pages/AcademicProgram.html

    2 problems and 1 notice in 1 override folder.

The first argument is the extension key of the academic extension whose
templates the folder overrides. Everything else is optional, but at least one
:bash:`--override-path` or a :bash:`--site` has to be given.

An override folder is named as an :bash:`EXT:` path, or as an absolute path
**inside the project root**: TYPO3 refuses any other absolute path, so a second
checkout next to the project cannot be checked - the command says so rather than
claiming the folder does not exist.

..  _upgrade-check-findings:

What it reports
===============

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
        -   At least one :bash:`missing-upstream` or :bash:`case-mismatch`.
    *   -   :bash:`2`
        -   Invalid input: the extension is not active, a folder does not
            exist or lies outside the project root, neither a folder nor a
            site was named, or the site could not be read.

A run that has both invalid input and findings exits with :bash:`2`: it did not
check everything it was asked to, so its report is not the whole answer. The
findings it did produce are printed all the same.

The command only reads. It never changes a file, so it is safe to run in a
pipeline against a production checkout:

..  code-block:: yaml
    :caption: .gitlab-ci.yml

    template-overrides:
      script:
        - vendor/bin/typo3 academic:upgrade:check academic_persons
            --override-path=EXT:my_site/Resources/Private/Extensions/AcademicPersons/
        - vendor/bin/typo3 academic:upgrade:check academic_persons_edit
            --override-path=EXT:my_site/Resources/Private/Extensions/AcademicPersonsEdit/

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
*   Static templates, TSconfig imports, site sets and XCLASSes.
*   Whether an upstream file *changed* since the project copied it. The command
    has no record of the version a copy was taken from, so :bash:`identical` is
    the only statement it can make about a copy that still exists upstream.
*   The settings file of :php:`EXT:academic_persons`. Its own command
    :bash:`academic:persons:migrate-settings` reports and migrates that.
