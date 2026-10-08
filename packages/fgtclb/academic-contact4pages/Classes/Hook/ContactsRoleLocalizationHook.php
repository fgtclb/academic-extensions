<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Hook;

use FGTCLB\AcademicBase\DataHandling\SecondaryParentLocalizationGuard;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\DataHandling\DataHandler;

/**
 * Keeps the localization of a contacts role from localizing the page contacts it lists
 * (ACE-874). A page contact is translated with its page and keeps the contacts role of
 * its default language record.
 *
 * See {@see SecondaryParentLocalizationGuard}. Registered in `ext_localconf.php` as a
 * `processCmdmapClass`. Public, because the DataHandler instantiates its hooks through
 * `GeneralUtility::makeInstance()`, and stateless.
 */
#[Autoconfigure(public: true)]
final readonly class ContactsRoleLocalizationHook
{
    public function __construct(
        private SecondaryParentLocalizationGuard $secondaryParentLocalizationGuard,
    ) {}

    public function processCmdmap_afterFinish(DataHandler $dataHandler): void
    {
        $this->secondaryParentLocalizationGuard->removeChildrenLocalizedWithParent(
            $dataHandler,
            'tx_academiccontacts4pages_domain_model_role',
            'contacts',
        );
    }
}
