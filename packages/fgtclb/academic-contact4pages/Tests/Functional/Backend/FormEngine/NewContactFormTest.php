<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Tests\Functional\Backend\FormEngine;

use FGTCLB\AcademicContacts4pages\Tests\Functional\AbstractAcademicContacts4PagesTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The form of a new page contact, compiled the way the backend compiles it when an
 * editor creates one.
 *
 * The record title of a new record is resolved through the `label_userFunc` of the
 * table with the `NEW…` placeholder as uid. `ContactLabels` handed it to an Extbase
 * `findByUid()`, and PostgreSQL rejects the placeholder as input for the integer
 * column (SQLSTATE 22P02), so the form of every new contact failed there. SQLite,
 * MariaDB and MySQL accept the comparison and find nothing, so run this class on
 * PostgreSQL to see the defect.
 */
final class NewContactFormTest extends AbstractAcademicContacts4PagesTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/NewContactForm/page.csv');
        $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['BE_USER'], $GLOBALS['LANG'], $GLOBALS['TYPO3_REQUEST']);
        parent::tearDown();
    }

    #[Test]
    public function theFormOfANewContactIsCompiled(): void
    {
        $request = (new ServerRequest('https://localhost/typo3/record/edit'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $result = GeneralUtility::makeInstance(FormDataCompiler::class)->compile(
            [
                'request' => $request,
                'tableName' => 'tx_academiccontacts4pages_domain_model_contact',
                'vanillaUid' => 40,
                'command' => 'new',
            ],
            // Not `$this->get()`: on TYPO3 v12 `TcaDatabaseRecord` is not a public
            // service, so the test container cannot hand it over.
            GeneralUtility::makeInstance(TcaDatabaseRecord::class),
        );

        $this->assertStringStartsWith('NEW', (string)$result['databaseRow']['uid']);
        $this->assertSame(40, (int)$result['databaseRow']['pid']);
    }
}
