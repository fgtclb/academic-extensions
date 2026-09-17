<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\DataProcessing;

use FGTCLB\AcademicContacts4pages\Service\PageContactsProvider;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

/**
 * Assigns the contacts of the current page, and the roles they carry, to the page
 * rendering.
 *
 * Which contacts those are is decided by `PageContactsProvider`, the very service the
 * contacts content element asks - so a contact without a visible person is left out here
 * exactly as it is there.
 *
 * The service is published (`public: true`) because TypoScript references this processor
 * by class name in `page.10.dataProcessing.400`: TYPO3 takes such an entry from the
 * service container when it knows the name and instantiates the class itself otherwise,
 * and only the container way passes the provider to the constructor.
 */
#[Autoconfigure(public: true)]
class ContactsProcessor implements DataProcessorInterface
{
    public function __construct(
        private readonly PageContactsProvider $pageContactsProvider,
    ) {}

    /**
     * Make project data accessable in Fluid
     *
     * @param ContentObjectRenderer $cObj The data of the content element or page
     * @param array<string, mixed> $contentObjectConfiguration The configuration of Content Object
     * @param array<string, mixed> $processorConfiguration The configuration of this processor
     * @param array<string, mixed> $processedData Key/value store of processed data (e.g. to be passed to a Fluid View)
     * @return array<string, mixed> the processed data as key/value store
     */
    public function process(
        ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData
    ) {
        [$currentRecordTable, $currentRecordUid] = explode(':', $cObj->currentRecord);
        if ($currentRecordTable !== 'pages') {
            return $processedData;
        }

        $pageContacts = $this->pageContactsProvider->get((int)$currentRecordUid);

        $processedData['contacts'] = $pageContacts->contacts;
        $processedData['roles'] = $pageContacts->roles;

        return $processedData;
    }
}
