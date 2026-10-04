..  _introduction:

What does it do?
================

The TYPO3 extension Academic Jobs with b-ite displays jobs from the b-Ite
eRecruiting platform embedded in the TYPO3 website. All you need to use it
is the b-ite key, which can usually be read from existing b-Ite job
advertisements in the browser.

As part of the bundling of requirements for the FGTCLB Academic Extensions,
the TYPO3 extension "Academic Jobs b-ite" was created, which only requires
the b-ite key to seamlessly display job adverts from the b-ite platform on
a TYPO3 website.

List view, tile view and table view are currently available as display modes.
There are also options for sorting and grouping as well as a limit for the
number of job adverts to be displayed.

Categorisations that differ from one B-ITE installation to the next, such as
`appointment procedures`, `academic staff`, `non-scientific staff` and
`training positions`, are added by a project with two event listeners and one
TypoScript setting: one listener filters the request by a custom field, the
other writes the category into every job advert, and the setting groups the
list by it. See :ref:`developers`.

..  _third-party-icons:

Third-party icons
-----------------

The SVG icons below :file:`Resources/Public/Icons/`, except
:file:`Extension.svg`, are `Font Awesome Free <https://fontawesome.com>`__
icons by Fonticons, Inc., licensed under the `Creative Commons Attribution 4.0
International license <https://creativecommons.org/licenses/by/4.0/>`__. The
notice :file:`Resources/Public/Icons/LICENSE-font-awesome.txt` lists every file
with its Font Awesome name and the changes made to it.
