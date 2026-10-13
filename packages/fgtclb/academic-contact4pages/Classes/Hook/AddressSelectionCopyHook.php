<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Hook;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;

/**
 * Points the address selections of a copied page contact at the copies of the selected
 * addresses (ACE-481).
 *
 * The three address selects of a page contact are relations to the address tables of
 * `academic_persons`, and each of them names its table in `dontRemapTablesOnCopy`. Core
 * would otherwise remap them through the copy mapping of the whole command, and that
 * mapping holds versions as well: the first workspace edit of a contract versions its
 * addresses and its page contacts in one command, so the contact version would select
 * the version row of its address, which publishing deletes.
 *
 * This hook remaps a selection only to a real copy of the selected address, a record
 * that is neither a version (`t3ver_oid`) nor a translation (`l10n_parent`) of it. That
 * covers a copied contract and a copied profile, including the translations of their
 * page contacts, which select the default language address as their default does.
 *
 * `processCmdmap_afterFinish` sees every record the run created in
 * `DataHandler::$copyMappingArray_merged`, also the children of an inline cascade, and
 * fires before the outermost DataHandler updates the reference index, so the index holds
 * the remapped values. The values are written directly, as core writes its own remapping,
 * so the copy does not depend on the editor's access to the three excluded fields.
 *
 * Registered in `ext_localconf.php` as a `processCmdmapClass`. Public, because the
 * DataHandler instantiates its hooks through `GeneralUtility::makeInstance()`, and
 * stateless.
 */
#[Autoconfigure(public: true)]
final readonly class AddressSelectionCopyHook
{
    private const TABLE_CONTACT = 'tx_academiccontacts4pages_domain_model_contact';

    /**
     * @var array<string, string>
     */
    private const SELECTION_FIELDS = [
        'email_address' => 'tx_academicpersons_domain_model_email',
        'phone_number' => 'tx_academicpersons_domain_model_phone_number',
        'physical_address' => 'tx_academicpersons_domain_model_address',
    ];

    public function __construct(
        private ConnectionPool $connectionPool,
    ) {}

    public function processCmdmap_afterFinish(DataHandler $dataHandler): void
    {
        $createdContactUids = $dataHandler->copyMappingArray_merged[self::TABLE_CONTACT] ?? [];
        if (!is_array($createdContactUids) || $createdContactUids === []) {
            return;
        }

        foreach ($createdContactUids as $createdContactUid) {
            $contact = BackendUtility::getRecord(
                self::TABLE_CONTACT,
                (int)$createdContactUid,
                'uid,' . implode(',', array_keys(self::SELECTION_FIELDS))
            );
            if ($contact === null) {
                continue;
            }

            $remappedSelection = [];
            foreach (self::SELECTION_FIELDS as $fieldName => $addressTableName) {
                $selectedUid = (int)($contact[$fieldName] ?? 0);
                if ($selectedUid <= 0) {
                    continue;
                }
                $createdAddressUid = (int)($dataHandler->copyMappingArray_merged[$addressTableName][$selectedUid] ?? 0);
                if ($createdAddressUid > 0 && $this->isCopyOf($addressTableName, $createdAddressUid, $selectedUid)) {
                    $remappedSelection[$fieldName] = $createdAddressUid;
                }
            }

            if ($remappedSelection !== []) {
                $this->connectionPool
                    ->getConnectionForTable(self::TABLE_CONTACT)
                    ->update(self::TABLE_CONTACT, $remappedSelection, ['uid' => (int)$contact['uid']]);
            }
        }
    }

    private function isCopyOf(string $addressTableName, int $createdAddressUid, int $selectedUid): bool
    {
        $createdAddress = BackendUtility::getRecord($addressTableName, $createdAddressUid, 't3ver_oid,l10n_parent');
        if ($createdAddress === null) {
            return false;
        }

        return (int)$createdAddress['t3ver_oid'] !== $selectedUid
            && (int)$createdAddress['l10n_parent'] !== $selectedUid;
    }
}
