<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Backend\FormEngine;

use FGTCLB\AcademicBase\Event\ModifyTcaSelectFieldItemsEvent;
use FGTCLB\AcademicContacts4pages\Exception\InvalidAddressRecordTableException;
use FGTCLB\AcademicPersons\Types\EmailAddressTypes;
use FGTCLB\AcademicPersons\Types\PhoneNumberTypes;
use FGTCLB\AcademicPersons\Types\PhysicalAddressTypes;
use FGTCLB\AcademicPersons\Types\TypesInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * `itemsProcFunc` handlers for the dedicated address record selects of a contact record.
 *
 * They offer the email addresses, phone numbers and physical addresses of the contract the
 * contact points at, on top of the two static items the TCA already defines: display all
 * records of that kind (the default) and display none of them.
 *
 * Hidden records are offered as well, with a prefix marking them as such: whether they
 * reach the frontend is decided by the "Show hidden records" option of the contacts list
 * plugin, not here. Deleted records are left out. Only default language records are
 * offered: the selection is shared by all translations of a contact, and the frontend
 * resolves the language overlay itself.
 *
 * Dispatches {@see ModifyTcaSelectFieldItemsEvent} to allow projects or other extensions
 * to modify the available select items.
 *
 * @phpstan-type ItemsProcFuncParameters array{
 *      items: array<int, array{
 *       label?: string|null,
 *       value?: mixed,
 *       icon?: string|null,
 *       group?: string|null,
 *      }>,
 *      config: array<string, mixed>,
 *      TSconfig: array<string, mixed>,
 *      table: string,
 *      row: array<string, mixed>,
 *      field: string,
 *      effectivePid: int,
 *      site: \TYPO3\CMS\Core\Site\Entity\Site|null,
 *      flexParentDatabaseRow?: array<string, mixed>|null,
 *      inlineParentUid?: int,
 *      inlineParentTableName?: string,
 *      inlineParentFieldName?: string,
 *      inlineParentConfig?: array<string, mixed>,
 *      inlineTopMostParentUid?: int,
 *      inlineTopMostParentTableName?: string,
 *      inlineTopMostParentFieldName?: string,
 *  }
 */
final class AddressRecordItems
{
    private const TABLE_EMAIL_ADDRESS = 'tx_academicpersons_domain_model_email';
    private const TABLE_PHONE_NUMBER = 'tx_academicpersons_domain_model_phone_number';
    private const TABLE_PHYSICAL_ADDRESS = 'tx_academicpersons_domain_model_address';

    /**
     * @var array<string, class-string<TypesInterface>>
     */
    private const TYPES_CLASS_NAMES = [
        self::TABLE_EMAIL_ADDRESS => EmailAddressTypes::class,
        self::TABLE_PHONE_NUMBER => PhoneNumberTypes::class,
        self::TABLE_PHYSICAL_ADDRESS => PhysicalAddressTypes::class,
    ];

    /**
     * @param ItemsProcFuncParameters $parameters
     */
    public function emailAddressItems(array &$parameters): void
    {
        $this->addAddressRecordItems($parameters, self::TABLE_EMAIL_ADDRESS);
    }

    /**
     * @param ItemsProcFuncParameters $parameters
     */
    public function phoneNumberItems(array &$parameters): void
    {
        $this->addAddressRecordItems($parameters, self::TABLE_PHONE_NUMBER);
    }

    /**
     * @param ItemsProcFuncParameters $parameters
     */
    public function physicalAddressItems(array &$parameters): void
    {
        $this->addAddressRecordItems($parameters, self::TABLE_PHYSICAL_ADDRESS);
    }

    /**
     * @param ItemsProcFuncParameters $parameters
     */
    private function addAddressRecordItems(array &$parameters, string $tableName): void
    {
        $contractUid = $this->resolveContractUid($parameters);
        if ($contractUid > 0) {
            foreach ($this->getAddressRecords($tableName, $contractUid) as $addressRecord) {
                $parameters['items'][] = [
                    'label' => $this->buildLabel($tableName, $addressRecord),
                    'value' => (int)$addressRecord['uid'],
                ];
            }
        }

        /** @var ModifyTcaSelectFieldItemsEvent $event */
        $event = GeneralUtility::makeInstance(EventDispatcherInterface::class)->dispatch(new ModifyTcaSelectFieldItemsEvent(parameters: $parameters));
        $parameters = $event->getParameters();
    }

    /**
     * @param ItemsProcFuncParameters $parameters
     */
    private function resolveContractUid(array $parameters): int
    {
        $contract = $parameters['row']['contract'] ?? 0;
        if (is_array($contract)) {
            $contract = $contract[0] ?? 0;
        }
        $contractUid = (int)$contract;
        if ($contractUid > 0) {
            return $contractUid;
        }

        if (($parameters['inlineParentTableName'] ?? '') === 'tx_academicpersons_domain_model_contract') {
            return (int)($parameters['inlineParentUid'] ?? 0);
        }

        return 0;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getAddressRecords(string $tableName, int $contractUid): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        return $queryBuilder
            ->select('*')
            ->from($tableName)
            ->where(
                $queryBuilder->expr()->eq(
                    'contract',
                    $queryBuilder->createNamedParameter($contractUid, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->in(
                    'sys_language_uid',
                    $queryBuilder->quoteArrayBasedValueListToIntegerList([-1, 0])
                ),
            )
            ->orderBy('sorting', 'ASC')
            ->addOrderBy('uid', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * @param array<string, mixed> $addressRecord
     */
    private function buildLabel(string $tableName, array $addressRecord): string
    {
        $label = match ($tableName) {
            self::TABLE_EMAIL_ADDRESS => trim((string)($addressRecord['email'] ?? '')),
            self::TABLE_PHONE_NUMBER => trim((string)($addressRecord['phone_number'] ?? '')),
            self::TABLE_PHYSICAL_ADDRESS => $this->buildPhysicalAddressLabel($addressRecord),
            default => throw new InvalidAddressRecordTableException(
                sprintf('Table "%s" does not hold address records of a contract.', $tableName),
                1786579200
            ),
        };

        $typeLabel = $this->getTypeLabel($tableName, trim((string)($addressRecord['type'] ?? '')));
        if ($typeLabel !== '' && $label !== '') {
            $label = $typeLabel . ': ' . $label;
        } elseif ($label === '' && $typeLabel === '') {
            $label = '#' . (int)($addressRecord['uid'] ?? 0);
        } else {
            $label = $label ?: $typeLabel;
        }

        return $this->markHiddenLabel($label, (bool)($addressRecord['hidden'] ?? false));
    }

    private function markHiddenLabel(string $label, bool $isHidden): string
    {
        if (!$isHidden) {
            return $label;
        }

        $labelPattern = $this->getLanguageService()->sL('LLL:EXT:academic_contacts4pages/Resources/Private/Language/locallang_db.xlf:tx_academiccontacts4pages_domain_model_contact.addressRecord.hidden');
        // A translation that lost the placeholder must not swallow the address itself and
        // leave the editor with a select of identical entries. Precautionary measure for overrides.
        if (!str_contains($labelPattern, '%s')) {
            return $label;
        }

        return sprintf($labelPattern, $label);
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @param array<string, mixed> $addressRecord
     */
    private function buildPhysicalAddressLabel(array $addressRecord): string
    {
        $labelParts = [
            trim(sprintf(
                '%s %s',
                (string)($addressRecord['street'] ?? ''),
                (string)($addressRecord['street_number'] ?? '')
            )),
            trim((string)($addressRecord['additional'] ?? '')),
            trim(sprintf(
                '%s %s',
                (string)($addressRecord['zip'] ?? ''),
                (string)($addressRecord['city'] ?? '')
            )),
            trim((string)($addressRecord['country'] ?? '')),
        ];

        return implode(', ', array_filter($labelParts, static fn(string $labelPart): bool => $labelPart !== ''));
    }

    private function getTypeLabel(string $tableName, string $type): string
    {
        if ($type === '') {
            return '';
        }

        $typesClassName = self::TYPES_CLASS_NAMES[$tableName] ?? throw new InvalidAddressRecordTableException(
            sprintf('Table "%s" does not hold address records of a contract.', $tableName),
            1786579203
        );
        /** @var TypesInterface $types */
        $types = GeneralUtility::makeInstance($typesClassName);

        return (string)($types->getAll()[$type] ?? $type);
    }
}
