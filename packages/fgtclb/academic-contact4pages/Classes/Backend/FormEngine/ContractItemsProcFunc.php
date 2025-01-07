<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Backend\FormEngine;

use Fgtclb\AcademicPersons\Domain\Repository\ContractRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;

class ContractItemsProcFunc
{
    /**
     * @param array<string, mixed> $parameters
     */
    public function itemsProcFunc(array &$parameters): void
    {
        $querySettings = GeneralUtility::makeInstance(Typo3QuerySettings::class);
        $querySettings->setRespectStoragePage(false);

        $contractRepository = GeneralUtility::makeInstance(ContractRepository::class);
        $contractRepository->setDefaultQuerySettings($querySettings);
        $contracts = $contractRepository->findAll();

        $items = [];
        foreach ($contracts as $contract) {
            $item = [];
            $item['lastName'] = '';
            $item['firstName'] = '';
            if ($contract->getProfile()) {
                $item['lastName'] = $contract->getProfile()->getLastName();
                $item['firstName'] = $contract->getProfile()->getFirstName();
            }

            $item['employeeType'] = '';
            if ($contract->getEmployeeType()) {
                $item['employeeType'] = $contract->getEmployeeType()->getTitle();
            }
            $item['label'] = $contract->getLabel();
            $items[$contract->getUid()] = $item;
        }

        uasort(
            $items,
            fn ($a, $b): int =>
                [$a['lastName'], $a['firstName'], $a['employeeType']]
                <=>
                [$b['lastName'], $b['firstName'], $b['employeeType']]
        );

        foreach ($items as $key => $properties) {
            $parameters['items'][] = [$properties['label'], $key];
        }
    }
}
