<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Tests\Unit\Backend\FormEngine;

use FGTCLB\AcademicContacts4pages\Backend\FormEngine\ContactLabels;
use FGTCLB\AcademicContacts4pages\Domain\Model\Contact;
use FGTCLB\AcademicContacts4pages\Domain\Repository\ContactRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * The `label_userFunc` of the page contact table. The backend calls it for every record
 * title it renders, also for a record that is deleted or missing, for example from the
 * open documents or the history, and hands it no row or a row without a uid then.
 */
final class ContactLabelsTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    /**
     * @param array<string, mixed> $parameters
     */
    #[Test]
    #[DataProvider('parametersWithoutARecordUidProvider')]
    public function parametersWithoutARecordUidLeaveTheTitleUntouched(array $parameters): void
    {
        $repository = $this->createMock(ContactRepository::class);
        $repository->expects($this->never())->method('findByUid');
        GeneralUtility::setSingletonInstance(ContactRepository::class, $repository);

        $expected = $parameters;
        (new ContactLabels())->getTitle($parameters);

        $this->assertSame($expected, $parameters);
    }

    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function parametersWithoutARecordUidProvider(): array
    {
        return [
            'no row key' => [['table' => 'tx_academiccontacts4pages_domain_model_contact', 'title' => '']],
            'row is null' => [['table' => 'tx_academiccontacts4pages_domain_model_contact', 'row' => null, 'title' => '']],
            'row is empty' => [['table' => 'tx_academiccontacts4pages_domain_model_contact', 'row' => [], 'title' => '']],
            'row without uid' => [['table' => 'tx_academiccontacts4pages_domain_model_contact', 'row' => ['pid' => 1], 'title' => '']],
        ];
    }

    #[Test]
    public function aRecordUidIsLabelledByItsModel(): void
    {
        $record = $this->createMock(Contact::class);
        $record->method('getLabel')->willReturn('Label of record 5');
        $repository = $this->createMock(ContactRepository::class);
        $repository->expects($this->once())->method('findByUid')->with(5)->willReturn($record);
        GeneralUtility::setSingletonInstance(ContactRepository::class, $repository);

        $parameters = ['table' => 'tx_academiccontacts4pages_domain_model_contact', 'row' => ['uid' => 5, 'pid' => 1], 'title' => ''];
        (new ContactLabels())->getTitle($parameters);

        $this->assertSame('Label of record 5', $parameters['title']);
    }
}
