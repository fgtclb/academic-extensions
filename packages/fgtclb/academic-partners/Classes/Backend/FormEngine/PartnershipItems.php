<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Backend\FormEngine;

use FGTCLB\AcademicPartners\Domain\Repository\PartnershipRepository;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;

final class PartnershipItems
{
    /**
     * @param array<string, mixed> $parameters
     */
    public function itemsProcFunc(array &$parameters): void
    {
        $querySettings = GeneralUtility::makeInstance(Typo3QuerySettings::class);
        $querySettings->setRespectStoragePage(false);

        $partnershipRepository = GeneralUtility::makeInstance(PartnershipRepository::class);
        $partnershipRepository->setDefaultQuerySettings($querySettings);
        $partnerships = $partnershipRepository->findByPartnerUid($parameters['effectivePid'] ?: 0);

        if ($partnerships !== null) {
            $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
            foreach ($partnerships as $partnership) {
                $parameters['items'][] = [
                    'label' => $pageRepository->getPage($partnership->getPage())['title'],
                    'value' => $partnership->getPage(),
                ];
            }
        }
    }
}
