<?php declare(strict_types=1);

namespace Fgtclb\AcademicPersons\Services;

use Fgtclb\AcademicPersons\Domain\Model\Dto\ContractDemand;
use Fgtclb\AcademicPersons\Domain\Model\Dto\ContractDemandInterface;
use Fgtclb\AcademicPersons\Domain\Model\Dto\ProfileDemand;
use Fgtclb\AcademicPersons\Domain\Model\Dto\ProfileDemandInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

class DemandGenerator
{
    public function overrideProfileDemand(ProfileDemandInterface $demand, array $overwriteDemand): ProfileDemandInterface
    {
        foreach ($overwriteDemand as $propertyName => $propertyValue) {
            if ($propertyValue === '') {
                continue;
            }

            if ($propertyName === 'showPublic') {
                $demand->setShowPublicOnly((bool)$overwriteDemand['showPublic']);
            } elseif ($propertyName === 'organisationalUnits') {
                $demand->setOrganisationalUnits(GeneralUtility::intExplode(',', $overwriteDemand['organisationalUnits'] ?? '', true));
            } elseif ($propertyName === 'functionTypes') {
                $demand->setFunctionTypes(GeneralUtility::intExplode(',', $overwriteDemand['functionTypes'] ?? '', true));
            } else {
                ObjectAccess::setProperty($demand, $propertyName, $propertyValue);
            }
        }

        return $demand;
    }

    public function createProfileDemand(array $settings): ProfileDemandInterface
    {
        $demand = GeneralUtility::makeInstance(ProfileDemand::class);

        if (isset($settings['functionTypes'])
            && is_string($settings['functionTypes'])
            && $settings['functionTypes'] !== ''
        ) {
            $functionTypeUids = GeneralUtility::intExplode(',', $settings['functionTypes'], true);
            if (!empty($functionTypeUids)) {
                $demand->setFunctionTypes($functionTypeUids);
            }
        }

        if (isset($settings['organisationalUnits'])
            && is_string($settings['organisationalUnits'])
            && $settings['organisationalUnits'] !== ''
        ) {
            $organisationalUnitUids = GeneralUtility::intExplode(',', $settings['organisationalUnits'], true);
            if (!empty($organisationalUnitUids)) {
                $demand->setOrganisationalUnits($organisationalUnitUids);
            }
        }

        $demand->setStoragePages((isset($settings['pages']) && $settings['pages'] !== '') ? $settings['pages'] : '');

        /**
         * Introduced with https://github.com/fgtclb/academic-persons/pull/30 to have the option to display profiles in
         * fallback mode even when site language (non-default) is configured to be in strict mode.
         *
         * {@see AcademicPersonsListAndDetailPluginTest::fullyLocalizedListDisplaysLocalizedSelectedProfilesForRequestedLanguageInSelectedOrder()}
         * {@see AcademicPersonsListPluginTest::fullyLocalizedListDisplaysLocalizedSelectedProfilesForRequestedLanguageInSelectedOrder()}
         */
        $fallbackForNonTranslated = (int)($settings['fallbackForNonTranslated'] ?? 0);
        if ($fallbackForNonTranslated === 1) {
            $demand->setFallbackForNonTranslated($fallbackForNonTranslated);
        }

        if (isset($settings['showPublic'])) {
            $demand->setShowPublicOnly((bool)(int)$settings['showPublic']);
        }

        return $demand;
    }

    public function createContractDemand(array $settings): ContractDemandInterface
    {
        $demand = GeneralUtility::makeInstance(ContractDemand::class);
        $demand->setContractList(GeneralUtility::intExplode(',', $settings['selectedContracts'] ?? '', true));

        if (isset($settings['showPublic'])) {
            $demand->setShowPublicOnly((bool)(int)$settings['showPublic']);
        }

        return $demand;
    }
}
