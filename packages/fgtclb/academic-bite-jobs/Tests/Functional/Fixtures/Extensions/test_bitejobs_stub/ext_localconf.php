<?php

declare(strict_types=1);

defined('TYPO3') or die();

// Answer every outgoing HTTP request with a canned response, so functional tests never
// reach the b-ite API. Evaluated identically by TYPO3 v13 and v14.
$GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler'] = \GuzzleHttp\HandlerStack::create(
    new \TESTS\TestBitejobsStub\Http\StubHttpHandler()
);
