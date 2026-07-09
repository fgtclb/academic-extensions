..  _installation:

============
Installation
============

The extension has to be installed like any other TYPO3 CMS extension. As it is a
base extension for the academic suite, it is usually installed automatically as
a dependency, but it can also be required explicitly.

..  tabs::

    ..  group-tab:: Composer

        ..  code-block:: bash
            :caption: Install the stable release

            composer require 'fgtclb/academic-base':'^2'

        ..  tip::

            The ``2.x`` version can already be used and tested in Composer based
            instances. Configure ``minimum-stability: dev`` and ``prefer-stable``
            in your root :file:`composer.json` so requiring the extension still
            prefers stable releases over development versions:

            ..  code-block:: bash

                composer config minimum-stability "dev" \
                    && composer config "prefer-stable" true

            and install the development version with:

            ..  code-block:: bash

                composer require 'fgtclb/academic-base':'2.*.*@dev'

    ..  group-tab:: Extension Manager

        #.  Switch to the module :guilabel:`Admin Tools > Extensions`.
        #.  Switch to :guilabel:`Get Extensions`.
        #.  Search for the extension key :guilabel:`academic_base`.
        #.  Import the extension from the repository.

    ..  group-tab:: Upload ZIP (TER)

        #.  Get the current version from `TER`_ by downloading the ZIP version.
            Alternatively, get the ZIP from the `GitHub Releases`_ page.
        #.  Switch to the module :guilabel:`Admin Tools > Extensions`.
        #.  Enable :guilabel:`Upload Extension`.
        #.  Select or drag the extension ZIP archive and upload the file.

..  _TER: https://extensions.typo3.org/extension/academic_base
..  _GitHub Releases: https://github.com/fgtclb/academic-base/releases
