..  _important-1790774500:

=======================================================
Important: A job list whose request fails shows no jobs
=======================================================

Description
===========

The service that asks the B-ITE API for the postings kept the last response it
received. On a page with two job lists, a second job list whose request failed
therefore showed the postings of the first one, under its own heading and
settings.

The service keeps nothing between two requests any more. A job list whose
request fails shows the message that no jobs are available, and the error is
logged as before.

Only pages with more than one job list are affected, and only while the B-ITE
API does not answer one of their requests. The behaviour is the same on TYPO3
v13 and v14.

..  index:: Frontend, NotScanned, ext:academic_bite_jobs
